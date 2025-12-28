<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPaymentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->with(['student', 'feeType', 'account'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('No. Kwitansi')
                    ->searchable(),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Siswa')
                    ->searchable(),

                Tables\Columns\TextColumn::make('feeType.name')
                    ->label('Jenis Pembayaran')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'SPP' => 'success',
                        default => 'info',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge(),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    protected function getTableHeading(): string
    {
        return 'Pembayaran Terakhir';
    }
}
