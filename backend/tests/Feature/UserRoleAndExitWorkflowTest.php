<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Resignation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRoleAndExitWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Department $department;
    protected Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');

        $this->company = Company::first() ?? Company::create([
            'name' => 'Riangdo Venners Pvt. Ltd.',
            'code' => 'RVPL',
            'legal_type' => 'Private Limited',
            'is_active' => true,
        ]);

        $this->department = Department::first() ?? Department::create([
            'name' => 'Technology',
            'code' => 'TECH',
            'is_active' => true,
        ]);

        $this->designation = Designation::first() ?? Designation::create([
            'name' => 'Senior Developer',
            'code' => 'SRDEV',
            'is_active' => true,
        ]);
    }

    public function test_regular_user_dashboard_displays_attendance_metrics_and_own_payslips(): void
    {
        // 1. Create a regular user with employee profile
        $user = User::create([
            'name' => 'Vikas Verma',
            'email' => 'vikas@test.in',
            'mobile_number' => '9812345678',
            'password' => Hash::make('Secret@123'),
            'role' => 'user',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_id' => 'EMP-VIK-01',
            'first_name' => 'Vikas',
            'last_name' => 'Verma',
            'full_name' => 'Vikas Verma',
            'personal_email' => 'vikas@test.in',
            'official_email' => 'vikas@test.in',
            'contact_number' => '9812345678',
            'date_of_joining' => Carbon::now()->subMonths(6),
            'status' => 'active',
            'employment_type' => 'full_time',
            'basic_salary' => 50000,
            'gross_salary' => 50000,
            'take_home_salary' => 45000,
        ]);

        // 2. Record attendances for the current month
        $now = Carbon::now();
        Attendance::create([
            'employee_id' => $employee->id,
            'company_id' => $this->company->id,
            'attendance_date' => $now->copy()->subDays(2),
            'punch_in_at' => $now->copy()->subDays(2)->setTime(9, 30),
            'punch_out_at' => $now->copy()->subDays(2)->setTime(18, 30),
            'status' => 'present',
            'is_late' => false,
        ]);

        Attendance::create([
            'employee_id' => $employee->id,
            'company_id' => $this->company->id,
            'attendance_date' => $now->copy()->subDays(1),
            'punch_in_at' => $now->copy()->subDays(1)->setTime(10, 15),
            'punch_out_at' => $now->copy()->subDays(1)->setTime(18, 30),
            'status' => 'half_day',
            'is_late' => true,
            'late_minutes' => 45,
        ]);

        // 3. Create a payslip for this employee
        $payrollRun = PayrollRun::create([
            'company_id' => $this->company->id,
            'year' => $now->year,
            'month' => $now->month,
            'cycle_period' => $now->format('F Y'),
            'start_date' => $now->copy()->startOfMonth(),
            'end_date' => $now->copy()->endOfMonth(),
            'total_gross' => 50000,
            'total_deductions' => 5000,
            'total_net_payout' => 45000,
            'status' => 'approved',
            'employee_count' => 1,
            'processed_by' => $user->id,
        ]);

        $payslip = Payslip::create([
            'payroll_run_id' => $payrollRun->id,
            'employee_id' => $employee->id,
            'company_id' => $this->company->id,
            'payslip_number' => 'PAY-TEST-001',
            'year' => $now->year,
            'month' => $now->month,
            'start_date' => $now->copy()->startOfMonth(),
            'end_date' => $now->copy()->endOfMonth(),
            'basic_salary' => 50000,
            'gross_salary' => 50000,
            'net_salary' => 45000,
            'status' => 'generated',
        ]);

        // 4. Authenticate as regular user and access dashboard
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Present Days');
        $response->assertSee('Half Days');
        $response->assertSee('Late Arrivals');
        $response->assertSee('My Recent Payslips');
        $response->assertSee('PAY-TEST-001');

        // Verify user cannot see admin-only master center
        $response->assertDontSee('Superadmin Master Center');
    }

    public function test_regular_user_cannot_view_or_download_another_employees_payslip(): void
    {
        // User A
        $userA = User::create([
            'name' => 'User A',
            'email' => 'userA@test.in',
            'mobile_number' => '9800000001',
            'password' => Hash::make('Secret@123'),
            'role' => 'user',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $empA = Employee::create([
            'user_id' => $userA->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_id' => 'EMP-AAA-01',
            'first_name' => 'User',
            'last_name' => 'A',
            'full_name' => 'User A',
            'personal_email' => 'userA@test.in',
            'official_email' => 'userA@test.in',
            'contact_number' => '9800000001',
            'date_of_joining' => Carbon::now()->subMonths(3),
            'status' => 'active',
            'basic_salary' => 30000,
            'gross_salary' => 30000,
            'take_home_salary' => 28000,
        ]);

        // User B
        $userB = User::create([
            'name' => 'User B',
            'email' => 'userB@test.in',
            'mobile_number' => '9800000002',
            'password' => Hash::make('Secret@123'),
            'role' => 'user',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $empB = Employee::create([
            'user_id' => $userB->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_id' => 'EMP-BBB-02',
            'first_name' => 'User',
            'last_name' => 'B',
            'full_name' => 'User B',
            'personal_email' => 'userB@test.in',
            'official_email' => 'userB@test.in',
            'contact_number' => '9800000002',
            'date_of_joining' => Carbon::now()->subMonths(3),
            'status' => 'active',
            'basic_salary' => 60000,
            'gross_salary' => 60000,
            'take_home_salary' => 54000,
        ]);

        $payrollRun = PayrollRun::create([
            'company_id' => $this->company->id,
            'year' => 2026,
            'month' => 10,
            'cycle_period' => 'October 2026',
            'start_date' => Carbon::parse('2026-10-01'),
            'end_date' => Carbon::parse('2026-10-31'),
            'total_gross' => 100000,
            'total_deductions' => 10000,
            'total_net_payout' => 90000,
            'status' => 'approved',
            'employee_count' => 2,
            'processed_by' => $userA->id,
        ]);

        $slipB = Payslip::create([
            'payroll_run_id' => $payrollRun->id,
            'employee_id' => $empB->id,
            'company_id' => $this->company->id,
            'payslip_number' => 'PAY-SECRET-B',
            'year' => 2026,
            'month' => 10,
            'start_date' => Carbon::parse('2026-10-01'),
            'end_date' => Carbon::parse('2026-10-31'),
            'basic_salary' => 60000,
            'gross_salary' => 60000,
            'net_salary' => 54000,
            'status' => 'generated',
        ]);

        // User A attempts to view User B's payslip -> should abort with 403
        $response = $this->actingAs($userA)->get(route('payroll.payslip', $slipB->id));
        $response->assertStatus(403);
    }

    public function test_employee_can_submit_resignation_and_admin_can_approve_or_extend(): void
    {
        $admin = User::create([
            'name' => 'Admin Boss',
            'email' => 'adminboss@test.in',
            'mobile_number' => '9899999999',
            'password' => Hash::make('Secret@123'),
            'role' => 'admin',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $employeeUser = User::create([
            'name' => 'Rahul Sharma',
            'email' => 'rahul@test.in',
            'mobile_number' => '9811112222',
            'password' => Hash::make('Secret@123'),
            'role' => 'user',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_id' => 'EMP-RAHUL-01',
            'first_name' => 'Rahul',
            'last_name' => 'Sharma',
            'full_name' => 'Rahul Sharma',
            'personal_email' => 'rahul@test.in',
            'official_email' => 'rahul@test.in',
            'contact_number' => '9811112222',
            'date_of_joining' => Carbon::now()->subYear(),
            'status' => 'active',
            'basic_salary' => 40000,
            'gross_salary' => 40000,
            'take_home_salary' => 38000,
        ]);

        // 1. Employee submits resignation
        $reqLwd = Carbon::today()->addDays(30)->format('Y-m-d');
        $submitResponse = $this->actingAs($employeeUser)->post(route('resignations.store'), [
            'reason' => 'Pursuing higher studies at university.',
            'requested_last_working_day' => $reqLwd,
            'notice_period_days' => 30,
        ]);

        $submitResponse->assertRedirect();
        $this->assertDatabaseHas('resignations', [
            'employee_id' => $employee->id,
            'status' => 'pending',
            'notice_period_days' => 30,
        ]);

        $resignation = Resignation::where('employee_id', $employee->id)->first();
        $this->assertNotNull($resignation);

        // 2. Regular employee attempts to action/approve -> Should fail / be unauthorized
        $unauthAction = $this->actingAs($employeeUser)->post(route('resignations.action', $resignation->id), [
            'action' => 'approve',
            'action_remarks' => 'Attempting self-approval',
        ]);
        $this->assertEquals('pending', $resignation->fresh()->status);

        // 3. Admin extends notice period to 45 days and sets new LWD
        $extendedLwd = Carbon::today()->addDays(45)->format('Y-m-d');
        $adminExtend = $this->actingAs($admin)->post(route('resignations.action', $resignation->id), [
            'action' => 'extend_notice',
            'notice_period_days' => 45,
            'approved_last_working_day' => $extendedLwd,
            'action_remarks' => 'Handover on ongoing client projects required for additional 15 days.',
        ]);

        $adminExtend->assertRedirect();
        $resignation->refresh();
        $employee->refresh();

        $this->assertEquals('approved', $resignation->status);
        $this->assertEquals(45, $resignation->notice_period_days);
        $this->assertEquals($extendedLwd, $resignation->approved_last_working_day->format('Y-m-d'));
        $this->assertEquals($extendedLwd, $employee->exit_date->format('Y-m-d'));
    }

    public function test_regular_user_has_view_only_profile_and_cannot_update_records(): void
    {
        $user = User::create([
            'name' => 'Koushi Deka',
            'email' => 'koushi@test.in',
            'mobile_number' => '9865432100',
            'password' => Hash::make('Secret@123'),
            'role' => 'user',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_id' => 'EMP-KD-01',
            'first_name' => 'Koushi',
            'last_name' => 'Deka',
            'full_name' => 'Koushi Deka',
            'personal_email' => 'koushi@test.in',
            'official_email' => 'koushi@test.in',
            'contact_number' => '9865432100',
            'date_of_joining' => Carbon::now()->subMonths(4),
            'status' => 'active',
            'basic_salary' => 45000,
            'gross_salary' => 45000,
            'take_home_salary' => 41000,
        ]);

        // 1. Visit profile page -> should render "My Employee Profile" and "View-Only"
        $response = $this->actingAs($user)->get(route('settings.profile'));
        $response->assertStatus(200);
        $response->assertSee('My Employee Profile');
        $response->assertSee('View-Only');
        $response->assertDontSee('Superadmin Profile Settings');

        // 2. Regular user tries to POST to update profile details -> rejected with error
        $updateAttempt = $this->actingAs($user)->post(route('settings.profile.update'), [
            'name' => 'Hacked Name',
            'email' => 'hacked@test.in',
            'mobile_number' => '9865432100',
        ]);

        $updateAttempt->assertSessionHasErrors(['profile_error']);
        $this->assertEquals('Koushi Deka', $user->fresh()->name);
    }

    public function test_superadmin_can_update_profile_settings(): void
    {
        $superadmin = User::where('role', 'superadmin')->first();

        $response = $this->actingAs($superadmin)->get(route('settings.profile'));
        $response->assertStatus(200);
        $response->assertSee('Superadmin Profile Settings');
        $response->assertSee('Save Profile Changes');

        $updateResponse = $this->actingAs($superadmin)->post(route('settings.profile.update'), [
            'name' => 'Rajesh Sharma Senior',
            'email' => 'admin@hrms.in',
            'mobile_number' => '9876543210',
            'designation' => 'Executive Vice President HR',
        ]);

        $updateResponse->assertRedirect();
        $this->assertEquals('Rajesh Sharma Senior', $superadmin->fresh()->name);
    }

    public function test_regular_user_can_only_see_their_own_attendance_records(): void
    {
        // User A
        $userA = User::create([
            'name' => 'Amit Sen',
            'email' => 'amitsen@test.in',
            'mobile_number' => '9870001111',
            'password' => Hash::make('Secret@123'),
            'role' => 'user',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $empA = Employee::create([
            'user_id' => $userA->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_id' => 'EMP-AMIT-01',
            'first_name' => 'Amit',
            'last_name' => 'Sen',
            'full_name' => 'Amit Sen',
            'personal_email' => 'amitsen@test.in',
            'official_email' => 'amitsen@test.in',
            'contact_number' => '9870001111',
            'date_of_joining' => Carbon::now()->subMonths(2),
            'status' => 'active',
            'basic_salary' => 35000,
            'gross_salary' => 35000,
            'take_home_salary' => 32000,
        ]);

        // User B
        $userB = User::create([
            'name' => 'Bikash Das',
            'email' => 'bikash@test.in',
            'mobile_number' => '9870002222',
            'password' => Hash::make('Secret@123'),
            'role' => 'user',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $empB = Employee::create([
            'user_id' => $userB->id,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employee_id' => 'EMP-BIKASH-02',
            'first_name' => 'Bikash',
            'last_name' => 'Das',
            'full_name' => 'Bikash Das',
            'personal_email' => 'bikash@test.in',
            'official_email' => 'bikash@test.in',
            'contact_number' => '9870002222',
            'date_of_joining' => Carbon::now()->subMonths(2),
            'status' => 'active',
            'basic_salary' => 40000,
            'gross_salary' => 40000,
            'take_home_salary' => 36000,
        ]);

        // Record attendance for both
        Attendance::create([
            'employee_id' => $empA->id,
            'company_id' => $this->company->id,
            'attendance_date' => Carbon::today(),
            'punch_in_at' => Carbon::today()->setTime(9, 30),
            'status' => 'present',
        ]);

        Attendance::create([
            'employee_id' => $empB->id,
            'company_id' => $this->company->id,
            'attendance_date' => Carbon::today(),
            'punch_in_at' => Carbon::today()->setTime(9, 45),
            'status' => 'present',
        ]);

        // When User A views /attendance:
        $response = $this->actingAs($userA)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertSee('My Attendance History');
        $response->assertSee('Amit Sen');
        $response->assertSee('EMP-AMIT-01');

        // Must NOT see User B's attendance
        $response->assertDontSee('Bikash Das');
        $response->assertDontSee('EMP-BIKASH-02');
    }
}
