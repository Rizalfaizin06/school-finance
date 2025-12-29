<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\Student;
use App\Models\Payment;
use Carbon\Carbon;
use UnitEnum;
use BackedEnum;

class TunggakanSPP extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected string $view = 'filament.pages.tunggakan-spp';

    protected static ?string $navigationLabel = 'Tunggakan SPP';

    protected static ?string $title = 'Daftar Siswa Tunggakan SPP';

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    public function table(Table $table): Table
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Get students who haven't paid this month
        $paidStudentIds = Payment::whereMonth('payment_date', $currentMonth)
            ->whereYear('payment_date', $currentYear)
            ->distinct('student_id')
            ->pluck('student_id');

        return $table
            ->query(
                Student::query()
                    ->where('status', 'active')
                    ->whereNotIn('id', $paidStudentIds)
                    ->with(['class'])
            )
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

                Tables\Columns\TextColumn::make('total_months_should_pay')
                    ->label('Harus Bayar')
                    ->state(function (Student $record): string {
                        // Calculate months from enrollment date to now
                        $enrollmentDate = Carbon::parse($record->enrollment_date);
                        $now = Carbon::now();
                        $monthsDiff = $enrollmentDate->diffInMonths($now) + 1; // +1 to include current month
            
                        // Max 72 months (6 years x 12 months)
                        $totalMonths = min($monthsDiff, 72);

                        return $totalMonths . ' bulan';
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Sudah Bayar')
                    ->state(function (Student $record): string {
                        $paidCount = Payment::where('student_id', $record->id)
                            ->whereHas('feeType', function ($query) {
                                $query->where('name', 'LIKE', '%SPP%');
                            })
                            ->count();

                        return $paidCount . ' bulan';
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('outstanding')
                    ->label('Tunggakan')
                    ->state(function (Student $record): string {
                        // Calculate months from enrollment date to now
                        $enrollmentDate = Carbon::parse($record->enrollment_date);
                        $now = Carbon::now();
                        $monthsDiff = $enrollmentDate->diffInMonths($now) + 1;
                        $totalMonths = min($monthsDiff, 72);

                        // Count paid
                        $paidCount = Payment::where('student_id', $record->id)
                            ->whereHas('feeType', function ($query) {
                            $query->where('name', 'LIKE', '%SPP%');
                        })
                            ->count();

                        $outstanding = $totalMonths - $paidCount;

                        return $outstanding . ' bulan';
                    })
                    ->color('danger')
                    ->weight('bold')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('parent_name')
                    ->label('Nama Orang Tua')
                    ->searchable(),

                Tables\Columns\TextColumn::make('parent_phone')
                    ->label('No. HP Orang Tua')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Kelas')
                    ->relationship('class', 'name'),
            ])
            ->heading('Siswa yang Belum Bayar SPP Bulan ' . Carbon::now()->format('F Y'))
            ->description('Daftar siswa aktif yang belum melakukan pembayaran SPP bulan ini')
            ->defaultSort('class.name');
    }
}
