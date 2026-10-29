<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactDirectoryController extends Controller
{
    /**
     * Display Company Contact & Department Directory
     */
    public function index(Request $request): View
    {
        $activeCompanyId = session('active_company_id');

        $query = Employee::with(['department', 'designation', 'company', 'user', 'manager', 'location'])
            ->where('status', 'active');

        if ($activeCompanyId) {
            $query->where('company_id', $activeCompanyId);
        }

        // Search by Employee Name, Employee ID, Email, Phone
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%")
                  ->orWhere('alternate_number', 'like', "%{$search}%")
                  ->orWhere('personal_email', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Department
        if ($request->filled('department_id')) {
            $query->where('department_id', (int) $request->input('department_id'));
        }

        // Filter by Company Entity
        if ($request->filled('company_id') && $request->input('company_id') !== 'all') {
            $query->where('company_id', (int) $request->input('company_id'));
        }

        // Filter by Work Location
        if ($request->filled('location_id')) {
            $query->where('location_id', (int) $request->input('location_id'));
        }

        $contacts = $query->orderBy('full_name', 'asc')->paginate(24)->withQueryString();

        // Department statistics for department-wise view tabs / sidebar
        $deptQuery = Department::withCount(['employees' => function ($eq) use ($activeCompanyId) {
            $eq->where('status', 'active');
            if ($activeCompanyId) {
                $eq->where('company_id', $activeCompanyId);
            }
        }])->orderBy('name');

        $departments = $deptQuery->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $locations = \App\Models\Location::where('is_active', true)->orderBy('name')->get();

        $totalContacts = Employee::where('status', 'active')
            ->when($activeCompanyId, fn($q) => $q->where('company_id', $activeCompanyId))
            ->count();

        return view('contacts.index', compact(
            'contacts',
            'departments',
            'companies',
            'locations',
            'totalContacts'
        ));
    }
}
