<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'default_annual_quota',
        'is_paid',
        'carry_forward',
        'max_consecutive_days',
        'description',
        'is_active',
    ];

    protected $casts = [
        'default_annual_quota' => 'decimal:1',
        'is_paid' => 'boolean',
        'carry_forward' => 'boolean',
        'max_consecutive_days' => 'decimal:1',
        'is_active' => 'boolean',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
