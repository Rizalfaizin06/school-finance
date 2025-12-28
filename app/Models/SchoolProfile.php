<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolProfile extends Model
{
    protected $fillable = [
        'name',
        'npsn',
        'address',
        'phone',
        'email',
        'headmaster',
        'treasurer',
        'logo',
        'letterhead',
    ];

    public static function current()
    {
        return static::first() ?? static::create([
            'name' => 'SD Negeri',
            'address' => '',
            'phone' => '',
            'email' => '',
        ]);
    }
}
