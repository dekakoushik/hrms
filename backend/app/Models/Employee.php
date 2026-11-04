<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'department_id',
        'designation_id',
        'shift_id',
        'location_id',
        'reporting_manager_id',
        'employee_id',
        'usertype',
        'date_of_joining',
        'employment_type',
        'probation_duration',
        'attendance_location_type',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'marital_status',
        'blood_group',
        'current_address',
        'permanent_address',
        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_contact_phone',
        'pan_number',
        'aadhaar_number',
        'uan_number',
        'esic_number',
        'passport_number',
        'bank_name',
        'bank_account_number',
        'bank_ifsc_code',
        'bank_branch',
        'qualifications',
        'basic_salary',
        'monthly_tax',
        'enable_epf',
        'da_amount',
        'hra_amount',
        'ca_amount',
        'ea_amount',
        'sa_amount',
        'gross_salary',
        'gratuity_amount',
        'employee_pf',
        'employee_esic',
        'employer_esic',
        'total_deductions',
        'ctc_amount',
        'net_salary',
        'status',
    ];

    protected $casts = [
        'qualifications' => 'array',
        'enable_epf' => 'boolean',
        'basic_salary' => 'float',
        'monthly_tax' => 'float',
        'da_amount' => 'float',
        'hra_amount' => 'float',
        'ca_amount' => 'float',
        'ea_amount' => 'float',
        'sa_amount' => 'float',
        'gross_salary' => 'float',
        'gratuity_amount' => 'float',
        'employee_pf' => 'float',
        'employee_esic' => 'float',
        'employer_esic' => 'float',
        'total_deductions' => 'float',
        'ctc_amount' => 'float',
        'net_salary' => 'float',
        'date_of_joining' => 'date:Y-m-d',
        'date_of_birth' => 'date:Y-m-d',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id');
    }

    public function reportees(): HasMany
    {
        return $this->hasMany(Employee::class, 'reporting_manager_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function fieldVisits(): HasMany
    {
        return $this->hasMany(FieldVisit::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Compute comprehensive salary breakdown as per statutory formulas.
     */
    public static function calculateSalaryBreakdown(float $basicSalary, float $monthlyTax, bool $enableEpf): array
    {
        // Monthly Earnings & Allowances
        $da = round($basicSalary * 0.20, 2);      // Dearness Allowance (DA - 20%)
        $hra = round($basicSalary * 0.60, 2);     // House Rent Allowance (HRA - 60%)
        $ca = round($basicSalary * 0.20, 2);      // Conveyance Allowance (CA - 20%)
        $ea = round($basicSalary * 0.08, 2);      // Entertainment Allowance (EA - 8%)
        $sa = round($basicSalary * 0.20, 2);      // Special Allowance (SA - 20%)

        $gross = round($basicSalary + $da + $hra + $ca + $ea + $sa, 2);

        // Statutory Benefits, Deductions & Net Pay
        // Gratuity = (Basic + DA) * 4.81%
        $gratuity = round(($basicSalary + $da) * 0.0481, 2);

        // Employee PF = 12% of (Basic + DA), capped at max 3,000 as per rule (or 0 if disabled)
        $employeePf = 0.00;
        if ($enableEpf) {
            $pfCalculated = ($basicSalary + $da) * 0.12;
            $employeePf = round(min($pfCalculated, 3000.00), 2);
        }

        // ESIC Eligibility (applicable if Gross Salary <= 21,000)
        $employeeEsic = 0.00;
        $employerEsic = 0.00;
        if ($gross <= 21000.00) {
            $employeeEsic = round($gross * 0.0075, 2); // 0.75%
            $employerEsic = round($gross * 0.0325, 2); // 3.25%
        }

        // Income Tax (TDS) manual entry
        $tds = round(max(0.00, $monthlyTax), 2);

        // Deductions from employee gross pay (PF + ESIC + TDS)
        $totalDeductions = round($employeePf + $employeeEsic + $tds, 2);

        // Net in-hand Take Home Salary
        $netSalary = round($gross - $totalDeductions, 2);

        // Cost to Company (CTC) = Gross + Gratuity + Employer ESIC (+ Employer PF matching if applied)
        $ctc = round($gross + $gratuity + $employerEsic + $employeePf, 2);

        return [
            'basic_salary' => $basicSalary,
            'da_amount' => $da,
            'hra_amount' => $hra,
            'ca_amount' => $ca,
            'ea_amount' => $ea,
            'sa_amount' => $sa,
            'gross_salary' => $gross,
            'gratuity_amount' => $gratuity,
            'employee_pf' => $employeePf,
            'employee_esic' => $employeeEsic,
            'employer_esic' => $employerEsic,
            'monthly_tax' => $tds,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'ctc_amount' => $ctc,
            'enable_epf' => $enableEpf,
        ];
    }
}
