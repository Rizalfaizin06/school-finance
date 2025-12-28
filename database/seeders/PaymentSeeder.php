<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\FeeType;
use App\Models\Account;
use App\Models\AcademicYear;
use App\Models\Payment;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding payment transactions...');

        $students = Student::all();
        $feeTypes = FeeType::all();
        $accounts = Account::all();
        $academicYear = AcademicYear::where('is_active', true)->first();

        if ($students->isEmpty() || $feeTypes->isEmpty() || $accounts->isEmpty() || !$academicYear) {
            $this->command->error('Missing required data. Please run other seeders first.');
            return;
        }

        // Delete existing payments if any
        Payment::query()->delete();

        $paymentCount = 0;
        $sppFeeType = $feeTypes->where('name', 'SPP')->first();

        if ($sppFeeType) {
            // For each of last 6 months
            for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
                $paymentDate = Carbon::now()->subMonths($monthsAgo);

                // 70-90% students pay SPP each month
                $payingStudents = $students->random(min(rand(70, 90), $students->count()));

                foreach ($payingStudents as $student) {
                    // Random account - distribute across all accounts
                    $account = $accounts->random();

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
                        'payment_method' => ['cash', 'transfer', 'transfer', 'cash'][rand(0, 3)], // 50% transfer, 50% cash
                        'notes' => 'Pembayaran SPP ' . $paymentDate->format('F Y'),
                    ]);
                    $paymentCount++;
                }
            }

            // Add some other fee type payments
            $otherFees = $feeTypes->whereNotIn('name', ['SPP']);
            foreach ($otherFees as $feeType) {
                $randomStudents = $students->random(min(rand(20, 40), $students->count()));
                foreach ($randomStudents as $student) {
                    $paymentDate = Carbon::now()->subDays(rand(1, 180));
                    $account = $accounts->random();

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
    }
}
