<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'payslip_number',
        'year',
        'month',
        'period_start',
        'period_end',
        'total_month_days',
        'sundays_count',
        'holidays_count',
        'present_days',
        'paid_leave_days',
        'unpaid_leave_days',
        'half_days',
        'late_days_count',
        'late_penalty_half_days',
        'absent_days',
        'total_payable_days',
        'basic_salary',
        'da_amount',
        'hra_amount',
        'ca_amount',
        'ea_amount',
        'sa_amount',
        'gross_salary',
        'earned_basic',
        'earned_gross',
        'employee_pf',
        'employee_esic',
        'monthly_tax',
        'other_deductions',
        'total_deductions',
        'net_salary',
        'status',
        'remarks',
    ];

    protected $casts = [
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'total_month_days' => 'integer',
        'sundays_count' => 'integer',
        'holidays_count' => 'integer',
        'present_days' => 'float',
        'paid_leave_days' => 'float',
        'unpaid_leave_days' => 'float',
        'half_days' => 'float',
        'late_days_count' => 'integer',
        'late_penalty_half_days' => 'float',
        'absent_days' => 'float',
        'total_payable_days' => 'float',
        'basic_salary' => 'float',
        'da_amount' => 'float',
        'hra_amount' => 'float',
        'ca_amount' => 'float',
        'ea_amount' => 'float',
        'sa_amount' => 'float',
        'gross_salary' => 'float',
        'earned_basic' => 'float',
        'earned_gross' => 'float',
        'employee_pf' => 'float',
        'employee_esic' => 'float',
        'monthly_tax' => 'float',
        'other_deductions' => 'float',
        'total_deductions' => 'float',
        'net_salary' => 'float',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

