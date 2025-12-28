<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassRoom extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'academic_year_id',
        'name',
        'grade_level',
        'homeroom_teacher',
        'capacity',
    ];

    protected $casts = [
        'grade_level' => 'integer',
        'capacity' => 'integer',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->name} - {$this->academicYear->name}";
    }
}
