<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'company_id',
        'payslip_number',
        'year',
        'month',
        'start_date',
        'end_date',
        'month_calendar_days',
        'full_day_leaves',
        'half_day_leaves',
        'total_deductible_days',
        'days_worked',
        'basic_salary',
        'da_amount',
        'hra_amount',
        'ca_amount',
        'ea_amount',
        'sa_amount',
        'gross_salary',
        'pf_deduction',
        'esic_deduction',
        'tax_deduction',
        'statutory_deductions',
        'baseline_take_home',
        'daily_take_home_rate',
        'leave_deduction_amount',
        'net_salary',
        'bank_name',
        'bank_account_number',
        'ifsc_code',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'month_calendar_days' => 'integer',
        'full_day_leaves' => 'decimal:1',
        'half_day_leaves' => 'decimal:1',
        'total_deductible_days' => 'decimal:1',
        'days_worked' => 'decimal:1',
        'basic_salary' => 'decimal:2',
        'da_amount' => 'decimal:2',
        'hra_amount' => 'decimal:2',
        'ca_amount' => 'decimal:2',
        'ea_amount' => 'decimal:2',
        'sa_amount' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'pf_deduction' => 'decimal:2',
        'esic_deduction' => 'decimal:2',
        'tax_deduction' => 'decimal:2',
        'statutory_deductions' => 'decimal:2',
        'baseline_take_home' => 'decimal:2',
        'daily_take_home_rate' => 'decimal:2',
        'leave_deduction_amount' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
