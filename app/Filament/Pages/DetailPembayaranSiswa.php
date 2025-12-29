<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Student;
use App\Models\Payment;
use App\Models\FeeType;
use App\Models\Account;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Http\Request;
use UnitEnum;
use BackedEnum;

class DetailPembayaranSiswa extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.detail-pembayaran-siswa';

    protected static ?string $navigationLabel = 'Detail Pembayaran Siswa';

    protected static ?string $title = 'Detail Pembayaran SPP Siswa';

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(Student::query()->where('status', 'active')->with(['class']))
            ->columns([
                Tables\Columns\TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('class.name')
                    ->label('Kelas')
                    ->sortable(),

                Tables\Columns\TextColumn::make('enrollment_date')
                    ->label('Tanggal Masuk')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_progress')
                    ->label('Progress Pembayaran')
                    ->state(function (Student $record): string {
                        $enrollmentDate = Carbon::parse($record->enrollment_date);
                        $now = Carbon::now();
                        $monthsDiff = $enrollmentDate->diffInMonths($now) + 1;
                        $totalMonths = min($monthsDiff, 72);

                        $paidCount = Payment::where('student_id', $record->id)
                            ->whereHas('feeType', function ($query) {
                                $query->where('name', 'LIKE', '%SPP%');
                            })
                            ->count();

                        return $paidCount . ' / ' . $totalMonths . ' bulan';
                    })
                    ->badge()
                    ->color(fn(Student $record): string => $this->getProgressColor($record)),

                Tables\Columns\TextColumn::make('outstanding_amount')
                    ->label('Tunggakan')
                    ->state(function (Student $record): string {
                        $enrollmentDate = Carbon::parse($record->enrollment_date);
                        $now = Carbon::now();
                        $monthsDiff = $enrollmentDate->diffInMonths($now) + 1;
                        $totalMonths = min($monthsDiff, 72);

                        $paidCount = Payment::where('student_id', $record->id)
                            ->whereHas('feeType', function ($query) {
                                $query->where('name', 'LIKE', '%SPP%');
                            })
                            ->count();

                        $outstanding = $totalMonths - $paidCount;
                        return $outstanding . ' bulan';
                    })
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->relationship('class', 'name'),
            ])
            ->actions([
                Action::make('view_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn(Student $record): string => 'Detail Pembayaran SPP - ' . $record->name)
                    ->modalContent(fn(Student $record) => view('filament.pages.components.payment-detail-table', [
                        'student' => $record,
                        'paymentDetails' => $this->generatePaymentDetails($record),
                    ]))
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->defaultSort('name');
    }

    protected function getProgressColor(Student $record): string
    {
        $enrollmentDate = Carbon::parse($record->enrollment_date);
        $now = Carbon::now();
        $monthsDiff = $enrollmentDate->diffInMonths($now) + 1;
        $totalMonths = min($monthsDiff, 72);

        $paidCount = Payment::where('student_id', $record->id)
            ->whereHas('feeType', function ($query) {
                $query->where('name', 'LIKE', '%SPP%');
            })
            ->count();

        $percentage = $totalMonths > 0 ? ($paidCount / $totalMonths) * 100 : 0;

        if ($percentage >= 90)
            return 'success';
        if ($percentage >= 70)
            return 'info';
        if ($percentage >= 50)
            return 'warning';
        return 'danger';
    }

    protected function generatePaymentDetails(Student $student): array
    {
        $enrollmentDate = Carbon::parse($student->enrollment_date);
        $details = [];

        // Get all SPP payments for this student
        $payments = Payment::where('student_id', $student->id)
            ->whereHas('feeType', function ($query) {
                $query->where('name', 'LIKE', '%SPP%');
            })
            ->get()
            ->keyBy(function ($payment) {
                return $payment->year . '-' . str_pad($payment->month, 2, '0', STR_PAD_LEFT);
            });

        // Generate 72 months data
        for ($i = 0; $i < 72; $i++) {
            $monthDate = $enrollmentDate->copy()->addMonths($i);
            $monthKey = $monthDate->format('Y-m');

            $isPaid = $payments->has($monthKey);
            $payment = $isPaid ? $payments->get($monthKey) : null;

            // Check if this month has passed
            $isPast = $monthDate->lte(Carbon::now());

            $details[] = [
                'no' => $i + 1,
                'month_name' => $monthDate->format('F Y'),
                'month_short' => $monthDate->format('M Y'),
                'year' => $monthDate->year,
                'month' => $monthDate->month,
                'is_paid' => $isPaid,
                'is_past' => $isPast,
                'should_pay' => $isPast, // Should pay if the month has passed
                'payment_date' => $isPaid ? $payment->payment_date->format('d M Y') : null,
                'receipt_number' => $isPaid ? $payment->receipt_number : null,
                'amount' => $isPaid ? 'Rp ' . number_format($payment->amount, 0, ',', '.') : null,
            ];
        }

        return $details;
    }

    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'account_id' => 'required|exists:accounts,id',
            'payment_method' => 'required|in:cash,transfer,check',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'notes' => 'nullable|string',
        ]);

        try {
            // Get SPP fee type
            $sppFeeType = FeeType::where('name', 'LIKE', '%SPP%')->first();
            if (!$sppFeeType) {
                Notification::make()
                    ->title('Error')
                    ->body('Fee Type SPP tidak ditemukan')
                    ->danger()
                    ->send();
                return redirect()->back();
            }

            // Get active academic year
            $academicYear = AcademicYear::where('is_active', true)->first();
            if (!$academicYear) {
                $academicYear = AcademicYear::first();
            }

            // Create payment
            Payment::create([
                'student_id' => $validated['student_id'],
                'fee_type_id' => $sppFeeType->id,
                'account_id' => $validated['account_id'],
                'academic_year_id' => $academicYear->id,
                'payment_date' => $validated['payment_date'],
                'month' => $validated['month'],
                'year' => $validated['year'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'],
                'created_by' => auth()->id(),
            ]);

            Notification::make()
                ->title('Berhasil')
                ->body('Pembayaran SPP berhasil disimpan')
                ->success()
                ->send();

            return redirect()->back();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Gagal menyimpan pembayaran: ' . $e->getMessage())
                ->danger()
                ->send();

            return redirect()->back();
        }
    }
}
