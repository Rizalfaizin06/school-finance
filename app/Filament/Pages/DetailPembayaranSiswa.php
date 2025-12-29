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
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\Student;
use App\Models\Payment;
use App\Models\AcademicYear;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;
use UnitEnum;
use BackedEnum;

class DetailPembayaranSiswa extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.detail-pembayaran-siswa';

    protected static ?string $navigationLabel = 'Detail Pemasukan Siswa';

    protected static ?string $title = 'Detail Pemasukan dari Siswa';

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];
    public $studentId = null;
    public $academicYearId = null;

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
                Section::make('Filter Pemasukan')
                    ->components([
                        Grid::make(2)
                            ->components([
                                Select::make('student_id')
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
                                        $this->studentId = $state;
                                    }),

                                Select::make('academic_year_id')
                                    ->label('Tahun Ajaran')
                                    ->options(AcademicYear::orderBy('name', 'desc')->pluck('name', 'id'))
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state) {
                                        $this->academicYearId = $state;
                                    }),
                            ]),
                    ]),

                Section::make('Ringkasan')
                    ->components([
                        Placeholder::make('summary')
                            ->label('')
                            ->content(fn() => $this->getSummaryHtml()),
                    ])
                    ->visible(fn() => $this->studentId !== null && $this->academicYearId !== null),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->when($this->studentId, fn($query) => $query->where('student_id', $this->studentId))
                    ->when($this->academicYearId, fn($query) => $query->where('academic_year_id', $this->academicYearId))
                    ->with(['student', 'feeType', 'account', 'academicYear'])
                    ->orderBy('payment_date', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('No. Kwitansi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nomor kwitansi disalin')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal Bayar')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('feeType.name')
                    ->label('Jenis Pemasukan')
                    ->badge()
                    ->color(fn(Payment $record): string => match (true) {
                        str_contains(strtolower($record->feeType->name), 'spp') => 'success',
                        str_contains(strtolower($record->feeType->name), 'seragam') => 'info',
                        str_contains(strtolower($record->feeType->name), 'buku') => 'warning',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('period')
                    ->label('Periode')
                    ->getStateUsing(function (Payment $record): string {
                        if ($record->month && $record->year) {
                            $monthNames = [
                                1 => 'Jan',
                                2 => 'Feb',
                                3 => 'Mar',
                                4 => 'Apr',
                                5 => 'Mei',
                                6 => 'Jun',
                                7 => 'Jul',
                                8 => 'Ags',
                                9 => 'Sep',
                                10 => 'Okt',
                                11 => 'Nov',
                                12 => 'Des'
                            ];
                            return $monthNames[$record->month] . ' ' . $record->year;
                        }
                        return '-';
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'check' => 'Cek',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Akun')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    })
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fee_type_id')
                    ->label('Jenis Pemasukan')
                    ->relationship('feeType', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Metode Penerimaan')
                    ->options([
                        'cash' => 'Tunai',
                        'transfer' => 'Transfer',
                        'check' => 'Cek',
                    ]),
            ])
            ->heading(fn() => $this->getTableHeading())
            ->emptyStateHeading('Belum Ada Pemasukan')
            ->emptyStateDescription('Pilih siswa dan tahun ajaran untuk melihat riwayat pemasukan')
            ->emptyStateIcon('heroicon-o-document-text')
            ->defaultSort('payment_date', 'desc');
    }

    protected function getTableHeading(): string
    {
        if (!$this->studentId || !$this->academicYearId) {
            return 'Riwayat Pemasukan';
        }

        $student = Student::find($this->studentId);
        $academicYear = AcademicYear::find($this->academicYearId);

        return 'Pemasukan dari ' . ($student?->name ?? 'Siswa') . ' - ' . ($academicYear?->name ?? 'Tahun Ajaran');
    }

    protected function getSummaryHtml(): HtmlString
    {
        if (!$this->studentId || !$this->academicYearId) {
            return new HtmlString('<p style="color: #6b7280;">Pilih siswa dan tahun ajaran</p>');
        }

        $payments = Payment::where('student_id', $this->studentId)
            ->where('academic_year_id', $this->academicYearId)
            ->with('feeType')
            ->get();

        $total = $payments->sum('amount');
        $count = $payments->count();

        // Group by fee type
        $byFeeType = $payments->groupBy('feeType.name')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('amount'),
            ];
        });

        $html = '<div style="display: flex; flex-direction: column; gap: 1rem;">';

        // Total summary
        $html .= '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">';
        $html .= '<div style="padding: 1rem; background-color: #eff6ff; border-radius: 0.5rem; border: 1px solid #bfdbfe;">';
        $html .= '<strong style="font-size: 0.75rem; color: #1e40af;">Total Transaksi:</strong>';
        $html .= '<span style="font-weight: 700; display: block; margin-top: 0.25rem; font-size: 1.5rem; color: #1e3a8a;">' . $count . '</span>';
        $html .= '</div>';
        $html .= '<div style="padding: 1rem; background-color: #f0fdf4; border-radius: 0.5rem; border: 1px solid #bbf7d0;">';
        $html .= '<strong style="font-size: 0.75rem; color: #15803d;">Total Pemasukan:</strong>';
        $html .= '<span style="font-weight: 700; display: block; margin-top: 0.25rem; font-size: 1.5rem; color: #166534;">Rp ' . number_format($total, 0, ',', '.') . '</span>';
        $html .= '</div>';
        $html .= '</div>';

        // By fee type
        if ($byFeeType->isNotEmpty()) {
            $html .= '<div style="padding: 1rem; background-color: #f9fafb; border-radius: 0.5rem; border: 1px solid #e5e7eb;">';
            $html .= '<strong style="font-size: 0.875rem; color: #374151; margin-bottom: 0.5rem; display: block;">Rincian per Jenis:</strong>';
            $html .= '<div style="display: flex; flex-direction: column; gap: 0.5rem;">';

            foreach ($byFeeType as $feeTypeName => $data) {
                $html .= '<div style="display: flex; justify-content: space-between; padding: 0.5rem; background-color: white; border-radius: 0.375rem;">';
                $html .= '<span style="font-size: 0.875rem; color: #4b5563;">' . $feeTypeName . ' (' . $data['count'] . 'x)</span>';
                $html .= '<span style="font-size: 0.875rem; font-weight: 600; color: #059669;">Rp ' . number_format($data['total'], 0, ',', '.') . '</span>';
                $html .= '</div>';
            }

            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }
}
