<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    /**
     * Search and list company employee directory
     * Accessible to all authenticated employees and administrators
     */
    public function index(Request $request)
    {
        $query = Employee::with([
            'department:id,name',
            'designation:id,title',
            'company:id,name',
            'location:id,name,city,state',
            'shift:id,name,start_time,end_time',
            'reportingManager:id,first_name,last_name,email,employee_id',
        ])
        ->where('status', 'active');

        // Search by Name, Employee ID, Email, Phone
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('employee_id', 'like', "%{$s}%")
                  ->orWhere('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        // Filter by Department
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $query->where('department_id', $request->department_id);
        }

        // Filter by Work Location
        if ($request->filled('location_id') && $request->location_id !== 'all') {
            $query->where('location_id', $request->location_id);
        }

        $employees = $query->orderBy('first_name', 'asc')
            ->select([
                'id',
                'user_id',
                'company_id',
                'department_id',
                'designation_id',
                'location_id',
                'shift_id',
                'reporting_manager_id',
                'employee_id',
                'first_name',
                'last_name',
                'email',
                'phone',
                'gender',
                'date_of_joining',
                'employment_type',
                'attendance_location_type',
                'status',
            ])
            ->get();

        return response()->json([
            'count' => $employees->count(),
            'employees' => $employees,
        ]);
    }
}

