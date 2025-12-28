<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Models\ClassRoom;

class ClassRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding classes...');

        $academicYear = AcademicYear::where('is_active', true)->first();

        if (!$academicYear) {
            $this->command->warn('No active academic year found. Creating one...');
            $academicYear = AcademicYear::create([
                'name' => '2024/2025',
                'start_date' => '2024-07-01',
                'end_date' => '2025-06-30',
                'is_active' => true,
            ]);
        }

        // Delete existing classes if any
        ClassRoom::query()->delete();

        $classData = [
            ['name' => '1A', 'grade' => 1],
            ['name' => '1B', 'grade' => 1],
            ['name' => '2A', 'grade' => 2],
            ['name' => '2B', 'grade' => 2],
            ['name' => '3A', 'grade' => 3],
            ['name' => '3B', 'grade' => 3],
            ['name' => '4A', 'grade' => 4],
            ['name' => '4B', 'grade' => 4],
            ['name' => '5A', 'grade' => 5],
            ['name' => '5B', 'grade' => 5],
            ['name' => '6A', 'grade' => 6],
            ['name' => '6B', 'grade' => 6],
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

        $this->command->info('Created ' . count($classData) . ' classes');
    }
}
