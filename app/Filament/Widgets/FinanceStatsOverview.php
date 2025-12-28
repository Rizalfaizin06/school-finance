<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\Student;
use App\Filament\Pages\TunggakanSPP;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class FinanceStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Total Saldo Kas
        $totalBalance = Account::sum('balance');

        // Pemasukan Bulan Ini
        $monthlyIncome = Payment::whereMonth('payment_date', $currentMonth)
            ->whereYear('payment_date', $currentYear)
            ->sum('amount');

        // Pengeluaran Bulan Ini
        $monthlyExpense = Expense::whereMonth('expense_date', $currentMonth)
            ->whereYear('expense_date', $currentYear)
            ->sum('amount');

        // Tunggakan SPP (siswa aktif yang belum bayar bulan ini)
        $activeStudents = Student::where('status', 'active')->count();
        $paidStudents = Payment::whereMonth('payment_date', $currentMonth)
            ->whereYear('payment_date', $currentYear)
            ->distinct('student_id')
            ->count('student_id');
        $outstandingCount = $activeStudents - $paidStudents;

        return [
            Stat::make('Saldo Kas', 'Rp ' . number_format($totalBalance, 0, ',', '.'))
                ->description('Total saldo di semua akun')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($monthlyIncome, 0, ',', '.'))
                ->description(Carbon::now()->format('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Pengeluaran Bulan Ini', 'Rp ' . number_format($monthlyExpense, 0, ',', '.'))
                ->description(Carbon::now()->format('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Tunggakan SPP', $outstandingCount . ' Siswa')
                ->description('Belum bayar bulan ini')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->url(TunggakanSPP::getUrl()),
        ];
    }
}
