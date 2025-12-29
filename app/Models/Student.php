<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    protected $fillable = [
        'nis',
        'nisn',
        'name',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'enrollment_date',
        'status',
        'parent_name',
        'parent_phone',
        'parent_email',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'enrollment_date' => 'date',
    ];

    // Relasi ke kelas melalui pivot table student_classes
    public function studentClasses(): HasMany
    {
        return $this->hasMany(StudentClass::class);
    }

    // Get class for specific academic year
    public function getClassForYear($academicYearId)
    {
        return $this->studentClasses()
            ->where('academic_year_id', $academicYearId)
            ->with('classRoom')
            ->first();
    }

    // Get current class (based on active academic year)
    public function getCurrentClass()
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            return null;
        }
        return $this->getClassForYear($activeYear->id)?->classRoom;
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getFullNameWithNisAttribute(): string
    {
        return "{$this->nis} - {$this->name}";
    }
}
