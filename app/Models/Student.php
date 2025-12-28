<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'class_id',
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

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
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
