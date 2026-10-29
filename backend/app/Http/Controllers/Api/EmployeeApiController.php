<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeApiController extends Controller
{
    /**
     * List all employees (for directory & manager selection)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Employee::with([
            'company:id,name,code',
            'department:id,name',
            'designation:id,name',
            'manager:id,full_name,employee_id',
        ]);

        if ($request->filled('company_id') && $request->input('company_id') !== 'all') {
            $query->where('company_id', $request->input('company_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('full_name')->get();

        return response()->json([
            'success' => true,
            'employees' => $employees,
        ]);
    }

    /**
     * Get single employee detail
     */
    public function show(Employee $employee): JsonResponse
    {
        $employee->load(['company', 'department', 'designation', 'manager', 'directReports', 'user']);

        return response()->json([
            'success' => true,
            'employee' => $employee,
        ]);
    }

    /**
     * Metadata: Companies, Departments, Designations & Managers
     */
    public function getMetadata(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'designations' => Designation::where('is_active', true)->orderBy('name')->get(),
            'managers' => Employee::where('status', 'active')->select('id', 'full_name', 'employee_id')->orderBy('full_name')->get(),
        ]);
    }

    /**
     * Real-time statutory salary calculator
     */
    public function calculateSalary(Request $request): JsonResponse
    {
        $basic = (float) $request->input('basic', 0);
        $pfEnabled = $request->boolean('pf_enabled', true);
        $tax = (float) $request->input('tax', 0);

        $breakdown = Employee::calculateSalaryBreakdown($basic, $pfEnabled, $tax);

        return response()->json([
            'success' => true,
            'breakdown' => $breakdown,
        ]);
    }
}
