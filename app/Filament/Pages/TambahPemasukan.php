<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use App\Models\FeeType;
use App\Models\FeeRate;
use App\Models\Student;
use App\Models\Account;
use App\Models\AcademicYear;
use App\Models\Payment;
use App\Models\ClassRoom;
use DateTime;
use Illuminate\Support\HtmlString;
use UnitEnum;
use BackedEnum;

class TambahPemasukan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-plus-circle';

    protected string $view = 'filament.pages.tambah-pemasukan';

    protected static ?string $navigationLabel = 'Tambah Pemasukan';

    protected static ?string $title = 'Tambah Pemasukan';

    protected static UnitEnum|string|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];
    public $paymentMode = 'bulk'; // 'individual' or 'bulk'
    public $feeTypeId = null;
    public $feeRateId = null;
    public $accountId = null;
    public $academicYearId = null;
    public $paymentMethod = 'cash';
    public $classFilter = null;
    public $selectedMonths = []; // Array of months for SPP payment
    public $students = [];
    public $selectedStudents = [];

    // Individual payment mode properties
    public $individualStudentId = null;
    public $individualMonthsData = [];

    public function mount(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if ($activeYear) {
            $this->academicYearId = $activeYear->id;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Pemasukan')
                    ->description('Lengkapi informasi pemasukan')
                    ->components([
                        Grid::make(2)
                            ->components([
                                Select::make('fee_type_id')
                                    ->label('Jenis Pemasukan')
                                    ->options(FeeType::all()->pluck('name', 'id'))
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->feeTypeId = $state;
                                        $this->feeRateId = null;
                                        $this->students = [];
                                        $this->selectedStudents = [];
                                        $this->individualStudentId = null;
                                        $this->individualMonthsData = [];
                                    }),

                                Select::make('payment_mode')
                                    ->label('Mode Pembayaran')
                                    ->options([
                                        'individual' => 'Individual (Per Siswa)',
                                        'bulk' => 'Bulk (Massal)',
                                    ])
                                    ->default('bulk')
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->paymentMode = $state;
                                        // Reset hanya data spesifik per mode, jangan reset feeRateId/accountId
                                        if ($state === 'individual') {
                                            // Reset bulk mode data
                                            $this->students = [];
                                            $this->selectedStudents = [];
                                            $this->selectedMonths = [];
                                            $this->classFilter = null;
                                        } else {
                                            // Reset individual mode data
                                            $this->individualStudentId = null;
                                            $this->individualMonthsData = [];
                                        }
                                    })
                                    ->visible(fn() => $this->isSppType()),

                                Select::make('fee_rate_id')
                                    ->label(fn() => $this->isSppType() ? 'Tarif SPP' : 'Pilih Item')
                                    ->options(fn() => $this->getFeeRateOptions())
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->visible(fn() => $this->feeTypeId !== null)
                                    ->afterStateUpdated(function ($state) {
                                        $this->feeRateId = $state;

                                        // Load academic year from selected FeeRate
                                        if ($state) {
                                            $feeRate = \App\Models\FeeRate::find($state);
                                            if ($feeRate && $feeRate->academic_year_id) {
                                                $this->academicYearId = $feeRate->academic_year_id;
                                            }
                                        }

                                        // Reset data yang dependent pada tahun ajaran
                                        $this->selectedMonths = [];
                                        $this->students = [];
                                        $this->selectedStudents = [];
                                        $this->classFilter = null;

                                        // Reload individual student months if already selected
                                        if ($this->individualStudentId) {
                                            $this->loadIndividualMonths();
                                        }
                                    }),

                                Select::make('account_id')
                                    ->label('Akun Kas')
                                    ->options(Account::all()->pluck('name', 'id'))
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->accountId = $state;
                                    }),

                                Select::make('payment_method')
                                    ->label('Metode Pembayaran')
                                    ->options([
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer',
                                        'check' => 'Cek',
                                    ])
                                    ->default('cash')
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->paymentMethod = $state;
                                    }),
                            ]),
                    ])
                    ->visible(fn() => true),

                Section::make('Pilih Bulan SPP')
                    ->description('Centang bulan-bulan yang akan dibayar')
                    ->components([
                        Placeholder::make('month_selector')
                            ->label('')
                            ->content(fn() => $this->getMonthSelectorHtml()),
                    ])
                    ->visible(fn() => $this->isSppType() && $this->feeRateId && $this->paymentMode === 'bulk'),

                Section::make('Pilih Siswa')
                    ->description('Filter kelas dan pilih siswa yang akan membayar')
                    ->components([
                        Grid::make(1)
                            ->components([
                                Select::make('class_filter')
                                    ->label('Filter Kelas')
                                    ->options(ClassRoom::orderBy('name')->pluck('name', 'id'))
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->classFilter = $state;
                                        $this->loadStudents();
                                    }),

                                Placeholder::make('students_table')
                                    ->label('')
                                    ->content(fn() => $this->getStudentsTableHtml()),
                            ]),
                    ])
                    ->visible(fn() => $this->feeRateId && $this->accountId && $this->paymentMethod && $this->paymentMode === 'bulk'),

                // Individual Payment Section
                Section::make('Pembayaran Individual')
                    ->description('Pilih siswa dan bayar per bulan')
                    ->components([
                        Grid::make(2)
                            ->components([
                                Select::make('individual_student_id')
                                    ->label('Pilih Siswa')
                                    ->options(Student::where('status', 'active')
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn($s) => [$s->id => $s->nis . ' - ' . $s->name]))
                                    ->searchable()
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->individualStudentId = $state;
                                        $this->loadIndividualMonths();
                                    }),

                                Placeholder::make('student_info')
                                    ->label('')
                                    ->content(fn() => $this->getIndividualStudentInfoHtml()),
                            ]),

                        Placeholder::make('individual_months_table')
                            ->label('')
                            ->content(fn() => $this->getIndividualMonthsTableHtml()),
                    ])
                    ->visible(fn() => $this->isSppType() && $this->feeRateId && $this->paymentMode === 'individual'),
            ])
            ->statePath('data');
    }

    protected function isSppType(): bool
    {
        if (!$this->feeTypeId)
            return false;

        $feeType = FeeType::find($this->feeTypeId);
        return $feeType && str_contains(strtolower($feeType->name), 'spp');
    }

    protected function getFeeRateOptions(): array
    {
        if (!$this->feeTypeId)
            return [];

        $query = FeeRate::where('fee_type_id', $this->feeTypeId)
            ->where('is_active', true);

        // Tampilkan semua items tanpa filter tahun ajaran
        // Untuk SPP maupun kegiatan lainnya

        return $query->get()
            ->mapWithKeys(fn($rate) => [
                $rate->id => $rate->name . ' - Rp ' . number_format((float) $rate->amount, 0, ',', '.')
            ])
            ->toArray();
    }

    protected function getMonthSelectorHtml(): HtmlString
    {
        if (!$this->feeRateId) {
            return new HtmlString('<p style="color: #6b7280;">Pilih tarif SPP terlebih dahulu</p>');
        }

        $feeRate = FeeRate::find($this->feeRateId);
        if (!$feeRate || !$feeRate->academic_year_id) {
            return new HtmlString('<p style="color: #ef4444;">Tarif SPP harus memiliki tahun ajaran</p>');
        }

        $academicYear = AcademicYear::find($feeRate->academic_year_id);
        if (!$academicYear) {
            return new HtmlString('<p style="color: #ef4444;">Tahun ajaran tidak ditemukan</p>');
        }

        $months = $this->generate12MonthsForYear($academicYear);

        $html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem;">';

        foreach ($months as $month) {
            $monthKey = $month['month'] . '-' . $month['year'];
            $isChecked = in_array($monthKey, $this->selectedMonths);

            $html .= '<label style="display: flex; align-items: center; padding: 0.75rem; border: 2px solid ' . ($isChecked ? '#3b82f6' : '#e5e7eb') . '; border-radius: 0.5rem; cursor: pointer; background-color: ' . ($isChecked ? '#dbeafe' : 'white') . ';">';
            $html .= '<input type="checkbox" wire:click="toggleMonth(\'' . $monthKey . '\')" ' . ($isChecked ? 'checked' : '') . ' style="margin-right: 0.5rem; cursor: pointer;">';
            $html .= '<span style="font-weight: 500;">' . $month['month_name'] . '</span>';
            $html .= '</label>';
        }

        $html .= '</div>';

        if (!empty($this->selectedMonths)) {
            $html .= '<div style="margin-top: 1rem; padding: 0.75rem; background-color: #dbeafe; border-radius: 0.5rem;">';
            $html .= '<p style="font-weight: 600; color: #1e40af;">Bulan dipilih: ' . count($this->selectedMonths) . ' bulan</p>';
            $html .= '<p style="color: #1e40af; font-size: 0.875rem;">Total per siswa: Rp ' . number_format($feeRate->amount * count($this->selectedMonths), 0, ',', '.') . '</p>';
            $html .= '</div>';
        }

        return new HtmlString($html);
    }

    protected function generate12MonthsForYear($academicYear): array
    {
        $months = [];
        $startMonth = $academicYear->start_month;
        $currentMonth = $startMonth;
        $currentYear = (int) explode('/', $academicYear->name)[0];

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        for ($i = 0; $i < 12; $i++) {
            $year = $currentYear;

            if ($currentMonth > 12) {
                $currentMonth = 1;
                $year++;
            }

            $months[] = [
                'month' => $currentMonth,
                'year' => $year,
                'month_name' => $monthNames[$currentMonth] . ' ' . $year,
            ];

            $currentMonth++;
        }

        return $months;
    }

    public function toggleMonth($monthKey): void
    {
        if (in_array($monthKey, $this->selectedMonths)) {
            $this->selectedMonths = array_values(array_diff($this->selectedMonths, [$monthKey]));
        } else {
            $this->selectedMonths[] = $monthKey;
        }

        // Reload students to update their payment status
        if ($this->classFilter) {
            $this->loadStudents();
        }
    }

    protected function loadStudents(): void
    {
        if (!$this->classFilter) {
            $this->students = [];
            return;
        }

        // Get academic year from selected fee rate (for SPP) or use active year
        $academicYearId = $this->academicYearId;

        if ($this->feeRateId) {
            $feeRate = FeeRate::find($this->feeRateId);
            if ($feeRate && $feeRate->academic_year_id) {
                $academicYearId = $feeRate->academic_year_id;
            }
        }

        if (!$academicYearId) {
            $this->students = [];
            return;
        }

        // Get students in this class for this academic year
        $this->students = Student::where('status', 'active')
            ->whereHas('studentClasses', function ($q) use ($academicYearId) {
                $q->where('class_id', $this->classFilter)
                    ->where('academic_year_id', $academicYearId);
            })
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    protected function getStudentsTableHtml(): HtmlString
    {
        if (empty($this->students)) {
            if (!$this->classFilter) {
                return new HtmlString('<p style="color: #6b7280;">Pilih kelas untuk melihat daftar siswa</p>');
            }
            return new HtmlString('<p style="color: #6b7280;">Tidak ada siswa di kelas ini</p>');
        }

        $feeRate = FeeRate::find($this->feeRateId);
        if (!$feeRate) {
            return new HtmlString('<p style="color: #ef4444;">Error: Tarif tidak ditemukan</p>');
        }

        $html = '<div style="margin-bottom: 1rem;">';
        $html .= '<button wire:click="selectAll" type="button" style="padding: 0.5rem 1rem; background-color: #3b82f6; color: white; border-radius: 0.375rem; margin-right: 0.5rem; border: none; cursor: pointer;">Select All</button>';
        $html .= '<button wire:click="deselectAll" type="button" style="padding: 0.5rem 1rem; background-color: #6b7280; color: white; border-radius: 0.375rem; margin-right: 1rem; border: none; cursor: pointer;">Deselect All</button>';
        $html .= '<button wire:click="bulkPay" type="button" style="padding: 0.5rem 1rem; background-color: #10b981; color: white; border-radius: 0.375rem; font-weight: 600; border: none; cursor: pointer;">Terima Pembayaran (' . count($this->selectedStudents) . ' siswa)</button>';
        $html .= '</div>';

        $html .= '<div style="overflow-x: auto; border-radius: 0.5rem; border: 1px solid #e5e7eb;">';
        $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">';
        $html .= '<thead style="background-color: #f9fafb;">';
        $html .= '<tr>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb; width: 50px;">
                    <input type="checkbox" wire:click="toggleSelectAll" ' . (count($this->selectedStudents) === count($this->students) && count($this->students) > 0 ? 'checked' : '') . ' style="cursor: pointer;">
                  </th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">No</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">NIS</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Nama</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Jumlah</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($this->students as $index => $student) {
            $isSelected = in_array($student['id'], $this->selectedStudents);
            $rowBg = $index % 2 == 0 ? 'background-color: white;' : 'background-color: #f9fafb;';

            $html .= '<tr style="' . $rowBg . ($isSelected ? ' background-color: #dbeafe;' : '') . '">';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">
                        <input type="checkbox" wire:click="toggleStudent(' . $student['id'] . ')" ' . ($isSelected ? 'checked' : '') . ' style="cursor: pointer;">
                      </td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">' . ($index + 1) . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; font-weight: 500;">' . ($student['nis'] ?? '-') . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">' . $student['name'] . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600;">Rp ' . number_format((float) $feeRate->amount, 0, ',', '.') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= '<div style="margin-top: 1rem; padding: 1rem; background-color: #f9fafb; border-radius: 0.5rem;">';
        $html .= '<p style="font-weight: 600; margin-bottom: 0.5rem;">Ringkasan:</p>';
        $html .= '<p>Total Siswa Dipilih: <strong>' . count($this->selectedStudents) . '</strong></p>';
        $html .= '<p>Total Pembayaran: <strong>Rp ' . number_format($feeRate->amount * count($this->selectedStudents), 0, ',', '.') . '</strong></p>';
        $html .= '</div>';

        return new HtmlString($html);
    }

    public function toggleStudent($studentId): void
    {
        if (in_array($studentId, $this->selectedStudents)) {
            $this->selectedStudents = array_values(array_diff($this->selectedStudents, [$studentId]));
        } else {
            $this->selectedStudents[] = $studentId;
        }
    }

    protected function getSppStudentsTableHtml($feeRate): HtmlString
    {
        // Get existing payments for selected months
        $studentsWithPaymentStatus = [];

        foreach ($this->students as $student) {
            $unpaidMonths = [];
            $totalUnpaid = 0;

            foreach ($this->selectedMonths as $monthKey) {
                list($month, $year) = explode('-', $monthKey);

                // Check if payment exists
                $payment = Payment::where('student_id', $student['id'])
                    ->where('fee_type_id', $this->feeTypeId)
                    ->where('month', (int) $month)
                    ->where('year', (int) $year)
                    ->first();

                if (!$payment) {
                    $unpaidMonths[] = $monthKey;
                    $totalUnpaid++;
                }
            }

            if ($totalUnpaid > 0) {
                $studentsWithPaymentStatus[] = [
                    'student' => $student,
                    'unpaid_months' => $unpaidMonths,
                    'unpaid_count' => $totalUnpaid,
                    'total_amount' => $totalUnpaid * $feeRate->amount,
                ];
            }
        }

        if (empty($studentsWithPaymentStatus)) {
            return new HtmlString('<p style="color: #10b981; font-weight: 600;">✓ Semua siswa sudah lunas untuk bulan yang dipilih</p>');
        }

        $html = '<div style="margin-bottom: 1rem;">';
        $html .= '<button wire:click="selectAll" type="button" style="padding: 0.5rem 1rem; background-color: #3b82f6; color: white; border-radius: 0.375rem; margin-right: 0.5rem; border: none; cursor: pointer;">Select All</button>';
        $html .= '<button wire:click="deselectAll" type="button" style="padding: 0.5rem 1rem; background-color: #6b7280; color: white; border-radius: 0.375rem; margin-right: 1rem; border: none; cursor: pointer;">Deselect All</button>';
        $html .= '<button wire:click="bulkPay" type="button" style="padding: 0.5rem 1rem; background-color: #10b981; color: white; border-radius: 0.375rem; font-weight: 600; border: none; cursor: pointer;">Terima Pembayaran (' . count($this->selectedStudents) . ' siswa)</button>';
        $html .= '</div>';

        $html .= '<div style="overflow-x: auto; border-radius: 0.5rem; border: 1px solid #e5e7eb;">';
        $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">';
        $html .= '<thead style="background-color: #f9fafb;">';
        $html .= '<tr>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb; width: 50px;">☑</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">No</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">NIS</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Nama</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Bulan Belum Bayar</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Total Tagihan</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($studentsWithPaymentStatus as $index => $data) {
            $student = $data['student'];
            $isSelected = in_array($student['id'], $this->selectedStudents);
            $rowBg = $index % 2 == 0 ? 'background-color: white;' : 'background-color: #f9fafb;';

            $html .= '<tr style="' . $rowBg . ($isSelected ? ' background-color: #dbeafe;' : '') . '">';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">
                        <input type="checkbox" wire:click="toggleStudent(' . $student['id'] . ')" ' . ($isSelected ? 'checked' : '') . ' style="cursor: pointer;">
                      </td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">' . ($index + 1) . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; font-weight: 500;">' . ($student['nis'] ?? '-') . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">' . $student['name'] . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: center;"><span style="padding: 0.25rem 0.75rem; background-color: #fef3c7; color: #92400e; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">' . $data['unpaid_count'] . ' bulan</span></td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600; color: #dc2626;">Rp ' . number_format($data['total_amount'], 0, ',', '.') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        $totalAmount = array_sum(array_column($studentsWithPaymentStatus, 'total_amount'));
        $selectedAmount = 0;
        foreach ($studentsWithPaymentStatus as $data) {
            if (in_array($data['student']['id'], $this->selectedStudents)) {
                $selectedAmount += $data['total_amount'];
            }
        }

        $html .= '<div style="margin-top: 1rem; padding: 1rem; background-color: #f9fafb; border-radius: 0.5rem;">';
        $html .= '<p style="font-weight: 600; margin-bottom: 0.5rem;">Ringkasan:</p>';
        $html .= '<p>Total Siswa Belum Lunas: <strong>' . count($studentsWithPaymentStatus) . '</strong></p>';
        $html .= '<p>Total Siswa Dipilih: <strong>' . count($this->selectedStudents) . '</strong></p>';
        $html .= '<p style="font-size: 1.125rem; font-weight: 700; color: #dc2626; margin-top: 0.5rem;">Total Pembayaran: Rp ' . number_format($selectedAmount, 0, ',', '.') . '</p>';
        $html .= '</div>';

        return new HtmlString($html);
    }

    public function selectAll(): void
    {
        $this->selectedStudents = array_column($this->students, 'id');
    }

    public function deselectAll(): void
    {
        $this->selectedStudents = [];
    }

    public function toggleSelectAll(): void
    {
        if (count($this->selectedStudents) === count($this->students) && count($this->students) > 0) {
            $this->deselectAll();
        } else {
            $this->selectAll();
        }
    }

    protected function resetForm(): void
    {
        $this->feeRateId = null;
        $this->students = [];
        $this->selectedStudents = [];
        $this->selectedMonths = [];
        $this->individualStudentId = null;
        $this->individualMonthsData = [];
        $this->classFilter = null;
    }

    // Individual Payment Methods
    protected function loadIndividualMonths(): void
    {
        if (!$this->individualStudentId || !$this->feeRateId) {
            $this->individualMonthsData = [];
            return;
        }

        $student = Student::find($this->individualStudentId);
        $feeRate = FeeRate::find($this->feeRateId);

        if (!$student || !$feeRate || !$feeRate->academic_year_id) {
            $this->individualMonthsData = [];
            return;
        }

        $academicYear = AcademicYear::find($feeRate->academic_year_id);
        if (!$academicYear) {
            $this->individualMonthsData = [];
            return;
        }

        // Check if student is registered in this academic year
        $studentClass = $student->studentClasses()
            ->where('academic_year_id', $academicYear->id)
            ->first();

        if (!$studentClass) {
            Notification::make()
                ->warning()
                ->title('Siswa tidak terdaftar')
                ->body('Siswa tidak terdaftar di tahun ajaran ' . $academicYear->name)
                ->send();
            $this->individualMonthsData = [];
            return;
        }

        // Generate 12 months with payment status
        $this->individualMonthsData = $this->generate12MonthsWithPaymentStatus($academicYear, $student);
    }

    protected function generate12MonthsWithPaymentStatus($academicYear, $student): array
    {
        $months = [];
        $startMonth = $academicYear->start_month;
        $currentMonth = $startMonth;
        $currentYear = (int) explode('/', $academicYear->name)[0];

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        // Get existing payments
        $payments = Payment::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('fee_type_id', $this->feeTypeId)
            ->get();

        for ($i = 0; $i < 12; $i++) {
            $year = $currentYear;
            if ($currentMonth > 12) {
                $currentMonth = 1;
                $year++;
            }

            $payment = $payments->first(function ($p) use ($currentMonth, $year) {
                return $p->month == $currentMonth && $p->year == $year;
            });

            $months[] = [
                'month' => $currentMonth,
                'year' => $year,
                'month_name' => $monthNames[$currentMonth] . ' ' . $year,
                'is_paid' => $payment !== null,
                'payment' => $payment,
                'receipt_number' => $payment?->receipt_number ?? '-',
                'payment_date' => $payment ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : null,
                'amount' => $payment?->amount,
            ];

            $currentMonth++;
        }

        return $months;
    }

    protected function getIndividualStudentInfoHtml(): HtmlString
    {
        if (!$this->individualStudentId || empty($this->individualMonthsData)) {
            return new HtmlString('');
        }

        $student = Student::find($this->individualStudentId);
        $feeRate = FeeRate::find($this->feeRateId);
        $academicYear = AcademicYear::find($feeRate->academic_year_id);

        $studentClass = $student->studentClasses()
            ->where('academic_year_id', $academicYear->id)
            ->first();

        $className = $studentClass?->classRoom->name ?? '-';

        $paidCount = collect($this->individualMonthsData)->where('is_paid', true)->count();
        $unpaidCount = 12 - $paidCount;

        $html = '<div style="padding: 1rem; background-color: #f9fafb; border-radius: 0.5rem; border: 1px solid #e5e7eb;">';
        $html .= '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 0.75rem;">';
        $html .= '<div><strong style="font-size: 0.75rem; color: #6b7280;">NIS:</strong> <span style="font-weight: 500;">' . $student->nis . '</span></div>';
        $html .= '<div><strong style="font-size: 0.75rem; color: #6b7280;">Nama:</strong> <span style="font-weight: 500;">' . $student->name . '</span></div>';
        $html .= '<div><strong style="font-size: 0.75rem; color: #6b7280;">Kelas:</strong> <span style="font-weight: 500;">' . $className . '</span></div>';
        $html .= '<div><strong style="font-size: 0.75rem; color: #6b7280;">Tahun Ajaran:</strong> <span style="font-weight: 500;">' . $academicYear->name . '</span></div>';
        $html .= '</div>';

        $html .= '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">';
        $html .= '<div style="padding: 0.75rem; background-color: #eff6ff; border-radius: 0.5rem; border: 1px solid #bfdbfe;"><strong style="font-size: 0.75rem; color: #1e40af;">Tarif SPP/Bulan:</strong> <span style="font-weight: 700; display: block; margin-top: 0.25rem; font-size: 1.125rem; color: #1e3a8a;">Rp ' . number_format((float) $feeRate->amount, 0, ',', '.') . '</span></div>';
        $html .= '<div style="padding: 0.75rem; background-color: #f0fdf4; border-radius: 0.5rem; border: 1px solid #bbf7d0;"><strong style="font-size: 0.75rem; color: #15803d;">Sudah Bayar:</strong> <span style="font-weight: 700; display: block; margin-top: 0.25rem; font-size: 1.125rem; color: #166534;">' . $paidCount . ' / 12 bulan</span></div>';
        $html .= '<div style="padding: 0.75rem; background-color: #fef2f2; border-radius: 0.5rem; border: 1px solid #fecaca;"><strong style="font-size: 0.75rem; color: #b91c1c;">Belum Bayar:</strong> <span style="font-weight: 700; display: block; margin-top: 0.25rem; font-size: 1.125rem; color: #dc2626;">' . $unpaidCount . ' bulan</span></div>';
        $html .= '</div>';
        $html .= '</div>';

        return new HtmlString($html);
    }

    protected function getIndividualMonthsTableHtml(): HtmlString
    {
        if (empty($this->individualMonthsData)) {
            return new HtmlString('<p style="color: #6b7280; margin-top: 1rem;">Pilih siswa untuk melihat status pembayaran SPP</p>');
        }

        $feeRate = FeeRate::find($this->feeRateId);

        $html = '<div style="overflow-x: auto; border-radius: 0.5rem; border: 1px solid #e5e7eb; margin-top: 1rem;">';
        $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">';
        $html .= '<thead style="background-color: #f9fafb;">';
        $html .= '<tr>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">No</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Bulan</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Status</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Tanggal Bayar</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Nominal</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">No. Struk</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Aksi</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($this->individualMonthsData as $index => $month) {
            $rowBg = $month['is_paid'] ? 'background-color: #f0fdf4;' : 'background-color: #fef2f2;';
            $statusBadge = $month['is_paid']
                ? '<span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: #dcfce7; color: #166534;">✓ Lunas</span>'
                : '<span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: #fee2e2; color: #991b1b;">✗ Belum Bayar</span>';

            $html .= '<tr style="' . $rowBg . '">';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">' . ($index + 1) . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; font-weight: 500;">' . $month['month_name'] . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; text-align: center; border-bottom: 1px solid #e5e7eb;">' . $statusBadge . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">' . ($month['payment_date'] ?? '-') . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; text-align: right; border-bottom: 1px solid #e5e7eb; font-weight: 500;">' . ($month['amount'] ? 'Rp ' . number_format((float) $month['amount'], 0, ',', '.') : '-') . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; font-family: monospace; font-size: 0.75rem; color: #6b7280;">' . $month['receipt_number'] . '</td>';

            // Action button
            if (!$month['is_paid']) {
                $html .= '<td style="padding: 0.75rem 1rem; text-align: center; border-bottom: 1px solid #e5e7eb;">';
                $html .= '<button wire:click="payIndividualMonth(' . $month['month'] . ', ' . $month['year'] . ')" type="button" style="padding: 0.375rem 0.75rem; background-color: #3b82f6; color: white; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; cursor: pointer; border: none;">Terima (Rp ' . number_format((float) $feeRate->amount, 0, ',', '.') . ')</button>';
                $html .= '</td>';
            } else {
                $html .= '<td style="padding: 0.75rem 1rem; text-align: center; border-bottom: 1px solid #e5e7eb; color: #9ca3af;">-</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return new HtmlString($html);
    }

    public function payIndividualMonth($month, $year): void
    {
        if (!$this->individualStudentId || !$this->feeRateId || !$this->accountId) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Data tidak lengkap')
                ->send();
            return;
        }

        $feeRate = FeeRate::find($this->feeRateId);
        if (!$feeRate) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Tarif tidak ditemukan')
                ->send();
            return;
        }

        // Check if already paid
        $existing = Payment::where('student_id', $this->individualStudentId)
            ->where('academic_year_id', $feeRate->academic_year_id)
            ->where('fee_type_id', $this->feeTypeId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existing) {
            Notification::make()
                ->warning()
                ->title('Sudah dibayar')
                ->body('Bulan ini sudah dibayar')
                ->send();
            return;
        }

        try {
            // Generate receipt number
            $lastPayment = Payment::latest('id')->first();
            $receiptNumber = 'PMK-' . date('Ymd') . '-' . str_pad(($lastPayment?->id ?? 0) + 1, 4, '0', STR_PAD_LEFT);

            $monthName = DateTime::createFromFormat('!m', $month)->format('F');

            Payment::create([
                'receipt_number' => $receiptNumber,
                'student_id' => $this->individualStudentId,
                'fee_type_id' => $this->feeTypeId,
                'account_id' => $this->accountId,
                'academic_year_id' => $feeRate->academic_year_id,
                'payment_date' => now(),
                'month' => $month,
                'year' => $year,
                'amount' => $feeRate->amount,
                'payment_method' => $this->paymentMethod,
                'notes' => 'SPP ' . $monthName . ' ' . $year,
                'created_by' => auth()->id(),
            ]);

            Notification::make()
                ->success()
                ->title('Berhasil')
                ->body('Pembayaran SPP berhasil diterima')
                ->send();

            // Reload data
            $this->loadIndividualMonths();

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->send();
        }
    }

    public function bulkPay(): void
    {
        if (empty($this->selectedStudents)) {
            Notification::make()
                ->warning()
                ->title('Tidak ada siswa dipilih')
                ->body('Pilih minimal satu siswa untuk melakukan pembayaran')
                ->send();
            return;
        }

        if (!$this->feeRateId || !$this->accountId || !$this->academicYearId) {
            Notification::make()
                ->danger()
                ->title('Data tidak lengkap')
                ->body('Pastikan semua field sudah diisi')
                ->send();
            return;
        }

        $feeRate = FeeRate::find($this->feeRateId);
        if (!$feeRate) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Tarif tidak ditemukan')
                ->send();
            return;
        }

        $successCount = 0;
        $failedCount = 0;

        // Get academic year from fee rate
        $academicYearId = $feeRate->academic_year_id ?? $this->academicYearId;

        if ($this->isSppType()) {
            // SPP: Pay for each unpaid month per student
            if (empty($this->selectedMonths)) {
                Notification::make()
                    ->warning()
                    ->title('Pilih bulan SPP')
                    ->body('Silakan pilih minimal satu bulan untuk pembayaran SPP')
                    ->send();
                return;
            }

            foreach ($this->selectedStudents as $studentId) {
                // Check which months are unpaid for this student
                $unpaidMonths = [];
                foreach ($this->selectedMonths as $monthYear) {
                    [$month, $year] = explode('-', $monthYear);

                    // Check if payment already exists
                    $existingPayment = Payment::where('student_id', $studentId)
                        ->where('fee_type_id', $this->feeTypeId)
                        ->where('month', (int) $month)
                        ->where('year', (int) $year)
                        ->where('academic_year_id', $academicYearId)
                        ->exists();

                    if (!$existingPayment) {
                        $unpaidMonths[] = [
                            'month' => (int) $month,
                            'year' => (int) $year
                        ];
                    }
                }

                // Create payment for each unpaid month
                foreach ($unpaidMonths as $monthData) {
                    try {
                        // Generate unique receipt number
                        $lastPayment = Payment::latest('id')->first();
                        $receiptNumber = 'PMK-' . date('Ymd') . '-' . str_pad(($lastPayment?->id ?? 0) + 1, 4, '0', STR_PAD_LEFT);

                        Payment::create([
                            'receipt_number' => $receiptNumber,
                            'student_id' => $studentId,
                            'fee_type_id' => $this->feeTypeId,
                            'account_id' => $this->accountId,
                            'academic_year_id' => $academicYearId,
                            'payment_date' => now(),
                            'month' => $monthData['month'],
                            'year' => $monthData['year'],
                            'amount' => $feeRate->amount,
                            'payment_method' => $this->paymentMethod,
                            'notes' => 'SPP ' . DateTime::createFromFormat('!m', $monthData['month'])->format('F') . ' ' . $monthData['year'],
                            'created_by' => auth()->id(),
                        ]);

                        $successCount++;
                    } catch (\Exception $e) {
                        $failedCount++;
                    }
                }
            }
        } else {
            // Non-SPP: Single payment per student
            foreach ($this->selectedStudents as $studentId) {
                try {
                    // Generate receipt number
                    $lastPayment = Payment::latest('id')->first();
                    $receiptNumber = 'PMK-' . date('Ymd') . '-' . str_pad(($lastPayment?->id ?? 0) + 1, 4, '0', STR_PAD_LEFT);

                    Payment::create([
                        'receipt_number' => $receiptNumber,
                        'student_id' => $studentId,
                        'fee_type_id' => $this->feeTypeId,
                        'account_id' => $this->accountId,
                        'academic_year_id' => $academicYearId,
                        'payment_date' => now(),
                        'month' => null,
                        'year' => null,
                        'amount' => $feeRate->amount,
                        'payment_method' => $this->paymentMethod,
                        'notes' => 'Bulk payment: ' . $feeRate->name,
                        'created_by' => auth()->id(),
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                }
            }
        }

        if ($successCount > 0) {
            Notification::make()
                ->success()
                ->title('Pembayaran Berhasil')
                ->body("$successCount pembayaran berhasil disimpan" . ($failedCount > 0 ? ", $failedCount gagal" : ''))
                ->send();

            // Reset selection
            $this->selectedStudents = [];
            $this->loadStudents();
        } else {
            Notification::make()
                ->danger()
                ->title('Pembayaran Gagal')
                ->body('Semua pembayaran gagal diproses')
                ->send();
        }
    }
}
