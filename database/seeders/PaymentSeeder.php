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
     * 
     * Seeder ini membuat data pembayaran SPP dengan prinsip FIFO (First In First Out)
     * Setiap siswa bayar dari bulan enrollment mereka, berurutan dari yang terlama
     */
    public function run(): void
    {
        $this->command->info('Seeding payment transactions with FIFO logic...');

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
            $this->command->info('Creating SPP payments with FIFO logic...');

            foreach ($students as $student) {
                $enrollmentDate = Carbon::parse($student->enrollment_date);
                $now = Carbon::now();

                // Calculate total months from enrollment to now
                $totalMonths = $enrollmentDate->diffInMonths($now) + 1;

                // Each student pays random 40-80% of their total months (FIFO from oldest)
                $percentagePaid = rand(40, 80);
                $monthsPaid = (int) ceil($totalMonths * ($percentagePaid / 100));

                // Generate all months from enrollment
                $allMonths = [];
                $current = $enrollmentDate->copy();
                while ($current->lte($now)) {
                    $allMonths[] = [
                        'month' => $current->month,
                        'year' => $current->year,
                        'date' => $current->copy()
                    ];
                    $current->addMonth();
                }

                // Pay FIRST N months (FIFO - oldest first)
                $monthsToPay = array_slice($allMonths, 0, $monthsPaid);

                foreach ($monthsToPay as $monthData) {
                    $account = $accounts->random();

                    // Payment date is in that month, random day
                    $paymentDate = Carbon::create(
                        $monthData['year'],
                        $monthData['month'],
                        rand(1, min(28, $monthData['date']->daysInMonth))
                    );

                    // But not in the future
                    if ($paymentDate->isFuture()) {
                        $paymentDate = Carbon::now()->subDays(rand(1, 30));
                    }

                    $receiptNumber = 'KWT/' . $paymentDate->format('Ymd') . '/' . str_pad($paymentCount + 1, 4, '0', STR_PAD_LEFT);

                    Payment::create([
                        'receipt_number' => $receiptNumber,
                        'student_id' => $student->id,
                        'fee_type_id' => $sppFeeType->id,
                        'academic_year_id' => $academicYear->id,
                        'account_id' => $account->id,
                        'amount' => $sppFeeType->amount,
                        'payment_date' => $paymentDate->format('Y-m-d'),
                        'month' => $monthData['month'],
                        'year' => $monthData['year'],
                        'payment_method' => ['cash', 'transfer', 'transfer', 'cash'][rand(0, 3)],
                        'notes' => 'Pembayaran SPP ' . Carbon::create($monthData['year'], $monthData['month'])->format('F Y'),
                        'created_by' => 1,
                    ]);
                    $paymentCount++;
                }

                $unpaidMonths = $totalMonths - $monthsPaid;
                $this->command->info("  {$student->name}: {$monthsPaid}/{$totalMonths} bulan terbayar (tunggakan: {$unpaidMonths} bulan)");
            }

            // Add some other fee type payments
            $this->command->info('Creating other fee payments...');
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
                        'created_by' => 1,
                    ]);
                    $paymentCount++;
                }
            }
        }

        $this->command->info("✓ Created {$paymentCount} payment transactions with FIFO logic");
    }
}
