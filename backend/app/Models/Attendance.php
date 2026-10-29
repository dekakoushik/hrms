<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'shift_id',
        'attendance_date',
        'punch_in_at',
        'punch_in_lat',
        'punch_in_lng',
        'punch_in_location_id',
        'punch_in_location_type',
        'punch_in_distance_meters',
        'punch_in_address',
        'punch_out_at',
        'punch_out_lat',
        'punch_out_lng',
        'punch_out_location_id',
        'punch_out_distance_meters',
        'punch_out_address',
        'punch_out_count',
        'working_hours',
        'status',
        'is_late',
        'late_minutes',
        'is_early_exit',
        'early_minutes',
        'used_grace_period',
        'is_regularized',
        'regularized_by',
        'regularized_at',
        'regularization_reason',
        'notes',
        'is_field_visit',
        'field_visit_selfie',
        'field_visit_remarks',
        'field_visit_status',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'punch_in_at' => 'datetime',
        'punch_out_at' => 'datetime',
        'regularized_at' => 'datetime',
        'punch_in_lat' => 'decimal:8',
        'punch_in_lng' => 'decimal:8',
        'punch_out_lat' => 'decimal:8',
        'punch_out_lng' => 'decimal:8',
        'working_hours' => 'decimal:2',
        'is_late' => 'boolean',
        'is_early_exit' => 'boolean',
        'early_minutes' => 'integer',
        'used_grace_period' => 'boolean',
        'is_regularized' => 'boolean',
        'late_minutes' => 'integer',
        'punch_out_count' => 'integer',
        'is_field_visit' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function regularizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'regularized_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function punchInLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'punch_in_location_id');
    }

    public function punchOutLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'punch_out_location_id');
    }

    /**
     * Check how many times an employee has used their grace period in a given month
     */
    public static function getGraceDaysUsedInMonth(int $employeeId, ?Carbon $date = null): int
    {
        $date = $date ?: Carbon::today();
        return self::where('employee_id', $employeeId)
            ->whereYear('attendance_date', $date->year)
            ->whereMonth('attendance_date', $date->month)
            ->where('used_grace_period', true)
            ->count();
    }
}
