<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DepartmentDesignationController extends Controller
{
    /**
     * Delete department (AJAX or Redirect)
     */
    public function destroyDepartment(Department $department): JsonResponse|RedirectResponse
    {
        $count = $department->employees()->count();
        if ($count > 0) {
            $msg = "Cannot delete department '{$department->name}' because {$count} employee(s) are assigned to it. Reassign employees first.";
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['error' => $msg]);
        }

        $name = $department->name;
        $department->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Department '{$name}' deleted successfully."]);
        }
        return back()->with('success', "Department '{$name}' deleted successfully.");
    }

    /**
     * Delete designation (AJAX or Redirect)
     */
    public function destroyDesignation(Designation $designation): JsonResponse|RedirectResponse
    {
        $count = $designation->employees()->count();
        if ($count > 0) {
            $msg = "Cannot delete designation '{$designation->name}' because {$count} employee(s) are assigned to it. Reassign employees first.";
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['error' => $msg]);
        }

        $name = $designation->name;
        $designation->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => "Designation '{$name}' deleted successfully."]);
        }
        return back()->with('success', "Designation '{$name}' deleted successfully.");
    }

    /**
     * Get list of departments for dropdowns & management
     */
    public function listDepartments(): JsonResponse
    {
        $departments = Department::withCount('employees')->orderBy('name')->get();
        return response()->json(['success' => true, 'departments' => $departments]);
    }

    /**
     * Get list of designations for dropdowns & management
     */
    public function listDesignations(): JsonResponse
    {
        $designations = Designation::withCount('employees')->orderBy('name')->get();
        return response()->json(['success' => true, 'designations' => $designations]);
    }
}
