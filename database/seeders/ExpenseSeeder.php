<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;
use App\Models\Account;
use App\Models\AcademicYear;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding expense transactions...');

        $expenseCategories = ExpenseCategory::all();
        $accounts = Account::all();
        $academicYear = AcademicYear::where('is_active', true)->first();
        $adminUser = User::role('admin')->first();
        $kepsekUser = User::role('kepala_sekolah')->first();

        if ($expenseCategories->isEmpty() || $accounts->isEmpty() || !$academicYear) {
            $this->command->error('Missing required data. Please run other seeders first.');
            return;
        }

        // Delete existing expenses if any
        Expense::query()->delete();

        $expenseTypes = [
            ['name' => 'Gaji Guru', 'amount' => 5000000],
            ['name' => 'Gaji Karyawan', 'amount' => 3000000],
            ['name' => 'Listrik', 'amount' => 800000],
            ['name' => 'Air', 'amount' => 300000],
            ['name' => 'Internet', 'amount' => 500000],
            ['name' => 'ATK', 'amount' => 750000],
            ['name' => 'Kebersihan', 'amount' => 400000],
            ['name' => 'Pemeliharaan Gedung', 'amount' => 2000000],
            ['name' => 'Peralatan Olahraga', 'amount' => 1500000],
            ['name' => 'Buku Perpustakaan', 'amount' => 1200000],
        ];

        $expenseCount = 0;

        for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
            $expenseDate = Carbon::now()->subMonths($monthsAgo);

            // 5-8 expenses per month
            for ($i = 0; $i < rand(5, 8); $i++) {
                $expense = $expenseTypes[array_rand($expenseTypes)];
                $category = $expenseCategories->random();
                // Random account - distribute across all accounts
                $account = $accounts->random();

                Expense::create([
                    'expense_number' => 'EXP/' . $expenseDate->format('Ymd') . '/' . str_pad($expenseCount + 1, 4, '0', STR_PAD_LEFT),
                    'expense_category_id' => $category->id,
                    'academic_year_id' => $academicYear->id,
                    'account_id' => $account->id,
                    'amount' => $expense['amount'] + rand(-100000, 100000),
                    'expense_date' => $expenseDate->day(rand(1, 28))->format('Y-m-d'),
                    'description' => $expense['name'] . ' - ' . $expenseDate->format('F Y'),
                    'vendor' => 'Supplier ' . chr(65 + rand(0, 25)),
                    'approved_by' => $kepsekUser ? $kepsekUser->id : null,
                    'created_by' => $adminUser ? $adminUser->id : 1,
                ]);
                $expenseCount++;
            }
        }

        $this->command->info('Created ' . $expenseCount . ' expense transactions');
    }
}
