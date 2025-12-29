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
    public $feeTypeId = null;
    public $feeRateId = null;
    public $accountId = null;
    public $academicYearId = null;
    public $paymentMethod = 'cash';
    public $classFilter = null;
    public $students = [];
    public $selectedStudents = [];

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
                                    }),

                                Select::make('fee_rate_id')
                                    ->label(fn() => $this->isSppType() ? 'Tarif SPP' : 'Pilih Item')
                                    ->options(fn() => $this->getFeeRateOptions())
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->visible(fn() => $this->feeTypeId !== null)
                                    ->afterStateUpdated(function ($state) {
                                        $this->feeRateId = $state;
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
                    ->visible(fn() => $this->feeRateId && $this->accountId && $this->paymentMethod),
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
                $rate->id => $rate->name . ' - Rp ' . number_format($rate->amount, 0, ',', '.')
            ])
            ->toArray();
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
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: 600;">Rp ' . number_format($feeRate->amount, 0, ',', '.') . '</td>';
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

        foreach ($this->selectedStudents as $studentId) {
            try {
                // Generate receipt number
                $lastPayment = Payment::latest('id')->first();
                $receiptNumber = 'PMK-' . date('Ymd') . '-' . str_pad(($lastPayment?->id ?? 0) + 1, 4, '0', STR_PAD_LEFT);

                // For SPP, use current month/year, for others null
                $month = null;
                $year = null;

                if ($this->isSppType()) {
                    $month = now()->month;
                    $year = now()->year;
                }

                // Get academic year from fee rate or use default
                $academicYearId = $feeRate->academic_year_id ?? $this->academicYearId;

                Payment::create([
                    'receipt_number' => $receiptNumber,
                    'student_id' => $studentId,
                    'fee_type_id' => $this->feeTypeId,
                    'account_id' => $this->accountId,
                    'academic_year_id' => $academicYearId,
                    'payment_date' => now(),
                    'month' => $month,
                    'year' => $year,
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
