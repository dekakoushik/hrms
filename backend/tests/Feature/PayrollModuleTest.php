<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Company $company;
    protected Employee $employee;
    protected User $employeeUser;
    protected LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Riangdo Veneers Pvt. Ltd.',
            'code' => 'RVPL',
            'legal_type' => 'Private Limited',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'company_id' => $this->company->id,
            'name' => 'Super Administrator',
            'email' => 'admin@hrms.in',
            'mobile_number' => '9876543210',
            'password' => Hash::make('AdminPass@123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->employeeUser = User::create([
            'company_id' => $this->company->id,
            'name' => 'Koushi Deka',
            'email' => 'koushikdeka300@gmail.com',
            'mobile_number' => '916232247729',
            'password' => Hash::make('UserPass@123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        // Basic: 30,000 -> DA: 6000, HRA: 18000, CA: 6000, EA: 2400, SA: 6000 => Gross: 68,400
        // PF (12% of 36000 capped at 3000): 3000 => Baseline Take Home = 65,400
        $salaryData = Employee::calculateSalaryBreakdown(30000.00, true);

        $this->employee = Employee::create(array_merge([
            'company_id' => $this->company->id,
            'user_id' => $this->employeeUser->id,
            'employee_id' => 'EMP-001',
            'user_type' => 'user',
            'employment_type' => 'Full-time',
            'full_name' => 'Koushi Deka',
            'personal_email' => 'koushikdeka300@gmail.com',
            'contact_number' => '916232247729',
            'status' => 'active',
        ], $salaryData));

        $this->leaveType = LeaveType::create([
            'name' => 'Casual Leave (CL)',
            'code' => 'CL',
            'default_annual_quota' => 12.0,
            'is_paid' => true,
            'is_active' => true,
        ]);
    }

    public function test_payroll_index_page_accessible(): void
    {
        $resp = $this->actingAs($this->superAdmin)->get('/payroll');
        $resp->assertStatus(200);
        $resp->assertSee('Payroll');
    }

    public function test_payroll_calculation_with_leave_deduction_formula(): void
    {
        // 1. Employee takes 2 full days leave in October 2026
        LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-06',
            'days_count' => 2.0,
            'is_half_day' => false,
            'reason' => 'Personal work',
            'status' => 'approved',
        ]);

        // 2. Employee takes 1 half day leave (0.5 day)
        LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-12',
            'days_count' => 0.5,
            'is_half_day' => true,
            'half_day_type' => 'first_half',
            'reason' => 'Doctor appointment',
            'status' => 'approved',
        ]);

        // Total deductible days = 2.0 + (1 * 0.5) = 2.5 days

        // Process Payroll for October 2026 (Month 10)
        $resp = $this->actingAs($this->superAdmin)->postJson('/payroll/process', [
            'year' => 2026,
            'month' => 10,
            'company_id' => $this->company->id,
        ]);

        $resp->assertStatus(200);

        // Verify PayrollRun
        $payrollRun = PayrollRun::where('year', 2026)->where('month', 10)->first();
        $this->assertNotNull($payrollRun);
        $this->assertEquals('01 Oct 2026 - 31 Oct 2026', $payrollRun->cycle_period);
        $this->assertEquals(1, $payrollRun->employee_count);

        // Verify Payslip
        $payslip = Payslip::where('employee_id', $this->employee->id)
            ->where('year', 2026)
            ->where('month', 10)
            ->first();

        $this->assertNotNull($payslip);
        $this->assertEquals(2.0, (float) $payslip->full_day_leaves);
        $this->assertEquals(1.0, (float) $payslip->half_day_leaves);
        $this->assertEquals(2.5, (float) $payslip->total_deductible_days);

        // Check exact formula:
        // daily_rate = round(baseline_take_home / 30, 2)
        // leave_deduction = round(daily_rate * 2.5, 2)
        // net_salary = baseline_take_home - leave_deduction
        $expectedDailyRate = round($this->employee->take_home_salary / 30.0, 2);
        $expectedLeaveDeduction = round($expectedDailyRate * 2.5, 2);
        $expectedNetSalary = round($this->employee->take_home_salary - $expectedLeaveDeduction, 2);

        $this->assertEquals($expectedDailyRate, (float) $payslip->daily_take_home_rate);
        $this->assertEquals($expectedLeaveDeduction, (float) $payslip->leave_deduction_amount);
        $this->assertEquals($expectedNetSalary, (float) $payslip->net_salary);
    }

    public function test_payslip_view_and_mark_batch_paid(): void
    {
        // Process payroll
        $this->actingAs($this->superAdmin)->postJson('/payroll/process', [
            'year' => 2026,
            'month' => 10,
            'company_id' => $this->company->id,
        ]);

        $payrollRun = PayrollRun::where('year', 2026)->where('month', 10)->first();
        $this->assertNotNull($payrollRun);
        $payslip = $payrollRun->payslips()->first();
        $this->assertNotNull($payslip);

        // Admin can view batch
        $batchResp = $this->actingAs($this->superAdmin)->get("/payroll/runs/{$payrollRun->id}");
        $batchResp->assertStatus(200);
        $batchResp->assertSee($payslip->payslip_number);

        // Employee can view their printable payslip
        $slipResp = $this->actingAs($this->employeeUser)->get("/payroll/payslips/{$payslip->id}");
        $slipResp->assertStatus(200);
        $slipResp->assertSee('Pay Slip for the month of');

        // Mark batch as paid
        $markPaidResp = $this->actingAs($this->superAdmin)->post("/payroll/runs/{$payrollRun->id}/mark-paid");
        $markPaidResp->assertRedirect();

        $payrollRun->refresh();
        $this->assertEquals('paid', $payrollRun->status);
        $payslip->refresh();
        $this->assertEquals('paid', $payslip->status);
        $this->assertNotNull($payslip->paid_at);
    }
}
