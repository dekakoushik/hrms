<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'year',
        'month',
        'period_start',
        'period_end',
        'calendar_days',
        'total_sundays',
        'total_holidays',
        'total_employees_processed',
        'total_gross_payout',
        'total_deductions',
        'total_net_payout',
        'status',
        'processed_by_user_id',
        'processed_at',
    ];

    protected $casts = [
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'calendar_days' => 'integer',
        'total_sundays' => 'integer',
        'total_holidays' => 'integer',
        'total_employees_processed' => 'integer',
        'total_gross_payout' => 'float',
        'total_deductions' => 'float',
        'total_net_payout' => 'float',
        'processed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}

