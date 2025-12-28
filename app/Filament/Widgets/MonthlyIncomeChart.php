<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MonthlyIncomeChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Pemasukan & Pengeluaran (6 Bulan Terakhir)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(function ($monthsBack) {
            return now()->subMonths($monthsBack);
        });

        $incomeData = $months->map(function ($date) {
            return Payment::whereYear('payment_date', '=', $date->year)
                ->whereMonth('payment_date', '=', $date->month)
                ->sum('amount');
        })->toArray();

        $expenseData = $months->map(function ($date) {
            return Expense::whereYear('expense_date', '=', $date->year)
                ->whereMonth('expense_date', '=', $date->month)
                ->sum('amount');
        })->toArray();

        $labels = $months->map(function ($date) {
            return $date->format('M Y');
        })->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $incomeData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $expenseData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
