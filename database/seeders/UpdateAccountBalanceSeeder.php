<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Expense;

class UpdateAccountBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Updating account balances...');

        $accounts = Account::all();

        foreach ($accounts as $account) {
            $totalIncome = Payment::where('account_id', $account->id)->sum('amount');
            $totalExpense = Expense::where('account_id', $account->id)->sum('amount');

            $newBalance = $account->opening_balance + $totalIncome - $totalExpense;

            $account->update(['balance' => $newBalance]);

            $this->command->info(
                $account->name . ': Rp ' . number_format($newBalance, 0, ',', '.') .
                ' (Income: Rp ' . number_format($totalIncome, 0, ',', '.') .
                ', Expense: Rp ' . number_format($totalExpense, 0, ',', '.') . ')'
            );
        }

        $this->command->info('Account balances updated successfully!');
    }
}
