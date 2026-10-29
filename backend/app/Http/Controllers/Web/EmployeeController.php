<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\EmployeeOnboardedMail;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    /**
     * Employee Directory
     */
    public function index(Request $request): View
    {
        $query = Employee::with(['company', 'department', 'designation', 'manager', 'user']);

        // Company filter: request query param overrides session active company
        $filterCompanyId = null;
        if ($request->filled('company_id')) {
            if ($request->input('company_id') !== 'all') {
                $filterCompanyId = (int) $request->input('company_id');
            }
        } elseif ($request->session()->has('active_company_id')) {
            $filterCompanyId = (int) $request->session()->get('active_company_id');
        }

        if ($filterCompanyId) {
            $query->where('company_id', $filterCompanyId);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%")
                  ->orWhere('personal_email', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('user_type')) {
            $query->where('user_type', $request->input('user_type'));
        }

        $employees = $query->orderBy('id', 'desc')->paginate(15);
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $designations = Designation::where('is_active', true)->orderBy('name')->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();

        return view('employees.index', compact('employees', 'departments', 'designations', 'companies', 'filterCompanyId'));
    }

    /**
     * Show Onboarding Form
     */
    public function create(): View
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $designations = Designation::where('is_active', true)->orderBy('name')->get();
        $managers = Employee::where('status', 'active')->orderBy('full_name')->get();
        $shifts = \App\Models\Shift::where('is_active', true)->orderBy('name')->get();
        $locations = \App\Models\Location::where('is_active', true)->orderBy('name')->get();

        // Auto-generate next sequential Employee ID e.g. EMP-0001
        $lastEmployee = Employee::orderBy('id', 'desc')->first();
        $nextNum = $lastEmployee ? ($lastEmployee->id + 1) : 1;
        $suggestedEmployeeId = 'EMP-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        return view('employees.create', compact('companies', 'departments', 'designations', 'managers', 'shifts', 'locations', 'suggestedEmployeeId'));
    }

    /**
     * Process Employee Onboarding
     */
    public function store(Request $request): RedirectResponse
    {
        // Pre-normalize Indian Mobile Number before validation to ensure reliable uniqueness checks
        if ($request->has('contact_number')) {
            $rawMobile = $request->input('contact_number');
            $cleanedMobile = preg_replace('/[^0-9]/', '', (string) $rawMobile);
            if (strlen($cleanedMobile) === 12 && str_starts_with($cleanedMobile, '91')) {
                $cleanedMobile = substr($cleanedMobile, 2);
            } elseif (strlen($cleanedMobile) === 11 && str_starts_with($cleanedMobile, '0')) {
                $cleanedMobile = substr($cleanedMobile, 1);
            }
            $request->merge(['contact_number' => $cleanedMobile]);
        }

        $validated = $request->validate([
            // Role & Org
            'company_id' => ['required', 'exists:companies,id'],
            'user_type' => ['required', 'string', 'in:admin,user'],
            'employment_type' => ['nullable', 'string', 'in:Probation,Permanent,Contract,Trainee,Intern'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'location_type' => ['nullable', 'string', 'in:fixed,random'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'joining_date' => ['nullable', 'date'],
            'probation_months' => ['nullable', 'integer', 'min:1', 'max:24'],

            // Section 1: Personal Details
            'full_name' => ['required', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'marital_status' => ['nullable', 'string'],
            'blood_group' => ['nullable', 'string'],
            'father_or_husband_name' => ['nullable', 'string', 'max:255'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'official_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'contact_number' => [
                'required',
                'digits:10',
                'unique:users,mobile_number',
                'unique:employees,contact_number',
            ],
            'alternate_number' => ['nullable', 'string', 'max:20'],
            'employee_id' => ['required', 'string', 'max:50', 'unique:employees,employee_id'],

            // Present Address
            'present_street' => ['nullable', 'string', 'max:255'],
            'present_city' => ['nullable', 'string', 'max:100'],
            'present_state' => ['nullable', 'string', 'max:100'],
            'present_pin' => ['nullable', 'string', 'max:15'],

            // Permanent Address
            'permanent_same_as_present' => ['nullable', 'boolean'],
            'permanent_street' => ['nullable', 'string', 'max:255'],
            'permanent_city' => ['nullable', 'string', 'max:100'],
            'permanent_state' => ['nullable', 'string', 'max:100'],
            'permanent_pin' => ['nullable', 'string', 'max:15'],

            // Section 2: Primary Emergency Contact
            'primary_contact_name' => ['nullable', 'string', 'max:255'],
            'primary_contact_relationship' => ['nullable', 'string', 'max:50'],
            'primary_contact_phone' => ['nullable', 'string', 'max:20'],
            'primary_contact_address' => ['nullable', 'string', 'max:500'],

            // Section 3: Statutory & Identity Details
            'pan_number' => ['nullable', 'string', 'max:20'],
            'pan_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'aadhar_number' => ['nullable', 'string', 'max:25'],
            'aadhar_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'uan_number' => ['nullable', 'string', 'max:30'],
            'esic_number' => ['nullable', 'string', 'max:30'],

            // Section 4: Bank Account Details
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'ifsc_code' => ['nullable', 'string', 'max:20'],
            'account_type' => ['nullable', 'string', 'in:Savings,Current'],
            'bank_proof_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],

            // Section 5: Educational Qualifications
            'education_degrees' => ['nullable', 'array'],
            'education_institutes' => ['nullable', 'array'],
            'education_years' => ['nullable', 'array'],
            'education_scores' => ['nullable', 'array'],

            // Section 6: Salary Breakdown
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'pf_enabled' => ['nullable', 'boolean'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
        ], [
            'company_id.required' => 'Please select the Company / Legal Entity for this employee.',
            'company_id.exists' => 'The selected company is invalid.',
            'contact_number.unique' => 'This mobile number is already registered in the system (e.g. by another employee or user). Please use a unique 10-digit mobile number.',
            'contact_number.digits' => 'Contact number must be a valid 10-digit Indian mobile number.',
            'official_email.unique' => 'This official email address is already registered to another account.',
            'employee_id.unique' => 'This Employee ID is already assigned. Please choose another ID.',
        ]);

        $cleanedMobile = $validated['contact_number'];

        // Compute Indian Statutory Salary
        $basic = (float) $validated['basic_salary'];
        $pfEnabled = $request->boolean('pf_enabled');
        $tax = (float) ($request->input('tax_amount') ?: 0);
        $salaryData = Employee::calculateSalaryBreakdown($basic, $pfEnabled, $tax);

        // Build education details JSON
        $educationDetails = [];
        if (!empty($request->input('education_degrees'))) {
            $degrees = $request->input('education_degrees');
            $institutes = $request->input('education_institutes', []);
            $years = $request->input('education_years', []);
            $scores = $request->input('education_scores', []);

            foreach ($degrees as $idx => $degree) {
                if (!empty(trim($degree))) {
                    $educationDetails[] = [
                        'degree' => trim($degree),
                        'institute' => $institutes[$idx] ?? '',
                        'year' => $years[$idx] ?? '',
                        'score' => $scores[$idx] ?? '',
                    ];
                }
            }
        }

        // File uploads
        $panDocPath = $request->hasFile('pan_document') ? $request->file('pan_document')->store('documents/pan', 'public') : null;
        $aadharDocPath = $request->hasFile('aadhar_document') ? $request->file('aadhar_document')->store('documents/aadhar', 'public') : null;
        $bankProofPath = $request->hasFile('bank_proof_document') ? $request->file('bank_proof_document')->store('documents/bank', 'public') : null;

        // Permanent address copy logic
        $sameAsPresent = $request->boolean('permanent_same_as_present');
        $permStreet = $sameAsPresent ? $validated['present_street'] : $request->input('permanent_street');
        $permCity = $sameAsPresent ? $validated['present_city'] : $request->input('permanent_city');
        $permState = $sameAsPresent ? $validated['present_state'] : $request->input('permanent_state');
        $permPin = $sameAsPresent ? $validated['present_pin'] : $request->input('permanent_pin');

        // Generate temporary secure password for employee
        $tempPassword = 'Emp@' . Str::random(6);

        DB::beginTransaction();
        try {
            // 1. Create User account for system access
            $user = User::create([
                'company_id' => $validated['company_id'],
                'name' => $validated['full_name'],
                'email' => strtolower($validated['official_email']),
                'mobile_number' => $cleanedMobile,
                'password' => Hash::make($tempPassword),
                'role' => ($validated['user_type'] === 'admin') ? 'admin' : 'employee',
                'designation' => Designation::find($validated['designation_id'] ?? null)?->name,
                'department' => Department::find($validated['department_id'] ?? null)?->name,
                'address' => $validated['present_street'],
                'city' => $validated['present_city'],
                'state' => $validated['present_state'],
                'pincode' => $validated['present_pin'],
                'is_active' => true,
            ]);

            // 2. Create Employee profile record
            $joiningDate = !empty($validated['joining_date']) ? \Carbon\Carbon::parse($validated['joining_date']) : now()->startOfDay();
            $empType = $validated['employment_type'] ?? 'Probation';
            $probMonths = (int) ($validated['probation_months'] ?? 6);
            $probEndDate = ($empType === 'Probation') ? (clone $joiningDate)->addMonths($probMonths) : null;

            $employee = Employee::create([
                'company_id' => $validated['company_id'],
                'user_id' => $user->id,
                'employee_id' => strtoupper(trim($validated['employee_id'])),
                'user_type' => $validated['user_type'],
                'employment_type' => $empType,
                'shift_id' => $validated['shift_id'] ?? null,
                'location_type' => $validated['location_type'] ?? 'fixed',
                'location_id' => (($validated['location_type'] ?? 'fixed') === 'fixed') ? ($validated['location_id'] ?? null) : null,
                'department_id' => $validated['department_id'] ?? null,
                'designation_id' => $validated['designation_id'] ?? null,
                'manager_id' => $validated['manager_id'] ?? null,
                'full_name' => $validated['full_name'],
                'dob' => $validated['dob'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'blood_group' => $validated['blood_group'] ?? null,
                'father_or_husband_name' => $validated['father_or_husband_name'] ?? null,
                'personal_email' => $validated['personal_email'] ?? null,
                'contact_number' => $cleanedMobile,
                'alternate_number' => $validated['alternate_number'] ?? null,

                'present_street' => $validated['present_street'] ?? null,
                'present_city' => $validated['present_city'] ?? null,
                'present_state' => $validated['present_state'] ?? null,
                'present_pin' => $validated['present_pin'] ?? null,

                'permanent_same_as_present' => $sameAsPresent,
                'permanent_street' => $permStreet,
                'permanent_city' => $permCity,
                'permanent_state' => $permState,
                'permanent_pin' => $permPin,

                'primary_contact_name' => $validated['primary_contact_name'] ?? null,
                'primary_contact_relationship' => $validated['primary_contact_relationship'] ?? null,
                'primary_contact_phone' => $validated['primary_contact_phone'] ?? null,
                'primary_contact_address' => $validated['primary_contact_address'] ?? null,

                'pan_number' => $validated['pan_number'] ? strtoupper(trim($validated['pan_number'])) : null,
                'pan_document' => $panDocPath,
                'aadhar_number' => $validated['aadhar_number'] ?? null,
                'aadhar_document' => $aadharDocPath,
                'uan_number' => $validated['uan_number'] ?? null,
                'esic_number' => $validated['esic_number'] ?? null,

                'account_holder_name' => $validated['account_holder_name'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_branch' => $validated['bank_branch'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'ifsc_code' => $validated['ifsc_code'] ? strtoupper(trim($validated['ifsc_code'])) : null,
                'account_type' => $validated['account_type'] ?? 'Savings',
                'bank_proof_document' => $bankProofPath,

                'education_details' => $educationDetails,

                // Salary Breakdown
                'basic_salary' => $salaryData['basic'],
                'da_amount' => $salaryData['da'],
                'hra_amount' => $salaryData['hra'],
                'ca_amount' => $salaryData['ca'],
                'ea_amount' => $salaryData['ea'],
                'sa_amount' => $salaryData['sa'],
                'gross_salary' => $salaryData['gross'],
                'gratuity_amount' => $salaryData['gratuity'],
                'pf_enabled' => $salaryData['pf_enabled'],
                'pf_amount' => $salaryData['pf'],
                'esic_enabled' => $salaryData['esic_enabled'],
                'esic_employer_amount' => $salaryData['esic_employer'],
                'esic_employee_amount' => $salaryData['esic_employee'],
                'ctc_amount' => $salaryData['ctc'],
                'total_deductions' => $salaryData['total_deductions'],
                'tax_amount' => $salaryData['tax'],
                'take_home_salary' => $salaryData['take_home'],

                'joining_date' => $joiningDate,
                'probation_months' => $probMonths,
                'probation_end_date' => $probEndDate,
                'status' => 'active',
            ]);

            DB::commit();

            // 3. Dispatch Email with credentials
            try {
                Mail::to($user->email)->send(new EmployeeOnboardedMail($employee, $user, $tempPassword));
            } catch (\Exception $e) {
                // If mail gateway is unconfigured or in offline dev, log without breaking onboarding
                logger()->warning('Onboarding email dispatch failed: ' . $e->getMessage());
            }

            return redirect()->route('employees.index')->with('success', "Employee {$employee->full_name} ({$employee->employee_id}) onboarded successfully! Login credentials dispatched to {$user->email}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['onboarding_error' => 'Failed to onboard employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Show Employee Profile
     */
    public function show(Employee $employee): View
    {
        $employee->load(['company', 'department', 'designation', 'manager', 'directReports', 'user']);
        return view('employees.show', compact('employee'));
    }

    /**
     * Show Employee Edit Form
     */
    public function edit(Employee $employee): View
    {
        $employee->load(['company', 'department', 'designation', 'manager', 'shift', 'location', 'user']);
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $designations = Designation::where('is_active', true)->orderBy('name')->get();
        $managers = Employee::where('status', 'active')->where('id', '!=', $employee->id)->orderBy('full_name')->get();
        $shifts = \App\Models\Shift::where('is_active', true)->orderBy('name')->get();
        $locations = \App\Models\Location::where('is_active', true)->orderBy('name')->get();

        return view('employees.edit', compact('employee', 'companies', 'departments', 'designations', 'managers', 'shifts', 'locations'));
    }

    /**
     * Update Employee Profile
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'user_type' => ['required', 'string', 'in:admin,user'],
            'employment_type' => ['required', 'string', 'in:Probation,Permanent,Contract,Trainee,Intern'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'location_type' => ['nullable', 'string', 'in:fixed,random'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'status' => ['required', 'string', 'in:active,inactive,probation,terminated,resigned'],
            'joining_date' => ['nullable', 'date'],
            'probation_months' => ['nullable', 'integer', 'min:1', 'max:24'],
            'probation_end_date' => ['nullable', 'date'],
            'confirmation_date' => ['nullable', 'date'],
            'exit_date' => ['nullable', 'date'],
            'exit_reason' => ['nullable', 'string', 'max:255'],

            // Personal
            'full_name' => ['required', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'marital_status' => ['nullable', 'string'],
            'blood_group' => ['nullable', 'string'],
            'father_or_husband_name' => ['nullable', 'string', 'max:255'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'official_email' => ['required', 'email', 'max:255', 'unique:users,email,' . ($employee->user_id ?? 0)],
            'contact_number' => ['required', 'digits:10', 'unique:employees,contact_number,' . $employee->id],
            'alternate_number' => ['nullable', 'string', 'max:20'],

            // Address
            'present_street' => ['nullable', 'string', 'max:255'],
            'present_city' => ['nullable', 'string', 'max:100'],
            'present_state' => ['nullable', 'string', 'max:100'],
            'present_pin' => ['nullable', 'string', 'max:15'],
            'permanent_street' => ['nullable', 'string', 'max:255'],
            'permanent_city' => ['nullable', 'string', 'max:100'],
            'permanent_state' => ['nullable', 'string', 'max:100'],
            'permanent_pin' => ['nullable', 'string', 'max:15'],

            // Emergency
            'primary_contact_name' => ['nullable', 'string', 'max:255'],
            'primary_contact_relationship' => ['nullable', 'string', 'max:50'],
            'primary_contact_phone' => ['nullable', 'string', 'max:20'],
            'primary_contact_address' => ['nullable', 'string', 'max:500'],

            // Statutory
            'pan_number' => ['nullable', 'string', 'max:20'],
            'pan_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'aadhar_number' => ['nullable', 'string', 'max:25'],
            'aadhar_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'uan_number' => ['nullable', 'string', 'max:30'],
            'esic_number' => ['nullable', 'string', 'max:30'],

            // Bank
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'ifsc_code' => ['nullable', 'string', 'max:20'],
            'account_type' => ['nullable', 'string', 'in:Savings,Current'],
            'bank_proof_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],

            // Salary
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'pf_enabled' => ['nullable', 'boolean'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        // File uploads if new files submitted
        $panDocPath = $request->hasFile('pan_document') ? $request->file('pan_document')->store('documents/pan', 'public') : $employee->pan_document;
        $aadharDocPath = $request->hasFile('aadhar_document') ? $request->file('aadhar_document')->store('documents/aadhar', 'public') : $employee->aadhar_document;
        $bankProofPath = $request->hasFile('bank_proof_document') ? $request->file('bank_proof_document')->store('documents/bank', 'public') : $employee->bank_proof_document;

        // Recompute Salary Breakdown
        $basic = (float) $validated['basic_salary'];
        $pfEnabled = $request->boolean('pf_enabled');
        $tax = (float) ($request->input('tax_amount') ?: 0);
        $salaryData = Employee::calculateSalaryBreakdown($basic, $pfEnabled, $tax);

        // Probation logic
        $joiningDate = !empty($validated['joining_date']) ? \Carbon\Carbon::parse($validated['joining_date']) : $employee->joining_date;
        $probMonths = (int) ($validated['probation_months'] ?? $employee->probation_months ?? 6);
        $probEndDate = !empty($validated['probation_end_date']) 
            ? \Carbon\Carbon::parse($validated['probation_end_date']) 
            : (($validated['employment_type'] === 'Probation' && $joiningDate) ? (clone $joiningDate)->addMonths($probMonths) : null);

        // Update User account
        if ($employee->user) {
            $employee->user->update([
                'company_id' => $validated['company_id'],
                'name' => $validated['full_name'],
                'email' => strtolower($validated['official_email']),
                'mobile_number' => $validated['contact_number'],
                'role' => ($validated['user_type'] === 'admin') ? 'admin' : 'employee',
                'designation' => Designation::find($validated['designation_id'] ?? null)?->name,
                'department' => Department::find($validated['department_id'] ?? null)?->name,
                'is_active' => ($validated['status'] === 'active' || $validated['status'] === 'probation'),
            ]);
        }

        // Update Employee Record
        $employee->update([
            'company_id' => $validated['company_id'],
            'user_type' => $validated['user_type'],
            'employment_type' => $validated['employment_type'],
            'shift_id' => $validated['shift_id'] ?? $employee->shift_id,
            'location_type' => $validated['location_type'] ?? $employee->location_type ?? 'fixed',
            'location_id' => (($validated['location_type'] ?? $employee->location_type ?? 'fixed') === 'fixed') 
                ? ($validated['location_id'] ?? $employee->location_id) 
                : null,
            'department_id' => $validated['department_id'] ?? null,
            'designation_id' => $validated['designation_id'] ?? null,
            'manager_id' => $validated['manager_id'] ?? null,
            'status' => $validated['status'],
            'joining_date' => $joiningDate,
            'probation_months' => $probMonths,
            'probation_end_date' => $probEndDate,
            'confirmation_date' => $validated['confirmation_date'] ?? null,
            'exit_date' => $validated['exit_date'] ?? null,
            'exit_reason' => $validated['exit_reason'] ?? null,

            'full_name' => $validated['full_name'],
            'dob' => $validated['dob'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'marital_status' => $validated['marital_status'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
            'father_or_husband_name' => $validated['father_or_husband_name'] ?? null,
            'personal_email' => $validated['personal_email'] ?? null,
            'contact_number' => $validated['contact_number'],
            'alternate_number' => $validated['alternate_number'] ?? null,

            'present_street' => $validated['present_street'] ?? null,
            'present_city' => $validated['present_city'] ?? null,
            'present_state' => $validated['present_state'] ?? null,
            'present_pin' => $validated['present_pin'] ?? null,
            'permanent_street' => $validated['permanent_street'] ?? null,
            'permanent_city' => $validated['permanent_city'] ?? null,
            'permanent_state' => $validated['permanent_state'] ?? null,
            'permanent_pin' => $validated['permanent_pin'] ?? null,

            'primary_contact_name' => $validated['primary_contact_name'] ?? null,
            'primary_contact_relationship' => $validated['primary_contact_relationship'] ?? null,
            'primary_contact_phone' => $validated['primary_contact_phone'] ?? null,
            'primary_contact_address' => $validated['primary_contact_address'] ?? null,

            'pan_number' => !empty($validated['pan_number']) ? strtoupper(trim($validated['pan_number'])) : null,
            'pan_document' => $panDocPath,
            'aadhar_number' => $validated['aadhar_number'] ?? null,
            'aadhar_document' => $aadharDocPath,
            'uan_number' => $validated['uan_number'] ?? null,
            'esic_number' => $validated['esic_number'] ?? null,

            'account_holder_name' => $validated['account_holder_name'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_branch' => $validated['bank_branch'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'ifsc_code' => !empty($validated['ifsc_code']) ? strtoupper(trim($validated['ifsc_code'])) : null,
            'account_type' => $validated['account_type'] ?? 'Savings',
            'bank_proof_document' => $bankProofPath,

            'basic_salary' => $salaryData['basic'],
            'da_amount' => $salaryData['da'],
            'hra_amount' => $salaryData['hra'],
            'ca_amount' => $salaryData['ca'],
            'ea_amount' => $salaryData['ea'],
            'sa_amount' => $salaryData['sa'],
            'gross_salary' => $salaryData['gross'],
            'gratuity_amount' => $salaryData['gratuity'],
            'pf_enabled' => $salaryData['pf_enabled'],
            'pf_amount' => $salaryData['pf'],
            'esic_enabled' => $salaryData['esic_enabled'],
            'esic_employer_amount' => $salaryData['esic_employer'],
            'esic_employee_amount' => $salaryData['esic_employee'],
            'ctc_amount' => $salaryData['ctc'],
            'total_deductions' => $salaryData['total_deductions'],
            'tax_amount' => $salaryData['tax'],
            'take_home_salary' => $salaryData['take_home'],
        ]);

        return redirect()->route('employees.show', $employee->id)->with('success', "Employee profile for {$employee->full_name} ({$employee->employee_id}) updated successfully!");
    }

    /**
     * Mark Employee Active or Inactive
     */
    public function toggleStatus(Request $request, Employee $employee): RedirectResponse
    {
        $newStatus = ($employee->status === 'active') ? 'inactive' : 'active';
        $employee->update(['status' => $newStatus]);

        if ($employee->user) {
            $employee->user->update(['is_active' => ($newStatus === 'active')]);
        }

        $msg = ($newStatus === 'active') 
            ? "Employee {$employee->full_name} marked as ACTIVE. Portal login enabled." 
            : "Employee {$employee->full_name} marked as INACTIVE. Portal login suspended.";

        return back()->with('success', $msg);
    }

    /**
     * Secure Document Viewer / Downloader
     */
    public function viewDocument(Employee $employee, string $type)
    {
        $path = match ($type) {
            'pan' => $employee->pan_document,
            'aadhar' => $employee->aadhar_document,
            'bank' => $employee->bank_proof_document,
            default => null,
        };

        if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            abort(404, 'Attached document not found.');
        }

        $fullPath = storage_path('app/public/' . $path);
        return response()->file($fullPath);
    }

    /**
     * Delete Employee (Superadmin Only)
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        // Strictly check if authenticated user is Superadmin
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only Super Administrators can delete employee records.');
        }

        $name = $employee->full_name;
        $empId = $employee->employee_id;

        DB::beginTransaction();
        try {
            // Delete linked user account if exists
            if ($employee->user) {
                $employee->user->tokens()->delete(); // revoke all Sanctum tokens
                $employee->user->delete();
            }

            // Delete employee record
            $employee->delete();

            DB::commit();

            return redirect()->route('employees.index')->with('success', "Employee {$name} ({$empId}) and linked user account permanently deleted.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Admin/Superadmin Reset Employee Password
     */
    public function resetPassword(Request $request, Employee $employee): RedirectResponse
    {
        $request->validate([
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        if ($employee->user) {
            $employee->user->update([
                'password' => Hash::make($request->input('new_password')),
            ]);

            return back()->with('success', "Password for {$employee->full_name} ({$employee->employee_id}) has been reset successfully!");
        }

        return back()->withErrors(['error' => 'No active user account found linked to this employee.']);
    }

    /**
     * AJAX Endpoint: Quick Add Department
     */
    public function quickStoreDepartment(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:100|unique:departments,name']);
        $dept = Department::create([
            'name' => trim($request->input('name')),
            'is_active' => true,
        ]);
        return response()->json(['success' => true, 'department' => $dept]);
    }

    /**
     * AJAX Endpoint: Quick Add Designation
     */
    public function quickStoreDesignation(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:100|unique:designations,name']);
        $desig = Designation::create([
            'name' => trim($request->input('name')),
            'is_active' => true,
        ]);
        return response()->json(['success' => true, 'designation' => $desig]);
    }

    /**
     * AJAX Endpoint: Calculate Salary Breakdown in Real-time
     */
    public function calculateSalary(Request $request): JsonResponse
    {
        $basic = (float) $request->input('basic', 0);
        $pfEnabled = $request->boolean('pf_enabled', true);
        $tax = (float) $request->input('tax', 0);

        $breakdown = Employee::calculateSalaryBreakdown($basic, $pfEnabled, $tax);
        return response()->json(['success' => true, 'breakdown' => $breakdown]);
    }
}
