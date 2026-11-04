<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'visit_time',
        'latitude',
        'longitude',
        'location_name',
        'address',
        'client_name',
        'remarks',
        'selfie_image',
        'status',
        'admin_feedback',
    ];

    protected $casts = [
        'date' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

