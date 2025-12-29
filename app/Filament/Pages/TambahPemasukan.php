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
use Filament\Notifications\Notification;
use App\Models\FeeType;
use App\Models\Student;
use App\Models\Account;
use App\Models\AcademicYear;
use App\Models\Payment;
use App\Models\SppRate;
use Carbon\Carbon;
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
    public $accountId = null;
    public $studentId = null;
    public $academicYearId = null;
    public $monthsData = [];

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
                Section::make('Pilih Jenis Pemasukan')
                    ->description('Pilih jenis pemasukan yang akan diinput')
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
                                        $this->monthsData = [];
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
                            ]),
                    ]),

                // SPP Form
                Section::make('Input SPP')
                    ->description('Pilih siswa dan klik tombol Terima pada bulan yang ingin dibayar')
                    ->components([
                        Grid::make(2)
                            ->components([
                                Select::make('student_id')
                                    ->label('Siswa')
                                    ->options(Student::where('status', 'active')
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn($s) => [$s->id => $s->nis . ' - ' . $s->name]))
                                    ->searchable()
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->studentId = $state;
                                        if ($this->academicYearId) {
                                            $this->loadSppMonths();
                                        }
                                    }),

                                Select::make('academic_year_id')
                                    ->label('Tahun Ajaran')
                                    ->options(AcademicYear::orderBy('name', 'desc')->pluck('name', 'id'))
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->academicYearId = $state;
                                        if ($this->studentId) {
                                            $this->loadSppMonths();
                                        }
                                    }),
                            ]),

                        Placeholder::make('months_table')
                            ->label('')
                            ->content(fn() => $this->getMonthsTableHtml()),
                    ])
                    ->visible(fn() => $this->isSppType()),

                // General Form (for non-SPP)
                Section::make('Input Pemasukan')
                    ->components([
                        Grid::make(2)
                            ->components([
                                Select::make('student_id_general')
                                    ->label('Siswa')
                                    ->options(Student::where('status', 'active')
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn($s) => [$s->id => $s->nis . ' - ' . $s->name]))
                                    ->searchable()
                                    ->required()
                                    ->native(false),

                                Select::make('academic_year_id_general')
                                    ->label('Tahun Ajaran')
                                    ->options(AcademicYear::orderBy('name', 'desc')->pluck('name', 'id'))
                                    ->required()
                                    ->native(false),

                                DatePicker::make('payment_date')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(now())
                                    ->native(false),

                                TextInput::make('amount')
                                    ->label('Jumlah')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('0'),

                                Select::make('payment_method')
                                    ->label('Metode')
                                    ->options([
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer',
                                        'check' => 'Cek',
                                    ])
                                    ->default('cash')
                                    ->required()
                                    ->native(false),

                                Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->visible(fn() => !$this->isSppType() && $this->feeTypeId)
                    ->footerActions([
                        \Filament\Actions\Action::make('save')
                            ->label('Simpan Pemasukan')
                            ->action('saveGeneralIncome')
                            ->requiresConfirmation()
                            ->color('success'),
                    ]),
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

    protected function loadSppMonths(): void
    {
        if (!$this->studentId || !$this->academicYearId) {
            $this->monthsData = [];
            return;
        }

        $student = Student::find($this->studentId);
        $academicYear = AcademicYear::find($this->academicYearId);

        if (!$student || !$academicYear)
            return;

        // Check if student is registered in this academic year
        $studentClass = $student->getClassForYear($this->academicYearId);
        if (!$studentClass) {
            Notification::make()
                ->warning()
                ->title('Siswa tidak terdaftar')
                ->body('Siswa tidak terdaftar di tahun ajaran ini.')
                ->send();
            $this->monthsData = [];
            return;
        }

        $this->monthsData = $this->generate12Months($academicYear, $student);
    }

    protected function generate12Months($academicYear, $student): array
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
            ];

            $currentMonth++;
        }

        return $months;
    }

    protected function getMonthsTableHtml(): HtmlString
    {
        if (empty($this->monthsData)) {
            return new HtmlString('<p style="color: #6b7280;">Pilih siswa untuk melihat status pembayaran SPP</p>');
        }

        $sppRate = SppRate::where('academic_year_id', $this->academicYearId)->first();

        $html = '<div style="overflow-x: auto; border-radius: 0.5rem; border: 1px solid #e5e7eb; margin-top: 1rem;">';
        $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">';
        $html .= '<thead style="background-color: #f9fafb;">';
        $html .= '<tr>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">No</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Bulan</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Status</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Aksi</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($this->monthsData as $index => $month) {
            $rowBg = $index % 2 == 0 ? 'background-color: white;' : 'background-color: #f9fafb;';
            $html .= '<tr style="' . $rowBg . '">';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">' . ($index + 1) . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; font-weight: 500;">' . $month['month_name'] . '</td>';

            if ($month['is_paid']) {
                $html .= '<td style="padding: 0.75rem 1rem; text-align: center; border-bottom: 1px solid #e5e7eb;"><span style="padding: 0.25rem 0.75rem; background-color: #d1fae5; color: #065f46; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Lunas</span></td>';
                $html .= '<td style="padding: 0.75rem 1rem; text-align: center; border-bottom: 1px solid #e5e7eb; color: #9ca3af;">-</td>';
            } else {
                $html .= '<td style="padding: 0.75rem 1rem; text-align: center; border-bottom: 1px solid #e5e7eb;"><span style="padding: 0.25rem 0.75rem; background-color: #fee2e2; color: #991b1b; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Belum</span></td>';
                $html .= '<td style="padding: 0.75rem 1rem; text-align: center; border-bottom: 1px solid #e5e7eb;">';
                $html .= '<button wire:click="paySppMonth(' . $month['month'] . ', ' . $month['year'] . ')" type="button" style="padding: 0.375rem 0.75rem; background-color: #3b82f6; color: white; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 500; cursor: pointer; border: none;">Terima (Rp ' . number_format($sppRate?->amount ?? 0, 0, ',', '.') . ')</button>';
                $html .= '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return new HtmlString($html);
    }

    public function paySppMonth($month, $year): void
    {
        if (!$this->accountId) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Pilih akun kas terlebih dahulu')
                ->send();
            return;
        }

        $sppRate = SppRate::where('academic_year_id', $this->academicYearId)->first();

        if (!$sppRate) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Tarif SPP belum diset untuk tahun ajaran ini')
                ->send();
            return;
        }

        // Generate receipt number
        $lastPayment = Payment::latest('id')->first();
        $receiptNumber = 'PMK-' . date('Ymd') . '-' . str_pad(($lastPayment?->id ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        Payment::create([
            'receipt_number' => $receiptNumber,
            'student_id' => $this->studentId,
            'fee_type_id' => $this->feeTypeId,
            'account_id' => $this->accountId,
            'academic_year_id' => $this->academicYearId,
            'payment_date' => now(),
            'month' => $month,
            'year' => $year,
            'amount' => $sppRate->amount,
            'payment_method' => 'cash',
            'created_by' => auth()->id(),
        ]);

        Notification::make()
            ->success()
            ->title('Berhasil')
            ->body('SPP berhasil diterima')
            ->send();

        $this->loadSppMonths();
    }

    public function saveGeneralIncome(): void
    {
        $validated = $this->form->getState();

        if (!$this->accountId) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Pilih akun kas terlebih dahulu')
                ->send();
            return;
        }

        $lastPayment = Payment::latest('id')->first();
        $receiptNumber = 'PMK-' . date('Ymd') . '-' . str_pad(($lastPayment?->id ?? 0) + 1, 4, '0', STR_PAD_LEFT);

        Payment::create([
            'receipt_number' => $receiptNumber,
            'student_id' => $validated['student_id_general'],
            'fee_type_id' => $this->feeTypeId,
            'account_id' => $this->accountId,
            'academic_year_id' => $validated['academic_year_id_general'],
            'payment_date' => $validated['payment_date'],
            'month' => null,
            'year' => null,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        Notification::make()
            ->success()
            ->title('Berhasil')
            ->body('Pemasukan berhasil disimpan')
            ->send();

        $this->form->fill();
        $this->feeTypeId = null;
        $this->studentId = null;
    }
}
