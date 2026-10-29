<?php

namespace Tests\Feature;

use App\Mail\EmployeeOnboardedMail;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmployeeOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected \App\Models\Company $company;
    protected Department $department;
    protected Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = \App\Models\Company::create([
            'name' => 'Riangdo Venners Pvt. Ltd.',
            'code' => 'RVPL',
            'legal_type' => 'Private Limited',
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'company_id' => $this->company->id,
            'name' => 'Super Administrator',
            'email' => 'admin@hrms.in',
            'mobile_number' => '9876543210',
            'password' => Hash::make('Password@123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'is_active' => true,
        ]);

        $this->designation = Designation::create([
            'name' => 'Software Engineer',
            'code' => 'SWE',
            'is_active' => true,
        ]);
    }

    public function test_can_view_employee_directory_and_onboarding_form(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/employees');
        $response->assertStatus(200);
        $response->assertSee('Employee Directory');

        $createResponse = $this->actingAs($this->superAdmin)->get('/employees/create');
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Employee Onboarding Process');
        $createResponse->assertSee('Basic Salary');
    }

    public function test_onboard_employee_with_auto_calculated_salary_and_email(): void
    {
        Mail::fake();

        $data = [
            'company_id' => $this->company->id,
            'user_type' => 'user',
            'employee_id' => 'EMP-0100',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'full_name' => 'Amit Verma',
            'dob' => '1995-05-15',
            'gender' => 'Male',
            'marital_status' => 'Single',
            'blood_group' => 'B+',
            'father_or_husband_name' => 'Ramesh Verma',
            'personal_email' => 'amit.personal@gmail.com',
            'official_email' => 'amit.verma@hrms.in',
            'contact_number' => '9876543220',
            'present_street' => 'Tower 4, Sector 62',
            'present_city' => 'Noida',
            'present_state' => 'Uttar Pradesh',
            'present_pin' => '201301',
            'permanent_same_as_present' => 1,
            'primary_contact_name' => 'Ramesh Verma',
            'primary_contact_relationship' => 'Father',
            'primary_contact_phone' => '9876543221',
            'pan_number' => 'ABCDE1234F',
            'aadhar_number' => '123456789012',
            'account_holder_name' => 'Amit Verma',
            'bank_name' => 'HDFC Bank',
            'account_number' => '50100234567890',
            'ifsc_code' => 'HDFC0000128',
            'basic_salary' => 25000,
            'pf_enabled' => 1,
            'tax_amount' => 500,
        ];

        $response = $this->actingAs($this->superAdmin)->post('/employees', $data);
        $response->assertRedirect('/employees');

        // Verify User Account was created
        $this->assertDatabaseHas('users', [
            'email' => 'amit.verma@hrms.in',
            'mobile_number' => '9876543220',
            'role' => 'employee',
        ]);

        // Verify Employee Profile was created with calculated Indian salary breakdown
        // Basic: 25000
        // DA: 20% = 5000
        // HRA: 60% = 15000
        // CA: 20% = 5000
        // EA: 8% = 2000
        // SA: 20% = 5000
        // Gross = 25000 + 5000 + 15000 + 5000 + 2000 + 5000 = 57000
        // Gratuity = (25000+5000) * 4.81% = 1443
        // PF = min((25000+5000)*12%, 3000) = 3000
        // ESIC Gross > 21000 so ESIC = 0
        // Total Deductions = 3000
        // Tax = 500
        // Takehome = 57000 - 3000 - 500 = 53500
        $this->assertDatabaseHas('employees', [
            'employee_id' => 'EMP-0100',
            'full_name' => 'Amit Verma',
            'basic_salary' => 25000.00,
            'da_amount' => 5000.00,
            'hra_amount' => 15000.00,
            'ca_amount' => 5000.00,
            'ea_amount' => 2000.00,
            'sa_amount' => 5000.00,
            'gross_salary' => 57000.00,
            'gratuity_amount' => 1443.00,
            'pf_amount' => 3000.00,
            'total_deductions' => 3000.00,
            'tax_amount' => 500.00,
            'take_home_salary' => 53500.00,
        ]);

        Mail::assertSent(EmployeeOnboardedMail::class, function ($mail) {
            return $mail->hasTo('amit.verma@hrms.in');
        });
    }

    public function test_admin_can_reset_employee_password(): void
    {
        $user = User::create([
            'name' => 'Pooja Nair',
            'email' => 'pooja.nair@hrms.in',
            'mobile_number' => '9876543230',
            'password' => Hash::make('OldPassword@123'),
            'role' => 'employee',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_id' => 'EMP-0101',
            'user_type' => 'user',
            'full_name' => 'Pooja Nair',
            'contact_number' => '9876543230',
            'basic_salary' => 30000,
        ]);

        $response = $this->actingAs($this->superAdmin)->post("/employees/{$employee->id}/reset-password", [
            'new_password' => 'NewSecretPass@789',
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecretPass@789', $user->password));
    }

    public function test_rejects_duplicate_mobile_number_with_validation_error(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/employees', [
            'company_id' => $this->company->id,
            'user_type' => 'user',
            'full_name' => 'Duplicate Mobile User',
            'official_email' => 'duplicate@hrms.in',
            'contact_number' => '+91 9876543210', // Same as superAdmin mobile with +91 prefix
            'employee_id' => 'EMP-9999',
            'basic_salary' => 20000,
        ]);

        $response->assertSessionHasErrors(['contact_number']);
        $this->assertDatabaseMissing('users', ['email' => 'duplicate@hrms.in']);
    }

    public function test_can_switch_global_company_filter(): void
    {
        $company2 = \App\Models\Company::create([
            'name' => 'North East Veneers & Plywood LLP',
            'code' => 'NEVP',
            'legal_type' => 'LLP',
            'is_active' => true,
        ]);

        // Switch to Company 2
        $response = $this->actingAs($this->superAdmin)->post('/companies/switch', [
            'company_id' => $company2->id,
        ]);
        $response->assertSessionHas('active_company_id', $company2->id);

        // Switch to All Companies
        $responseAll = $this->actingAs($this->superAdmin)->post('/companies/switch', [
            'company_id' => 'all',
        ]);
        $responseAll->assertSessionMissing('active_company_id');
    }

    public function test_can_quick_store_company_via_ajax(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson('/companies/quick-store', [
            'name' => 'Assam Timber Products Pvt. Ltd.',
            'code' => 'ATPPL',
            'legal_type' => 'Private Limited',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('companies', ['name' => 'Assam Timber Products Pvt. Ltd.']);
    }

    public function test_can_update_employment_type_and_probation_details(): void
    {
        $user = User::create([
            'name' => 'Probation Test User',
            'email' => 'probation@hrms.in',
            'mobile' => '9876543299',
            'password' => Hash::make('Password@123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
            'employee_id' => 'EMP-7701',
            'full_name' => 'Probation Test User',
            'contact_number' => '9876543299',
            'official_email' => 'probation@hrms.in',
            'employment_type' => 'Probation',
            'probation_months' => 6,
            'joining_date' => '2026-01-01',
            'probation_end_date' => '2026-07-01',
            'status' => 'active',
            'basic_salary' => 25000,
        ]);

        // Update to Permanent with confirmation date
        $response = $this->actingAs($this->superAdmin)->put("/employees/{$employee->id}", [
            'company_id' => $this->company->id,
            'user_type' => 'user',
            'full_name' => 'Probation Test User Confirmed',
            'official_email' => 'probation@hrms.in',
            'employment_type' => 'Permanent',
            'confirmation_date' => '2026-07-01',
            'status' => 'active',
            'basic_salary' => 30000,
            'joining_date' => '2026-01-01',
            'contact_number' => '9876543299',
        ]);

        $response->assertRedirect("/employees/{$employee->id}");
        $employee->refresh();
        $this->assertEquals('Permanent', $employee->employment_type);
        $this->assertEquals('2026-07-01', $employee->confirmation_date?->format('Y-m-d'));
        $this->assertEquals(30000, $employee->basic_salary);

        // Update and disable statutory PF
        $responseNoPf = $this->actingAs($this->superAdmin)->put("/employees/{$employee->id}", [
            'company_id' => $this->company->id,
            'user_type' => 'user',
            'full_name' => 'Probation Test User Confirmed',
            'official_email' => 'probation@hrms.in',
            'employment_type' => 'Permanent',
            'status' => 'active',
            'basic_salary' => 30000,
            'contact_number' => '9876543299',
            'pf_enabled' => 0, // Unchecked / disabled
        ]);

        $responseNoPf->assertRedirect("/employees/{$employee->id}");
        $employee->refresh();
        $this->assertFalse((bool) $employee->pf_enabled);
        $this->assertEquals(0.0, (float) $employee->pf_amount);
    }

    public function test_can_toggle_employee_active_inactive_status(): void
    {
        $user = User::create([
            'name' => 'Toggle User',
            'email' => 'toggle@hrms.in',
            'mobile' => '9876543288',
            'password' => Hash::make('Password@123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
            'employee_id' => 'EMP-7702',
            'full_name' => 'Toggle User',
            'contact_number' => '9876543288',
            'official_email' => 'toggle@hrms.in',
            'status' => 'active',
            'basic_salary' => 25000,
        ]);

        // Toggle to inactive
        $response = $this->actingAs($this->superAdmin)->patch("/employees/{$employee->id}/toggle-status");
        $response->assertRedirect();

        $employee->refresh();
        $user->refresh();
        $this->assertEquals('inactive', $employee->status);
        $this->assertFalse((bool) $user->is_active);

        // Toggle back to active
        $this->actingAs($this->superAdmin)->patch("/employees/{$employee->id}/toggle-status");
        $employee->refresh();
        $user->refresh();
        $this->assertEquals('active', $employee->status);
        $this->assertTrue((bool) $user->is_active);
    }

    public function test_regular_admin_cannot_delete_employee(): void
    {
        $adminUser = User::create([
            'name' => 'Branch Admin',
            'email' => 'branchadmin@hrms.in',
            'mobile' => '9876543277',
            'password' => Hash::make('Password@123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $targetUser = User::create([
            'name' => 'Target User',
            'email' => 'target@hrms.in',
            'mobile' => '9876543266',
            'password' => Hash::make('Password@123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $targetEmployee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $targetUser->id,
            'employee_id' => 'EMP-7703',
            'full_name' => 'Target User',
            'contact_number' => '9876543266',
            'official_email' => 'target@hrms.in',
            'status' => 'active',
            'basic_salary' => 25000,
        ]);

        $response = $this->actingAs($adminUser)->delete("/employees/{$targetEmployee->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('employees', ['id' => $targetEmployee->id]);
    }

    public function test_superadmin_can_delete_employee(): void
    {
        $targetUser = User::create([
            'name' => 'Delete Target',
            'email' => 'delete_target@hrms.in',
            'mobile' => '9876543255',
            'password' => Hash::make('Password@123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        $targetEmployee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $targetUser->id,
            'employee_id' => 'EMP-7704',
            'full_name' => 'Delete Target',
            'contact_number' => '9876543255',
            'official_email' => 'delete_target@hrms.in',
            'status' => 'active',
            'basic_salary' => 25000,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete("/employees/{$targetEmployee->id}");
        $response->assertRedirect('/employees');
        $this->assertDatabaseMissing('employees', ['id' => $targetEmployee->id]);
        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }
}

