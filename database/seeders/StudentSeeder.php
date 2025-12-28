<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassRoom;
use App\Models\Student;
use Carbon\Carbon;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding students...');

        $classes = ClassRoom::all();

        if ($classes->isEmpty()) {
            $this->command->error('No classes found. Please run ClassRoomSeeder first.');
            return;
        }

        // Delete existing students if any
        Student::query()->delete();

        $studentNames = [
            'Ahmad Hidayat',
            'Siti Nurhaliza',
            'Budi Santoso',
            'Aisyah Putri',
            'Dedi Kurniawan',
            'Fitri Handayani',
            'Gunawan Pratama',
            'Hana Maharani',
            'Irfan Hakim',
            'Juwita Sari',
            'Kevin Anggara',
            'Lina Wati',
            'Muhammad Rizki',
            'Nadia Safitri',
            'Oscar Wijaya',
            'Putri Ayu',
            'Qori Ramadhan',
            'Rini Susanti',
            'Surya Pratama',
            'Tina Marlina',
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
                    'name' => $randomName . ' ' . chr(65 + ($studentCount % 26)),
                    'gender' => $i % 2 == 0 ? 'L' : 'P',
                    'birth_place' => ['Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Yogyakarta'][rand(0, 4)],
                    'birth_date' => Carbon::now()->subYears(6 + $class->grade_level)->subDays(rand(0, 365))->format('Y-m-d'),
                    'address' => 'Jl. Pendidikan No. ' . rand(1, 100) . ', RT 00' . rand(1, 9) . '/RW 00' . rand(1, 9),
                    'class_id' => $class->id,
                    'enrollment_date' => '2024-07-01',
                    'status' => 'active',
                    'parent_name' => 'Bpk/Ibu ' . $randomName,
                    'parent_phone' => '08' . rand(1000000000, 9999999999),
                ]);
                $studentCount++;
            }
        }

        $this->command->info('Created ' . $studentCount . ' students');
    }
}
