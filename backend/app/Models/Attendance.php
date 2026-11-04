<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'punch_in_time',
        'punch_in_latitude',
        'punch_in_longitude',
        'punch_in_location_id',
        'punch_in_distance_meters',
        'punch_in_status',
        'punch_in_note',
        'punch_out_time',
        'punch_out_latitude',
        'punch_out_longitude',
        'punch_out_location_id',
        'punch_out_distance_meters',
        'punch_out_note',
        'total_working_hours',
        'status',
        'location_type',
        'is_regularized',
        'regularization_reason',
        'regularization_remarks',
        'regularized_by_user_id',
        'regularized_at',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'punch_in_latitude' => 'float',
        'punch_in_longitude' => 'float',
        'punch_in_distance_meters' => 'float',
        'punch_out_latitude' => 'float',
        'punch_out_longitude' => 'float',
        'punch_out_distance_meters' => 'float',
        'total_working_hours' => 'float',
        'is_regularized' => 'boolean',
        'regularized_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function punchInLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'punch_in_location_id');
    }

    public function punchOutLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'punch_out_location_id');
    }

    public function regularizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'regularized_by_user_id');
    }
}

