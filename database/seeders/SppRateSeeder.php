<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SppRate;
use App\Models\AcademicYear;

class SppRateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding SPP rates...');

        $academicYears = AcademicYear::all();

        foreach ($academicYears as $year) {
            SppRate::create([
                'academic_year_id' => $year->id,
                'amount' => 200000, // Rp 200.000 per bulan
                'description' => 'Tarif SPP ' . $year->name,
            ]);
        }

        $this->command->info('✓ SPP rates created successfully');
    }
}
