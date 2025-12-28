<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class MonthlyIncomeChart extends ChartWidget
{
    protected ?string $heading = 'Pemasukan & Pengeluaran (6 Bulan Terakhir)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $months = collect();
        $incomeData = [];
        $expenseData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push($date->format('M Y'));

            $incomeData[] = Payment::whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount');

            $expenseData[] = Expense::whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)
                ->sum('amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $incomeData,
                    'backgroundColor' => '#10b981',
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $expenseData,
                    'backgroundColor' => '#ef4444',
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
