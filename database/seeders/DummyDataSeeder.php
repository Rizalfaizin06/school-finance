<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\FeeType;
use App\Models\ExpenseCategory;
use App\Models\Account;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding dummy data...');

        // Get or create academic year
        $academicYear = AcademicYear::first();
        if (!$academicYear) {
            $academicYear = AcademicYear::create([
                'name' => '2024/2025',
                'start_date' => '2024-07-01',
                'end_date' => '2025-06-30',
                'is_active' => true,
                'description' => 'Tahun Ajaran 2024/2025'
            ]);
        }

        // Get or create classes
        $classes = ClassRoom::all();
        if ($classes->isEmpty()) {
            $classData = [
                ['name' => '1A', 'grade' => 1], ['name' => '1B', 'grade' => 1],
                ['name' => '2A', 'grade' => 2], ['name' => '2B', 'grade' => 2],
                ['name' => '3A', 'grade' => 3], ['name' => '3B', 'grade' => 3],
                ['name' => '4A', 'grade' => 4], ['name' => '4B', 'grade' => 4],
                ['name' => '5A', 'grade' => 5], ['name' => '5B', 'grade' => 5],
                ['name' => '6A', 'grade' => 6], ['name' => '6B', 'grade' => 6],
            ];
            
            foreach ($classData as $data) {
                ClassRoom::create([
                    'name' => $data['name'],
                    'academic_year_id' => $academicYear->id,
                    'grade_level' => $data['grade'],
                    'capacity' => 30,
                    'homeroom_teacher' => 'Guru ' . $data['name']
                ]);
            }
            $classes = ClassRoom::all();
        }

        // Get fee types and categories
        $feeTypes = FeeType::all();
        $expenseCategories = ExpenseCategory::all();
        $account = Account::first();

        // Get existing students or create new ones
        $students = Student::all();
        if ($students->isEmpty()) {
            $this->command->info('Creating students...');
            $studentNames = [
                'Ahmad Hidayat', 'Siti Nurhaliza', 'Budi Santoso', 'Aisyah Putri', 'Dedi Kurniawan',
                'Fitri Handayani', 'Gunawan Pratama', 'Hana Maharani', 'Irfan Hakim', 'Juwita Sari',
                'Kevin Anggara', 'Lina Wati', 'Muhammad Rizki', 'Nadia Safitri', 'Oscar Wijaya',
                'Putri Ayu', 'Qori Ramadhan', 'Rini Susanti', 'Surya Pratama', 'Tina Marlina',
            ];

            $studentCount = 0;
            foreach ($classes as $class) {
                for ($i = 1; $i <= 8; $i++) {
                    $randomName = $studentNames[array_rand($studentNames)];
                    $nisNumber = str_pad($studentCount + 1, 6, '0', STR_PAD_LEFT);
                    $nisnNumber = '00' . $nisNumber;
                    
                    Student::create([
                        'nis' => $nisNumber,
                        'nisn' => $nisnNumber,
                        'name' => $randomName . ' ' . chr(65 + $studentCount),
                        'gender' => $i % 2 == 0 ? 'L' : 'P',
                        'birth_place' => ['Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Yogyakarta'][rand(0, 4)],
                        'birth_date' => Carbon::now()->subYears(rand(6, 12))->format('Y-m-d'),
                        'address' => 'Jl. Pendidikan No. ' . rand(1, 100),
                        'class_id' => $class->id,
                        'enrollment_date' => '2024-07-01',
                        'status' => 'active',
                        'parent_name' => 'Orang Tua ' . $randomName,
                        'parent_phone' => '08' . rand(1000000000, 9999999999),
                    ]);
                    $studentCount++;
                }
            }
            $students = Student::all();
            $this->command->info('Created ' . $students->count() . ' students');
        } else {
            $this->command->info('Using existing ' . $students->count() . ' students');
        }

        // Create payments for the last 6 months
        $this->command->info('Creating payment transactions...');
        $paymentCount = Payment::count(); // Start from existing count
        $sppFeeType = $feeTypes->where('name', 'SPP')->first();
        
        if ($sppFeeType && $account) {
            // For each of last 6 months
            for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
                $paymentDate = Carbon::now()->subMonths($monthsAgo);
                
                // 70-90% students pay SPP each month
                $payingStudents = $students->random(min(rand(70, 90), $students->count()));
                
                foreach ($payingStudents as $student) {
                    Payment::create([
                        'receipt_number' => 'KWT/' . $paymentDate->format('Ymd') . '/' . str_pad($paymentCount + 1, 4, '0', STR_PAD_LEFT),
                        'student_id' => $student->id,
                        'fee_type_id' => $sppFeeType->id,
                        'academic_year_id' => $academicYear->id,
                        'account_id' => $account->id,
                        'amount' => $sppFeeType->amount,
                        'payment_date' => $paymentDate->day(rand(1, 28))->format('Y-m-d'),
                        'month' => $paymentDate->month,
                        'year' => $paymentDate->year,
                        'payment_method' => ['cash', 'transfer'][rand(0, 1)],
                        'notes' => 'Pembayaran SPP ' . $paymentDate->format('F Y'),
                    ]);
                    $paymentCount++;
                }
            }
            
            // Add some other fee type payments
            $otherFees = $feeTypes->whereNotIn('name', ['SPP'])->take(3);
            foreach ($otherFees as $feeType) {
                $randomStudents = $students->random(min(rand(20, 40), $students->count()));
                foreach ($randomStudents as $student) {
                    $paymentDate = Carbon::now()->subDays(rand(1, 180));
                    Payment::create([
                        'receipt_number' => 'KWT/' . $paymentDate->format('Ymd') . '/' . str_pad($paymentCount + 1, 4, '0', STR_PAD_LEFT),
                        'student_id' => $student->id,
                        'fee_type_id' => $feeType->id,
                        'academic_year_id' => $academicYear->id,
                        'account_id' => $account->id,
                        'amount' => $feeType->amount,
                        'payment_date' => $paymentDate->format('Y-m-d'),
                        'payment_method' => ['cash', 'transfer'][rand(0, 1)],
                        'notes' => 'Pembayaran ' . $feeType->name,
                    ]);
                    $paymentCount++;
                }
            }
        }

        $this->command->info('Created ' . $paymentCount . ' payment transactions');

        // Create expense transactions for the last 6 months
        $this->command->info('Creating expense transactions...');
        $expenseCount = Expense::count(); // Start from existing count
        $adminUser = User::role('admin')->first();
        $kepsekUser = User::role('kepala_sekolah')->first();

        if ($account && $expenseCategories->isNotEmpty()) {
            $expenseTypes = [
                ['name' => 'Gaji Guru', 'amount' => 5000000],
                ['name' => 'Listrik', 'amount' => 800000],
                ['name' => 'Air', 'amount' => 300000],
                ['name' => 'Internet', 'amount' => 500000],
                ['name' => 'ATK', 'amount' => 750000],
                ['name' => 'Kebersihan', 'amount' => 400000],
                ['name' => 'Pemeliharaan Gedung', 'amount' => 2000000],
                ['name' => 'Peralatan Olahraga', 'amount' => 1500000],
            ];

            for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
                $expenseDate = Carbon::now()->subMonths($monthsAgo);
                
                // 5-8 expenses per month
                for ($i = 0; $i < rand(5, 8); $i++) {
                    $expense = $expenseTypes[array_rand($expenseTypes)];
                    $category = $expenseCategories->random();
                    
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
        }

        $this->command->info('Created ' . $expenseCount . ' expense transactions');

        // Update account balance based on all transactions
        if ($account) {
            $totalIncome = Payment::where('account_id', $account->id)->sum('amount');
            $totalExpense = Expense::where('account_id', $account->id)->sum('amount');
            $account->update([
                'balance' => $account->opening_balance + $totalIncome - $totalExpense
            ]);
            
            $this->command->info('Updated account balance: Rp ' . number_format($account->balance, 0, ',', '.'));
        }

        $this->command->info('Dummy data seeding completed!');
    }
}
