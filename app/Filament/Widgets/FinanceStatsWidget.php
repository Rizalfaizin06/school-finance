<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Expense;
use App\Models\Account;
use App\Models\Student;
use App\Models\AcademicYear;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        // Total saldo kas
        $totalBalance = Account::where('is_active', true)->sum('balance');

        // Pemasukan bulan ini
        $monthlyIncome = Payment::whereMonth('payment_date', '=', now()->month)
            ->whereYear('payment_date', '=', now()->year)
            ->sum('amount');

        // Pengeluaran bulan ini
        $monthlyExpense = Expense::whereMonth('expense_date', '=', now()->month)
            ->whereYear('expense_date', '=', now()->year)
            ->sum('amount');

        // Siswa belum bayar SPP bulan ini
        $totalStudents = Student::where('status', 'active')->count();
        $paidStudentsThisMonth = Payment::whereMonth('payment_date', '=', now()->month)
            ->whereYear('payment_date', '=', now()->year)
            ->whereHas('feeType', fn($q) => $q->where('category', 'spp'))
            ->distinct('student_id')
            ->count('student_id');
        $unpaidStudents = $totalStudents - $paidStudentsThisMonth;

        return [
            Stat::make('Saldo Kas', 'Rp ' . number_format($totalBalance, 0, ',', '.'))
                ->description('Total saldo semua akun')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($monthlyIncome, 0, ',', '.'))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success'),

            Stat::make('Pengeluaran Bulan Ini', 'Rp ' . number_format($monthlyExpense, 0, ',', '.'))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color('danger'),

            Stat::make('Siswa Belum Bayar SPP', $unpaidStudents)
                ->description('Dari total ' . $totalStudents . ' siswa aktif')
                ->descriptionIcon('heroicon-o-users')
                ->color($unpaidStudents > 0 ? 'warning' : 'success'),
        ];
    }
}
