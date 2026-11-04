<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'start_time',
        'end_time',
        'grace_period_minutes',
        'half_day_minutes',
        'full_day_minutes',
        'is_night_shift',
        'is_active',
    ];

    protected $casts = [
        'grace_period_minutes' => 'integer',
        'half_day_minutes' => 'integer',
        'full_day_minutes' => 'integer',
        'is_night_shift' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
