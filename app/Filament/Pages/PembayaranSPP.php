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
use App\Models\Student;
use App\Models\Payment;
use App\Models\FeeType;
use App\Models\Account;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;
use UnitEnum;
use BackedEnum;

class PembayaranSPP extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected string $view = 'filament.pages.pembayaran-spp';

    protected static ?string $navigationLabel = 'Pembayaran SPP';

    protected static ?string $title = 'Pembayaran SPP';

    protected static UnitEnum|string|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];
    public $studentId = null;
    public $studentInfo = null;
    public $unpaidMonths = [];
    public $paymentHistory = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pilih Siswa')
                    ->components([
                        Select::make('student_id')
                            ->label('Siswa')
                            ->options(Student::where('status', 'active')->get()->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->studentId = $state;
                                $this->loadStudentInfo();
                            }),
                    ]),

                Section::make('Informasi Tunggakan')
                    ->components([
                        Placeholder::make('info')
                            ->label('')
                            ->content(fn() => $this->getStudentInfoHtml()),
                    ])
                    ->visible(fn() => $this->studentId !== null),

                Section::make('Daftar Pembayaran per Bulan')
                    ->description('Status pembayaran SPP per bulan')
                    ->components([
                        Placeholder::make('payment_list')
                            ->label('')
                            ->content(fn() => $this->getPaymentListHtml()),
                    ])
                    ->visible(fn() => $this->studentId !== null),

                Section::make('Form Pembayaran')
                    ->components([
                        Grid::make(2)
                            ->components([
                                Select::make('account_id')
                                    ->label('Akun Pembayaran')
                                    ->options(Account::all()->pluck('name', 'id'))
                                    ->required()
                                    ->native(false),

                                Select::make('payment_method')
                                    ->label('Metode Pembayaran')
                                    ->options([
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer',
                                        'check' => 'Cek',
                                    ])
                                    ->default('cash')
                                    ->required()
                                    ->native(false),

                                DatePicker::make('payment_date')
                                    ->label('Tanggal Pembayaran')
                                    ->default(now())
                                    ->required()
                                    ->native(false),

                                TextInput::make('months_to_pay')
                                    ->label('Jumlah Bulan Dibayar')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(fn() => count($this->unpaidMonths))
                                    ->default(1)
                                    ->required()
                                    ->suffix('bulan')
                                    ->helperText(fn() => 'Maksimal: ' . count($this->unpaidMonths) . ' bulan'),

                                TextInput::make('amount_per_month')
                                    ->label('Nominal per Bulan')
                                    ->numeric()
                                    ->default(150000)
                                    ->required()
                                    ->prefix('Rp')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $months = $get('months_to_pay') ?? 1;
                                        $set('total_amount', $state * $months);
                                    }),

                                TextInput::make('total_amount')
                                    ->label('Total Pembayaran')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('Rp')
                                    ->dehydrated(false),

                                Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->visible(fn() => $this->studentId !== null && count($this->unpaidMonths) > 0),
            ])
            ->statePath('data');
    }

    protected function loadStudentInfo()
    {
        if (!$this->studentId) {
            $this->studentInfo = null;
            $this->unpaidMonths = [];
            return;
        }

        $student = Student::with('class')->find($this->studentId);
        if (!$student) {
            return;
        }

        $enrollmentDate = Carbon::parse($student->enrollment_date);
        $now = Carbon::now();

        // Calculate all months from enrollment to now
        $allMonths = [];
        $current = $enrollmentDate->copy();

        while ($current->lte($now)) {
            $allMonths[] = [
                'year' => $current->year,
                'month' => $current->month,
                'month_name' => $current->format('F Y'),
            ];
            $current->addMonth();
        }

        // Get paid months with payment details
        $payments = Payment::where('student_id', $student->id)
            ->whereHas('feeType', function ($query) {
                $query->where('name', 'LIKE', '%SPP%');
            })
            ->get();

        $paidMonths = $payments->map(function ($payment) {
            return $payment->year . '-' . str_pad($payment->month, 2, '0', STR_PAD_LEFT);
        })->toArray();

        // Build payment history with all months
        $this->paymentHistory = collect($allMonths)->map(function ($month) use ($payments) {
            $key = $month['year'] . '-' . str_pad($month['month'], 2, '0', STR_PAD_LEFT);
            $payment = $payments->first(function ($p) use ($month) {
                return $p->year == $month['year'] && $p->month == $month['month'];
            });

            $monthNameIndo = [
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

            return [
                'month_year' => $monthNameIndo[$month['month']] . ' ' . $month['year'],
                'status' => $payment ? 'Sudah Bayar' : 'Belum Bayar',
                'receipt_number' => $payment ? $payment->receipt_number : '-',
                'amount' => $payment ? $payment->amount : null,
                'payment_date' => $payment ? Carbon::parse($payment->payment_date)->format('d/m/Y') : null,
                'is_paid' => $payment !== null,
            ];
        })->toArray();

        // Filter unpaid months
        $this->unpaidMonths = collect($allMonths)->filter(function ($month) use ($paidMonths) {
            $key = $month['year'] . '-' . str_pad($month['month'], 2, '0', STR_PAD_LEFT);
            return !in_array($key, $paidMonths);
        })->values()->toArray();

        $this->studentInfo = [
            'student' => $student,
            'total_months_should_pay' => count($allMonths),
            'total_paid' => count($allMonths) - count($this->unpaidMonths),
            'total_unpaid' => count($this->unpaidMonths),
            'unpaid_months' => $this->unpaidMonths,
        ];
    }

    protected function getStudentInfoHtml(): HtmlString
    {
        if (!$this->studentInfo) {
            return new HtmlString('');
        }

        $info = $this->studentInfo;
        $student = $info['student'];

        $html = '<div style="display: flex; flex-direction: column; gap: 0.75rem;">';
        $html .= '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">';
        $html .= '<div><strong style="font-size: 0.75rem; color: #6b7280;">NIS:</strong> <span style="font-weight: 500;">' . $student->nis . '</span></div>';
        $html .= '<div><strong style="font-size: 0.75rem; color: #6b7280;">Kelas:</strong> <span style="font-weight: 500;">' . ($student->class->name ?? '-') . '</span></div>';
        $html .= '<div><strong style="font-size: 0.75rem; color: #6b7280;">Tanggal Masuk:</strong> <span style="font-weight: 500;">' . Carbon::parse($student->enrollment_date)->format('d M Y') . '</span></div>';
        $html .= '</div>';

        $html .= '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 0.5rem;">';
        $html .= '<div style="padding: 0.75rem; background-color: #eff6ff; border-radius: 0.5rem; border: 1px solid #bfdbfe;"><strong style="font-size: 0.75rem; color: #1e40af;">Harus Bayar:</strong> <span style="font-weight: 700; display: block; margin-top: 0.25rem; font-size: 1.125rem; color: #1e3a8a;">' . $info['total_months_should_pay'] . ' bulan</span></div>';
        $html .= '<div style="padding: 0.75rem; background-color: #f0fdf4; border-radius: 0.5rem; border: 1px solid #bbf7d0;"><strong style="font-size: 0.75rem; color: #15803d;">Sudah Bayar:</strong> <span style="font-weight: 700; display: block; margin-top: 0.25rem; font-size: 1.125rem; color: #166534;">' . $info['total_paid'] . ' bulan</span></div>';
        $html .= '<div style="padding: 0.75rem; background-color: #fef2f2; border-radius: 0.5rem; border: 1px solid #fecaca;"><strong style="font-size: 0.75rem; color: #b91c1c;">Tunggakan:</strong> <span style="font-weight: 700; display: block; margin-top: 0.25rem; font-size: 1.125rem; color: #dc2626;">' . $info['total_unpaid'] . ' bulan</span></div>';
        $html .= '</div>';

        if (count($this->unpaidMonths) > 0) {
            $html .= '<div style="margin-top: 0.5rem; padding: 0.75rem; background-color: #fef3c7; border-radius: 0.5rem; border: 1px solid #fde68a;">';
            $html .= '<strong style="font-size: 0.75rem; color: #92400e;">Bulan yang belum dibayar (dari terlama):</strong><br>';
            $html .= '<div style="font-size: 0.875rem; margin-top: 0.5rem; color: #451a03;">';
            $firstFive = array_slice($this->unpaidMonths, 0, 5);
            $monthNames = array_map(fn($m) => $m['month_name'], $firstFive);
            $html .= implode(', ', $monthNames);
            if (count($this->unpaidMonths) > 5) {
                $html .= ', ... <strong>(' . (count($this->unpaidMonths) - 5) . ' bulan lagi)</strong>';
            }
            $html .= '</div></div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }

    protected function getPaymentListHtml(): HtmlString
    {
        if (empty($this->paymentHistory)) {
            return new HtmlString('<p style="font-size: 0.875rem; color: #6b7280;">Tidak ada data pembayaran</p>');
        }

        $html = '<div style="overflow-x: auto; border-radius: 0.5rem; border: 1px solid #e5e7eb;">';
        $html .= '<table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">';
        $html .= '<thead style="background-color: #f9fafb;">';
        $html .= '<tr>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">No</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Bulan</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Status</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Tanggal Bayar</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: right; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Nominal</th>';
        $html .= '<th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-bottom: 1px solid #e5e7eb;">No. Struk</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($this->paymentHistory as $index => $payment) {
            $rowBg = $payment['is_paid']
                ? 'background-color: #f0fdf4;'
                : 'background-color: #fef2f2;';

            $statusBadge = $payment['is_paid']
                ? '<span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: #dcfce7; color: #166534;">✓ Sudah Bayar</span>'
                : '<span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: #fee2e2; color: #991b1b;">✗ Belum Bayar</span>';

            $borderBottom = ($index < count($this->paymentHistory) - 1) ? 'border-bottom: 1px solid #e5e7eb;' : '';

            $html .= '<tr style="' . $rowBg . ' ' . $borderBottom . '">';
            $html .= '<td style="padding: 0.75rem 1rem;">' . ($index + 1) . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; font-weight: 500;">' . $payment['month_year'] . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem;">' . $statusBadge . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem;">' . ($payment['payment_date'] ?? '-') . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; text-align: right; font-weight: 500;">' . ($payment['amount'] ? 'Rp ' . number_format($payment['amount'], 0, ',', '.') : '-') . '</td>';
            $html .= '<td style="padding: 0.75rem 1rem; font-family: monospace; font-size: 0.75rem; color: #6b7280;">' . $payment['receipt_number'] . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return new HtmlString($html);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        if (!$this->studentId || count($this->unpaidMonths) === 0) {
            Notification::make()
                ->title('Error')
                ->body('Tidak ada tunggakan untuk siswa ini')
                ->danger()
                ->send();
            return;
        }

        try {
            // Get SPP fee type
            $sppFeeType = FeeType::where('name', 'LIKE', '%SPP%')->first();
            if (!$sppFeeType) {
                throw new \Exception('Fee Type SPP tidak ditemukan');
            }

            // Get active academic year
            $academicYear = AcademicYear::where('is_active', true)->first();
            if (!$academicYear) {
                $academicYear = AcademicYear::first();
            }

            $monthsToPay = (int) $data['months_to_pay'];
            $amountPerMonth = (float) $data['amount_per_month'];

            // Create payments for the oldest unpaid months (FIFO)
            $monthsToProcess = array_slice($this->unpaidMonths, 0, $monthsToPay);

            foreach ($monthsToProcess as $month) {
                Payment::create([
                    'student_id' => $this->studentId,
                    'fee_type_id' => $sppFeeType->id,
                    'account_id' => $data['account_id'],
                    'academic_year_id' => $academicYear->id,
                    'payment_date' => $data['payment_date'],
                    'month' => $month['month'],
                    'year' => $month['year'],
                    'amount' => $amountPerMonth,
                    'payment_method' => $data['payment_method'],
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }

            Notification::make()
                ->title('Berhasil')
                ->body('Pembayaran SPP sebanyak ' . $monthsToPay . ' bulan berhasil disimpan')
                ->success()
                ->send();

            // Reload student info to refresh payment list
            $this->loadStudentInfo();

            // Reset only the form data
            $this->form->fill([]);
            $this->data['months_to_pay'] = 1;
            $this->data['account_id'] = null;
            $this->data['payment_method'] = 'cash';
            $this->data['payment_date'] = now();
            $this->data['amount_per_month'] = 150000;
            $this->data['notes'] = null;

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Gagal menyimpan pembayaran: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
