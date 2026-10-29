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
        'company_id',
        'user_id',
        'employee_id',
        'user_type',
        'employment_type',
        'shift_id',
        'location_type',
        'location_id',
        'department_id',
        'designation_id',
        'manager_id',
        'full_name',
        'dob',
        'gender',
        'marital_status',
        'blood_group',
        'father_or_husband_name',
        'personal_email',
        'contact_number',
        'alternate_number',
        'present_street',
        'present_city',
        'present_state',
        'present_pin',
        'permanent_same_as_present',
        'permanent_street',
        'permanent_city',
        'permanent_state',
        'permanent_pin',
        'primary_contact_name',
        'primary_contact_relationship',
        'primary_contact_phone',
        'primary_contact_address',
        'pan_number',
        'pan_document',
        'aadhar_number',
        'aadhar_document',
        'uan_number',
        'esic_number',
        'account_holder_name',
        'bank_name',
        'bank_branch',
        'account_number',
        'ifsc_code',
        'account_type',
        'bank_proof_document',
        'education_details',
        'basic_salary',
        'da_amount',
        'hra_amount',
        'ca_amount',
        'ea_amount',
        'sa_amount',
        'gross_salary',
        'gratuity_amount',
        'pf_enabled',
        'pf_amount',
        'esic_enabled',
        'esic_employer_amount',
        'esic_employee_amount',
        'ctc_amount',
        'total_deductions',
        'tax_amount',
        'take_home_salary',
        'joining_date',
        'probation_months',
        'probation_end_date',
        'confirmation_date',
        'status',
        'exit_date',
        'exit_reason',
    ];

    protected $casts = [
        'dob' => 'date',
        'joining_date' => 'date',
        'probation_end_date' => 'date',
        'confirmation_date' => 'date',
        'exit_date' => 'date',
        'permanent_same_as_present' => 'boolean',
        'pf_enabled' => 'boolean',
        'esic_enabled' => 'boolean',
        'education_details' => 'array',
        'basic_salary' => 'decimal:2',
        'da_amount' => 'decimal:2',
        'hra_amount' => 'decimal:2',
        'ca_amount' => 'decimal:2',
        'ea_amount' => 'decimal:2',
        'sa_amount' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'gratuity_amount' => 'decimal:2',
        'pf_amount' => 'decimal:2',
        'esic_employer_amount' => 'decimal:2',
        'esic_employee_amount' => 'decimal:2',
        'ctc_amount' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'take_home_salary' => 'decimal:2',
    ];

    /**
     * Check if currently on probation
     */
    public function isOnProbation(): bool
    {
        if ($this->employment_type !== 'Probation') {
            return false;
        }

        if ($this->confirmation_date) {
            return false;
        }

        if ($this->probation_end_date) {
            return now()->startOfDay()->lte($this->probation_end_date);
        }

        return true;
    }

    /**
     * Helper to get days remaining on probation
     */
    public function getProbationDaysRemainingAttribute(): ?int
    {
        if (!$this->isOnProbation() || !$this->probation_end_date) {
            return null;
        }

        $days = (int) now()->startOfDay()->diffInDays($this->probation_end_date, false);
        return $days;
    }

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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    /**
     * Compute full Indian salary structure according to business requirements
     */
    public static function calculateSalaryBreakdown(float $basic, bool $pfEnabled = true, float $tax = 0): array
    {
        $da = round($basic * 0.20, 2);
        $hra = round($basic * 0.60, 2);
        $ca = round($basic * 0.20, 2);
        $ea = round($basic * 0.08, 2);
        $sa = round($basic * 0.20, 2);
        $gross = round($basic + $da + $hra + $ca + $ea + $sa, 2);

        $gratuity = round(($basic + $da) * 0.0481, 2);

        $pf = 0.0;
        if ($pfEnabled) {
            $pfCalculated = ($basic + $da) * 0.12;
            $pf = round(min($pfCalculated, 3000.00), 2);
        }

        $esicAvailable = ($gross <= 21000.00);
        $esicEmployer = $esicAvailable ? round($gross * 0.0325, 2) : 0.0;
        $esicEmployee = $esicAvailable ? round($gross * 0.0075, 2) : 0.0;

        $ctc = round($gross + $gratuity + $pf + $esicEmployer, 2);
        $totalDeductions = round($pf + $esicEmployee, 2);
        $takeHome = round(max(0, $gross - $totalDeductions - $tax), 2);

        return [
            'basic' => $basic,
            'da' => $da,
            'hra' => $hra,
            'ca' => $ca,
            'ea' => $ea,
            'sa' => $sa,
            'gross' => $gross,
            'gratuity' => $gratuity,
            'pf_enabled' => $pfEnabled,
            'pf' => $pf,
            'esic_enabled' => $esicAvailable,
            'esic_employer' => $esicEmployer,
            'esic_employee' => $esicEmployee,
            'ctc' => $ctc,
            'total_deductions' => $totalDeductions,
            'tax' => $tax,
            'take_home' => $takeHome,
        ];
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function resignations(): HasMany
    {
        return $this->hasMany(Resignation::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}
