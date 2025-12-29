<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\AcademicYear;
use App\Models\StudentClass;

class StudentClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding student_classes...');

        // Delete existing student_classes if any
        StudentClass::query()->delete();

        $students = Student::all();
        $classes = ClassRoom::all();
        $academicYears = AcademicYear::orderBy('name')->get();

        if ($students->isEmpty() || $classes->isEmpty() || $academicYears->isEmpty()) {
            $this->command->error('No students, classes, or academic years found.');
            return;
        }

        // Logic: Distribute students to classes for each academic year
        // Assume students advance to next grade each year
        $studentsPerClass = 8;
        $studentCount = 0;

        foreach ($academicYears as $yearIndex => $academicYear) {
            $studentCount = 0;

            foreach ($classes as $class) {
                // Get grade level from class name (1A -> 1, 2B -> 2, etc.)
                $gradeLevel = (int) substr($class->name, 0, 1);

                // Calculate which students should be in this class this year
                // For first year (2025/2026), assign students normally
                // For subsequent years, students advance grades
                $adjustedGrade = $gradeLevel - $yearIndex;

                // Skip if adjusted grade is invalid (< 1 or > 6)
                if ($adjustedGrade < 1 || $adjustedGrade > 6) {
                    continue;
                }

                // Get students for this class
                $studentsForClass = $students->slice($studentCount, $studentsPerClass);

                foreach ($studentsForClass as $student) {
                    StudentClass::create([
                        'student_id' => $student->id,
                        'class_id' => $class->id,
                        'academic_year_id' => $academicYear->id,
                    ]);
                }

                $studentCount += $studentsPerClass;
            }
        }

        $totalRecords = StudentClass::count();
        $this->command->info("Created {$totalRecords} student_class records");
    }
}
