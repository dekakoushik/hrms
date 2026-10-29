<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_allocated',
        'used_days',
        'pending_days',
        'remaining_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_allocated' => 'decimal:1',
        'used_days' => 'decimal:1',
        'pending_days' => 'decimal:1',
        'remaining_days' => 'decimal:1',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Ensure all leave balances are initialized for an employee from global quotas
     */
    public static function ensureBalancesForEmployee(int $employeeId, ?int $year = null): void
    {
        $year = $year ?: (int) date('Y');
        $types = LeaveType::where('is_active', true)->get();

        foreach ($types as $type) {
            self::firstOrCreate(
                [
                    'employee_id' => $employeeId,
                    'leave_type_id' => $type->id,
                    'year' => $year,
                ],
                [
                    'total_allocated' => $type->default_annual_quota,
                    'used_days' => 0,
                    'pending_days' => 0,
                    'remaining_days' => $type->default_annual_quota,
                ]
            );
        }
    }
}
