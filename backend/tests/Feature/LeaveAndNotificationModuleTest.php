<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\InAppNotification;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LeaveAndNotificationModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Company $company;
    protected Employee $employee;
    protected User $employeeUser;
    protected LeaveType $casualLeave;
    protected LeaveType $sickLeave;

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

        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $this->employeeUser->id,
            'employee_id' => 'EMP-001',
            'user_type' => 'user',
            'employment_type' => 'Full-time',
            'full_name' => 'Koushi Deka',
            'personal_email' => 'koushikdeka300@gmail.com',
            'contact_number' => '916232247729',
            'basic_salary' => 45000.00,
            'status' => 'active',
        ]);

        $this->casualLeave = LeaveType::create([
            'name' => 'Casual Leave (CL)',
            'code' => 'CL',
            'default_annual_quota' => 12.0,
            'is_paid' => true,
            'is_active' => true,
        ]);

        $this->sickLeave = LeaveType::create([
            'name' => 'Sick Leave (SL)',
            'code' => 'SL',
            'default_annual_quota' => 10.0,
            'is_paid' => true,
            'is_active' => true,
        ]);

        // Initialize balances
        EmployeeLeaveBalance::ensureBalancesForEmployee($this->employee->id, (int) date('Y'));
    }

    public function test_leave_manager_index_accessible(): void
    {
        $resp = $this->actingAs($this->superAdmin)->get('/leave');
        $resp->assertStatus(200);
        $resp->assertSee('Leave Manager');
        $resp->assertSee('Casual Leave (CL)');
    }

    public function test_employee_can_apply_for_leave(): void
    {
        $resp = $this->actingAs($this->employeeUser)->post('/leave/apply', [
            'leave_type_id' => $this->casualLeave->id,
            'start_date' => Carbon::tomorrow()->format('Y-m-d'),
            'end_date' => Carbon::tomorrow()->addDays(2)->format('Y-m-d'), // 3 days
            'reason' => 'Family event in hometown',
        ]);

        $resp->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->casualLeave->id,
            'days_count' => 3.0,
            'status' => 'pending',
        ]);

        // Check pending days updated in balance
        $balance = EmployeeLeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->casualLeave->id)
            ->first();
        $this->assertEquals(3.0, (float) $balance->pending_days);

        // Check in-app notification generated for management
        $this->assertDatabaseHas('in_app_notifications', [
            'type' => 'leave_applied',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_admin_can_approve_pending_leave(): void
    {
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $this->employee->id,
            'company_id' => $this->company->id,
            'leave_type_id' => $this->casualLeave->id,
            'start_date' => Carbon::tomorrow()->format('Y-m-d'),
            'end_date' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'days_count' => 2.0,
            'reason' => 'Personal work',
            'status' => 'pending',
        ]);

        $resp = $this->actingAs($this->superAdmin)->post("/leave/{$leaveRequest->id}/action", [
            'action' => 'approve',
            'remarks' => 'Approved by admin',
        ]);

        $resp->assertRedirect();

        $leaveRequest->refresh();
        $this->assertEquals('approved', $leaveRequest->status);
        $this->assertEquals($this->superAdmin->id, $leaveRequest->actioned_by);

        // Check balance deductions
        $balance = EmployeeLeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->casualLeave->id)
            ->first();
        $this->assertEquals(2.0, (float) $balance->used_days);
        $this->assertEquals(10.0, (float) $balance->remaining_days);

        // Notification created for employee
        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $this->employeeUser->id,
            'type' => 'leave_actioned',
        ]);
    }

    public function test_superadmin_can_update_global_leave_policy_and_sync(): void
    {
        $resp = $this->actingAs($this->superAdmin)->post('/leave/global-policy', [
            'policies' => [
                [
                    'id' => $this->casualLeave->id,
                    'default_annual_quota' => 15.0,
                    'carry_forward' => 1,
                    'description' => 'Updated CL Policy',
                ],
                [
                    'id' => $this->sickLeave->id,
                    'default_annual_quota' => 12.0,
                    'carry_forward' => 1,
                    'description' => 'Updated SL Policy',
                ],
            ],
            'sync_existing_employees' => 1,
        ]);

        $resp->assertRedirect();

        $this->assertEquals(15.0, (float) $this->casualLeave->fresh()->default_annual_quota);
        $this->assertEquals(12.0, (float) $this->sickLeave->fresh()->default_annual_quota);

        // Synchronized with employee balance
        $balance = EmployeeLeaveBalance::where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->casualLeave->id)
            ->first();
        $this->assertEquals(15.0, (float) $balance->total_allocated);
        $this->assertEquals(15.0, (float) $balance->remaining_days);
    }

    public function test_notification_centre_endpoints(): void
    {
        InAppNotification::createNotification([
            'company_id' => $this->company->id,
            'type' => 'late_arrival',
            'title' => 'Late Arrival: Koushi Deka',
            'message' => 'Employee clocked in late by 25 mins',
            'icon' => 'clock',
            'color' => 'rose',
        ]);

        // Dropdown JSON
        $resp = $this->actingAs($this->superAdmin)->getJson('/notifications/dropdown');
        $resp->assertStatus(200);
        $resp->assertJsonFragment(['unread_count' => 1]);

        // Feed index page
        $pageResp = $this->actingAs($this->superAdmin)->get('/notifications');
        $pageResp->assertStatus(200);
        $pageResp->assertSee('Late Arrival: Koushi Deka');

        // Mark all read
        $readResp = $this->actingAs($this->superAdmin)->post('/notifications/read-all');
        $readResp->assertRedirect();

        $unreadCount = InAppNotification::where('is_read', false)->count();
        $this->assertEquals(0, $unreadCount);
    }
}
