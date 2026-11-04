<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * Get auto-generated unique Employee ID based on company or current year.
     */
    public function generateId(): JsonResponse
    {
        $year = date('Y');
        $prefix = "EMP-{$year}-";

        // Find last issued ID
        $lastEmployee = Employee::where('employee_id', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_id, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return response()->json([
            'employee_id' => $prefix . $newNumber,
        ]);
    }

    /**
     * Helper calculation preview endpoint for real-time frontend calculations.
     */
    public function calculateSalary(Request $request): JsonResponse
    {
        $basicSalary = (float) $request->input('basic_salary', 0);
        $monthlyTax = (float) $request->input('monthly_tax', 0);
        $enableEpf = filter_var($request->input('enable_epf', true), FILTER_VALIDATE_BOOLEAN);

        $breakdown = Employee::calculateSalaryBreakdown($basicSalary, $monthlyTax, $enableEpf);

        return response()->json([
            'breakdown' => $breakdown,
        ]);
    }

    /**
     * Display a listing of employees with filtering, searching, and relational details.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Employee::with([
            'company:id,name',
            'department:id,name',
            'designation:id,name',
            'shift:id,name,start_time,end_time',
            'location:id,name,city,country',
            'reportingManager:id,first_name,last_name,employee_id',
        ]);

        // Search by name, email, employee ID, or phone
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('designation_id')) {
            $query->where('designation_id', $request->designation_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('usertype')) {
            $query->where('usertype', $request->usertype);
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 50));

        return response()->json($employees);
    }

    /**
     * Store a newly created employee with all onboarded profiles.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Primary Onboarding & Roles
            'company_id' => ['required', 'exists:companies,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'attendance_location_type' => ['required', 'in:fixed,random'],
            'location_id' => [
                Rule::requiredIf(fn () => $request->attendance_location_type === 'fixed'),
                'nullable',
                'exists:locations,id',
            ],
            'reporting_manager_id' => ['nullable', 'exists:employees,id'],
            'employee_id' => ['required', 'string', 'max:50', 'unique:employees,employee_id'],
            'usertype' => ['required', 'in:user,admin'],
            'date_of_joining' => ['required', 'date'],
            'employment_type' => ['required', 'string'],
            'probation_duration' => ['required', 'string'],

            // Personal Information
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'marital_status' => ['nullable', 'in:Single,Married,Divorced,Widowed'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'current_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],

            // Emergency Contacts
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],

            // Statutory & Identity Details
            'pan_number' => ['nullable', 'string', 'max:50'],
            'aadhaar_number' => ['nullable', 'string', 'max:50'],
            'uan_number' => ['nullable', 'string', 'max:50'],
            'esic_number' => ['nullable', 'string', 'max:50'],
            'passport_number' => ['nullable', 'string', 'max:50'],

            // Bank Account Details
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_ifsc_code' => ['nullable', 'string', 'max:50'],
            'bank_branch' => ['nullable', 'string', 'max:100'],

            // Educational Qualifications
            'qualifications' => ['nullable', 'array'],

            // Compensation
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'monthly_tax' => ['nullable', 'numeric', 'min:0'],
            'enable_epf' => ['boolean'],

            // Initial Password for user portal account
            'portal_password' => ['nullable', 'string', 'min:6'],
        ]);

        try {
            DB::beginTransaction();

            // 1. Calculate full salary breakdown
            $breakdown = Employee::calculateSalaryBreakdown(
                (float) $validated['basic_salary'],
                (float) ($validated['monthly_tax'] ?? 0),
                filter_var($validated['enable_epf'] ?? true, FILTER_VALIDATE_BOOLEAN)
            );

            // 2. Create User account for employee login access
            $defaultPassword = $request->input('portal_password', 'Welcome@123');
            $fullName = trim("{$validated['first_name']} " . ($validated['last_name'] ?? ''));

            $user = User::create([
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => Hash::make($defaultPassword),
                'role' => $validated['usertype'] === 'admin' ? 'admin' : 'employee',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // 3. Create Employee Record merged with breakdown & user_id
            $employeeData = array_merge($validated, $breakdown, [
                'user_id' => $user->id,
            ]);
            unset($employeeData['portal_password']);

            $employee = Employee::create($employeeData);

            DB::commit();

            return response()->json([
                'message' => 'Employee onboarded successfully.',
                'employee' => $employee->load([
                    'company:id,name',
                    'department:id,name',
                    'designation:id,name',
                    'shift:id,name',
                    'location:id,name',
                    'reportingManager:id,first_name,last_name,employee_id',
                ]),
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to onboard employee: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a specific employee record with full details.
     */
    public function show(Employee $employee): JsonResponse
    {
        return response()->json([
            'employee' => $employee->load([
                'company:id,name,currency,timezone',
                'department:id,name',
                'designation:id,name',
                'shift:id,name,start_time,end_time,grace_period_minutes',
                'location:id,name,city,country,radius_meters',
                'user:id,email,role,is_active',
            ]),
        ]);
    }

    /**
     * Update existing employee details & recompute salary breakdown.
     */
    public function update(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'attendance_location_type' => ['required', 'in:fixed,random'],
            'location_id' => [
                Rule::requiredIf(fn () => $request->attendance_location_type === 'fixed'),
                'nullable',
                'exists:locations,id',
            ],
            'reporting_manager_id' => ['nullable', 'exists:employees,id'],
            'employee_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_id')->ignore($employee->id),
            ],
            'usertype' => ['required', 'in:user,admin'],
            'date_of_joining' => ['required', 'date'],
            'employment_type' => ['required', 'string'],
            'probation_duration' => ['required', 'string'],
            'status' => ['required', 'in:active,probation,notice,terminated,resigned'],

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employee->id),
                Rule::unique('users', 'email')->ignore($employee->user_id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'marital_status' => ['nullable', 'in:Single,Married,Divorced,Widowed'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'current_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],

            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],

            'pan_number' => ['nullable', 'string', 'max:50'],
            'aadhaar_number' => ['nullable', 'string', 'max:50'],
            'uan_number' => ['nullable', 'string', 'max:50'],
            'esic_number' => ['nullable', 'string', 'max:50'],
            'passport_number' => ['nullable', 'string', 'max:50'],

            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_ifsc_code' => ['nullable', 'string', 'max:50'],
            'bank_branch' => ['nullable', 'string', 'max:100'],

            'qualifications' => ['nullable', 'array'],

            'basic_salary' => ['required', 'numeric', 'min:0'],
            'monthly_tax' => ['nullable', 'numeric', 'min:0'],
            'enable_epf' => ['boolean'],

            'portal_password' => ['nullable', 'string', 'min:6'],
        ]);

        try {
            DB::beginTransaction();

            // Recompute salary snapshot
            $breakdown = Employee::calculateSalaryBreakdown(
                (float) $validated['basic_salary'],
                (float) ($validated['monthly_tax'] ?? 0),
                filter_var($validated['enable_epf'] ?? true, FILTER_VALIDATE_BOOLEAN)
            );

            // Sync User account
            if ($employee->user) {
                $userData = [
                    'name' => trim("{$validated['first_name']} " . ($validated['last_name'] ?? '')),
                    'email' => $validated['email'],
                    'role' => $validated['usertype'] === 'admin' ? 'admin' : 'employee',
                ];
                if (!empty($validated['portal_password'])) {
                    $userData['password'] = Hash::make($validated['portal_password']);
                }
                $employee->user->update($userData);
            }

            $updateData = array_merge($validated, $breakdown);
            unset($updateData['portal_password']);

            $employee->update($updateData);

            DB::commit();

            return response()->json([
                'message' => 'Employee details updated successfully.',
                'employee' => $employee->load([
                    'company:id,name',
                    'department:id,name',
                    'designation:id,name',
                    'shift:id,name',
                    'location:id,name',
                    'reportingManager:id,first_name,last_name,employee_id',
                ]),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update employee: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an employee record and associated portal account.
     */
    public function destroy(Employee $employee): JsonResponse
    {
        try {
            DB::beginTransaction();
            if ($employee->user) {
                $employee->user->delete();
            }
            $employee->delete();
            DB::commit();

            return response()->json([
                'message' => 'Employee record and portal account deleted successfully.',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete employee: ' . $e->getMessage(),
            ], 500);
        }
    }
}

