<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentPayments extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->with(['student', 'feeType', 'account'])
                    ->latest('payment_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('receipt_number')
                    ->label('No. Kwitansi')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('student.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('feeType.name')
                    ->label('Jenis Pembayaran')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('account.name')
                    ->label('Akun')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'success',
                        'transfer' => 'info',
                        default => 'gray',
                    }),
            ])
            ->heading('Pembayaran Terbaru');
    }
}
