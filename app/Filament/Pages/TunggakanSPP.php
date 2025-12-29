<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\Student;
use App\Models\Payment;
use App\Models\StudentClass;
use App\Models\AcademicYear;
use App\Models\FeeType;
use Carbon\Carbon;
use UnitEnum;
use BackedEnum;

class TunggakanSPP extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected string $view = 'filament.pages.tunggakan-spp';

    protected static ?string $navigationLabel = 'Tunggakan SPP';

    protected static ?string $title = 'Daftar Tunggakan SPP';

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    public function table(Table $table): Table
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        return $table
            ->query(
                Student::query()
                    ->where('status', 'active')
                    ->whereHas('studentClasses', function ($query) use ($activeYear) {
                        if ($activeYear) {
                            $query->where('academic_year_id', $activeYear->id);
                        }
                    })
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

                Tables\Columns\TextColumn::make('current_class')
                    ->label('Kelas')
                    ->state(function (Student $record) use ($activeYear): string {
                        if (!$activeYear)
                            return '-';
                        $studentClass = $record->getClassForYear($activeYear->id);
                        return $studentClass?->classRoom->name ?? '-';
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('academic_year')
                    ->label('Tahun Ajaran')
                    ->state(function () use ($activeYear): string {
                        return $activeYear?->name ?? '-';
                    })
                    ->sortable(false),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Sudah Lunas')
                    ->state(function (Student $record) use ($activeYear): string {
                        if (!$activeYear)
                            return '0 / 12 bulan';

                        $sppFeeType = FeeType::where('name', 'LIKE', '%SPP%')->first();

                        $paidCount = Payment::where('student_id', $record->id)
                            ->where('academic_year_id', $activeYear->id)
                            ->where('fee_type_id', $sppFeeType?->id)
                            ->count();

                        return $paidCount . ' / 12 bulan';
                    })
                    ->color('success')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('outstanding')
                    ->label('Belum Diterima')
                    ->state(function (Student $record) use ($activeYear): string {
                        if (!$activeYear)
                            return '12 bulan';

                        $sppFeeType = FeeType::where('name', 'LIKE', '%SPP%')->first();

                        $paidCount = Payment::where('student_id', $record->id)
                            ->where('academic_year_id', $activeYear->id)
                            ->where('fee_type_id', $sppFeeType?->id)
                            ->count();

                        $outstanding = 12 - $paidCount;

                        return $outstanding . ' bulan';
                    })
                    ->color(fn(Student $record) => $this->getOutstandingColor($record))
                    ->weight('bold')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('current_month_status')
                    ->label('Bulan ' . Carbon::now()->format('F'))
                    ->state(function (Student $record) use ($activeYear, $currentMonth, $currentYear): string {
                        if (!$activeYear)
                            return 'Belum Bayar';

                        $sppFeeType = FeeType::where('name', 'LIKE', '%SPP%')->first();

                        $paid = Payment::where('student_id', $record->id)
                            ->where('academic_year_id', $activeYear->id)
                            ->where('fee_type_id', $sppFeeType?->id)
                            ->where('month', $currentMonth)
                            ->where('year', $currentYear)
                            ->exists();

                        return $paid ? 'Lunas' : 'Belum Bayar';
                    })
                    ->badge()
                    ->color(function (Student $record) use ($activeYear, $currentMonth, $currentYear): string {
                        if (!$activeYear)
                            return 'danger';

                        $sppFeeType = FeeType::where('name', 'LIKE', '%SPP%')->first();

                        $paid = Payment::where('student_id', $record->id)
                            ->where('academic_year_id', $activeYear->id)
                            ->where('fee_type_id', $sppFeeType?->id)
                            ->where('month', $currentMonth)
                            ->where('year', $currentYear)
                            ->exists();

                        return $paid ? 'success' : 'danger';
                    }),

                Tables\Columns\TextColumn::make('parent_name')
                    ->label('Nama Orang Tua')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('parent_phone')
                    ->label('No. HP Orang Tua')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('academic_year')
                    ->label('Tahun Ajaran')
                    ->options(AcademicYear::orderBy('name', 'desc')->pluck('name', 'id'))
                    ->default(fn() => AcademicYear::where('is_active', true)->first()?->id)
                    ->query(function ($query, $data) {
                        if (!isset($data['value']))
                            return $query;

                        return $query->whereHas('studentClasses', function ($q) use ($data) {
                            $q->where('academic_year_id', $data['value']);
                        });
                    }),

                Tables\Filters\SelectFilter::make('class')
                    ->label('Kelas')
                    ->options(function () {
                        $activeYear = AcademicYear::where('is_active', true)->first();
                        if (!$activeYear)
                            return [];

                        return StudentClass::where('academic_year_id', $activeYear->id)
                            ->with('classRoom')
                            ->get()
                            ->pluck('classRoom.name', 'class_id')
                            ->unique();
                    })
                    ->query(function ($query, $data) {
                        if (!isset($data['value']))
                            return $query;

                        $activeYear = AcademicYear::where('is_active', true)->first();
                        if (!$activeYear)
                            return $query;

                        return $query->whereHas('studentClasses', function ($q) use ($data, $activeYear) {
                            $q->where('class_id', $data['value'])
                                ->where('academic_year_id', $activeYear->id);
                        });
                    }),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status Pembayaran Bulan Ini')
                    ->options([
                        'paid' => 'Sudah Bayar',
                        'unpaid' => 'Belum Bayar',
                    ])
                    ->query(function ($query, $data) use ($activeYear, $currentMonth, $currentYear) {
                        if (!isset($data['value']) || !$activeYear)
                            return $query;

                        $sppFeeType = FeeType::where('name', 'LIKE', '%SPP%')->first();

                        $paidStudentIds = Payment::where('academic_year_id', $activeYear->id)
                            ->where('fee_type_id', $sppFeeType?->id)
                            ->where('month', $currentMonth)
                            ->where('year', $currentYear)
                            ->pluck('student_id');

                        if ($data['value'] === 'paid') {
                            return $query->whereIn('id', $paidStudentIds);
                        } else {
                            return $query->whereNotIn('id', $paidStudentIds);
                        }
                    }),
            ])
            ->heading('Laporan Tunggakan SPP - ' . ($activeYear?->name ?? 'Tahun Ajaran Aktif'))
            ->description('Daftar pembayaran SPP siswa aktif untuk tahun ajaran ' . ($activeYear?->name ?? '-'))
            ->defaultSort('name');
    }

    protected function getOutstandingColor(Student $record): string
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear)
            return 'danger';

        $sppFeeType = FeeType::where('name', 'LIKE', '%SPP%')->first();

        $paidCount = Payment::where('student_id', $record->id)
            ->where('academic_year_id', $activeYear->id)
            ->where('fee_type_id', $sppFeeType?->id)
            ->count();

        $outstanding = 12 - $paidCount;

        if ($outstanding == 0)
            return 'success';
        if ($outstanding <= 2)
            return 'warning';
        return 'danger';
    }
}
