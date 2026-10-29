<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'grace_period_minutes',
        'half_day_hours',
        'full_day_hours',
        'is_night_shift',
        'is_active',
        'description',
    ];

    protected $casts = [
        'grace_period_minutes' => 'integer',
        'half_day_hours' => 'integer',
        'full_day_hours' => 'integer',
        'is_night_shift' => 'boolean',
        'is_active' => 'boolean',
    ];
}
