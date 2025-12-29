<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use App\Models\Student;
use App\Models\Payment;
use App\Models\FeeType;
use App\Models\Account;
use App\Models\AcademicYear;
use App\Models\SppRate;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;
use UnitEnum;
use BackedEnum;

class PembayaranSPP extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected string $view = 'filament.pages.pembayaran-spp';

    protected static ?string $navigationLabel = 'Pemasukan SPP';

    protected static ?string $title = 'Pemasukan SPP';

    protected static UnitEnum|string|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 1;

    // Hidden from navigation - accessible via Daftar Pemasukan > Tambah
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public $academicYearId = null;
    public $studentId = null;
    public $studentInfo = null;
    public $monthsData = []; // 12 bulan dengan status

    public function mount(): void
    {
        // Set default academic year to active one
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
                Section::make('Filter')
                    ->components([
                        Grid::make(2)
                            ->components([
                                Select::make('academic_year_id')
                                    ->label('Tahun Ajaran')
                                    ->options(AcademicYear::all()->pluck('name', 'id'))
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->academicYearId = $state;
                                        if ($this->studentId) {
                                            $this->loadStudentData();
                                        }
                                    }),

                                Select::make('student_id')
                                    ->label('Siswa')
                                    ->options(Student::where('status', 'active')->get()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->studentId = $state;
                                        $this->loadStudentData();
                                    }),
                            ]),
                    ]),

                Section::make('Informasi Siswa')
                    ->components([
                        Placeholder::make('info')
                            ->label('')
                            ->content(fn() => $this->getStudentInfoHtml()),
                    ])
                    ->visible(fn() => $this->studentId !== null),

                Section::make('Status Pembayaran SPP')
                    ->description('12 Bulan dalam Tahun Ajaran')
                    ->components([
                        Placeholder::make('months_status')
                            ->label('')
                            ->content(fn() => $this->getMonthsStatusHtml()),
                    ])
                    ->visible(fn() => $this->studentId !== null),
            ])
            ->statePath('data');
    }

    protected function loadStudentData()
    {
        if (!$this->studentId || !$this->academicYearId) {
            $this->studentInfo = null;
            $this->monthsData = [];
            return;
        }

        $student = Student::find($this->studentId);
        $academicYear = AcademicYear::find($this->academicYearId);

        if (!$student || !$academicYear) {
            return;
        }

        // Get student's class for this academic year via student_classes
        $studentClass = $student->getClassForYear($this->academicYearId);

        // Check if student is registered in this academic year
        if (!$studentClass) {
            $this->studentInfo = null;
            $this->monthsData = [];
            Notification::make()
                ->warning()
                ->title('Siswa tidak terdaftar')
                ->body('Siswa tidak terdaftar di tahun ajaran ini.')
                ->send();
            return;
        }

        // Get SPP rate for this academic year
        $sppRate = SppRate::where('academic_year_id', $academicYear->id)->first();

        // Generate 12 months based on academic year
        $this->monthsData = $this->generate12Months($academicYear, $student);

        // Count paid and unpaid
        $paidCount = collect($this->monthsData)->where('is_paid', true)->count();
        $unpaidCount = 12 - $paidCount;

        $this->studentInfo = [
            'student' => $student,
            'academic_year' => $academicYear,
            'student_class' => $studentClass,
            'class_name' => $studentClass->classRoom->name,
            'spp_rate' => $sppRate,
            'total_paid' => $paidCount,
            'total_unpaid' => $unpaidCount,
        ];
    }

    protected function generate12Months($academicYear, $student)
    {
        $months = [];
        $startMonth = $academicYear->start_month; // 7 (Juli)
        $endMonth = $academicYear->end_month; // 6 (Juni)

        $currentMonth = $startMonth;
        $currentYear = (int) explode('/', $academicYear->name)[0]; // 2025 dari "2025/2026"

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

        // Get paid months for this student & academic year
        $sppFeeType = FeeType::where('name', 'LIKE', '%SPP%')->first();
        $payments = Payment::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('fee_type_id', $sppFeeType?->id)
            ->get();

        for ($i = 0; $i < 12; $i++) {
            $year = $currentYear;

            // If month wraps around (e.g., Juli 2025 -> Juni 2026)
            if ($currentMonth > 12) {
                $currentMonth = 1;
                $year++;
            }

            // Check if this month is paid
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
                'payment_date' => $payment ? Carbon::parse($payment->payment_date)->format('d/m/Y') : null,
                'amount' => $payment?->amount,
            ];

            $currentMonth++;
        }

        return $months;
    }

    protected function getStudentInfoHtml(): HtmlString
    {
        if (!$this->studentInfo) {
            return new HtmlString('');
        }

        $info = $this->studentInfo;
        $student = $info['student'];
        $academicYear = $info['academic_year'];
        $sppRate = $info['spp_rate'];
        $className = $info['class_name'] ?? '-';

        $html = '<div style="display: flex; flex-direction: column; gap: 0.75rem;">';
        $html .= '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">';
        $html .= '<div><strong style="font-size: 0.75rem; color: #6b7280;">NIS:</strong> <span style="font-weight: 500;">' . $student->nis . '</span></div>';
        $html .= '<div><strong style="font-size: 0.75rem; color: #6b7280;">Nama:</strong> <span style="font-weight: 500;">' . $student->name . '</span></div>';
        $html .= '<div><strong style="font-size: 0.75rem; color: #6b7280;">Kelas:</strong> <span style="font-weight: 500;">' . $className . '</span></div>';
        $html .= '<div><strong style="font-size: 0.75rem; color: #6b7280;">Tahun Ajaran:</strong> <span style="font-weight: 500;">' . $academicYear->name . '</span></div>';
        $html .= '</div>';

        $html .= '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 0.5rem;">';
        $html .= '<div style="padding: 0.75rem; background-color: #eff6ff; border-radius: 0.5rem; border: 1px solid #bfdbfe;"><strong style="font-size: 0.75rem; color: #1e40af;">Tarif SPP/Bulan:</strong> <span style="font-weight: 700; display: block; margin-top: 0.25rem; font-size: 1.125rem; color: #1e3a8a;">Rp ' . number_format($sppRate?->amount ?? 0, 0, ',', '.') . '</span></div>';
        $html .= '<div style="padding: 0.75rem; background-color: #f0fdf4; border-radius: 0.5rem; border: 1px solid #bbf7d0;"><strong style="font-size: 0.75rem; color: #15803d;">Sudah Bayar:</strong> <span style="font-weight: 700; display: block; margin-top: 0.25rem; font-size: 1.125rem; color: #166534;">' . $info['total_paid'] . ' / 12 bulan</span></div>';
        $html .= '<div style="padding: 0.75rem; background-color: #fef2f2; border-radius: 0.5rem; border: 1px solid #fecaca;"><strong style="font-size: 0.75rem; color: #b91c1c;">Belum Bayar:</strong> <span style="font-weight: 700; display: block; margin-top: 0.25rem; font-size: 1.125rem; color: #dc2626;">' . $info['total_unpaid'] . ' bulan</span></div>';
        $html .= '</div>';
        $html .= '</div>';

        return new HtmlString($html);
    }

    protected function getMonthsStatusHtml(): HtmlString
    {
        if (empty($this->monthsData)) {
            return new HtmlString('<p style="font-size: 0.875rem; color: #6b7280;">Pilih siswa untuk melihat status pembayaran</p>');
        }

        $sppRate = $this->studentInfo['spp_rate'];

        $html = '<div style="overflow-x: auto; border-radius: 0.5rem; border: 1px solid #e5e7eb;">';
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

        foreach ($this->monthsData as $index => $month) {
            $rowBg = $month['is_paid'] ? 'background-color: #f0fdf4;' : 'background-color: #fef2f2;';
            $statusBadge = $month['is_paid']
                ? '<span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: #dcfce7; color: #166534;">✓ Lunas</span>'
                : '<span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: #fee2e2; color: #991b1b;">✗ Belum Bayar</span>';

            $borderBottom = ($index < count($this->monthsData) - 1) ? 'border-bottom: 1px solid #e5e7eb;' : '';

            $html .= '<tr style="' . $rowBg . ' ' . $borderBottom . '">';
            $html .= '<td style="padding: 0.75rem 1rem;">' . ($index + 1) . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; font-weight: 500;">' . $month['month_name'] . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; text-align: center;">' . $statusBadge . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem;">' . ($month['payment_date'] ?? '-') . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; text-align: right; font-weight: 500;">' . ($month['amount'] ? 'Rp ' . number_format($month['amount'], 0, ',', '.') : '-') . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; font-family: monospace; font-size: 0.75rem; color: #6b7280;">' . $month['receipt_number'] . '</td>';

            // Action button
            if (!$month['is_paid']) {
                $html .= '<td style="padding: 0.75rem 1rem; text-align: center;">';
                $html .= '<button wire:click="payMonth(' . $month['month'] . ', ' . $month['year'] . ')" type="button" style="padding: 0.375rem 0.75rem; background-color: #3b82f6; color: white; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; cursor: pointer; border: none;">Terima</button>';
                $html .= '</td>';
            } else {
                $html .= '<td style="padding: 0.75rem 1rem; text-align: center; color: #9ca3af;">-</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return new HtmlString($html);
    }

    public function payMonth($month, $year)
    {
        if (!$this->studentId || !$this->academicYearId) {
            Notification::make()
                ->title('Error')
                ->body('Pilih siswa dan tahun ajaran terlebih dahulu')
                ->danger()
                ->send();
            return;
        }

        try {
            $student = Student::find($this->studentId);
            $academicYear = AcademicYear::find($this->academicYearId);
            $sppRate = SppRate::where('academic_year_id', $academicYear->id)->first();
            $sppFeeType = FeeType::where('name', 'LIKE', '%SPP%')->first();
            $defaultAccount = Account::first(); // Atau bisa pilih account

            if (!$sppRate) {
                throw new \Exception('Tarif SPP untuk tahun ajaran ' . $academicYear->name . ' belum diset');
            }

            if (!$sppFeeType) {
                throw new \Exception('Fee Type SPP tidak ditemukan');
            }

            // Check if already paid
            $existing = Payment::where('student_id', $this->studentId)
                ->where('academic_year_id', $this->academicYearId)
                ->where('fee_type_id', $sppFeeType->id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if ($existing) {
                Notification::make()
                    ->title('Info')
                    ->body('Bulan ini sudah dibayar')
                    ->warning()
                    ->send();
                return;
            }

            // Create payment
            $receiptNumber = 'SPP/' . now()->format('Ymd') . '/' . str_pad(Payment::count() + 1, 4, '0', STR_PAD_LEFT);

            Payment::create([
                'receipt_number' => $receiptNumber,
                'student_id' => $this->studentId,
                'fee_type_id' => $sppFeeType->id,
                'academic_year_id' => $this->academicYearId,
                'account_id' => $defaultAccount->id,
                'payment_date' => now(),
                'month' => $month,
                'year' => $year,
                'amount' => $sppRate->amount,
                'payment_method' => 'cash',
                'notes' => 'Pembayaran SPP ' . $this->getMonthName($month) . ' ' . $year,
                'created_by' => auth()->id(),
            ]);

            Notification::make()
                ->title('Berhasil')
                ->body('Pembayaran SPP ' . $this->getMonthName($month) . ' ' . $year . ' berhasil')
                ->success()
                ->send();

            // Reload data
            $this->loadStudentData();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Gagal menyimpan pembayaran: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getMonthName($month)
    {
        $months = [
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
        return $months[$month];
    }
}
