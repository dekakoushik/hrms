<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'allocated_days',
        'used_days',
        'pending_days',
        'remaining_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'allocated_days' => 'decimal:1',
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

    public function recalculateRemaining(): void
    {
        $this->remaining_days = max(0, $this->allocated_days - $this->used_days - $this->pending_days);
        $this->save();
    }
}

