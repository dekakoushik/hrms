<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\AppNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveController extends Controller
{
    /**
     * Get Leave Types list
     */
    public function getLeaveTypes(Request $request): JsonResponse
    {
        $types = LeaveType::where('is_active', true)->orderBy('name')->get();
        return response()->json($types);
    }

    /**
     * Create or update a Leave Type (Admin / Superadmin)
     */
    public function storeLeaveType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:leave_types,code,' . $request->id,
            'description' => 'nullable|string',
            'default_days_per_year' => 'required|numeric|min:0',
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
        ]);

        $leaveType = LeaveType::updateOrCreate(['id' => $request->id], $validated);

        return response()->json([
            'message' => 'Leave type saved successfully.',
            'data' => $leaveType,
        ]);
    }

    /**
     * Get user's employee leave summary & history
     */
    public function myLeaveOverview(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee && $user->role === 'superadmin') {
            // Pick primary or first employee record for superadmin self testing
            $employee = Employee::first();
        }

        if (!$employee) {
            return response()->json([
                'employee' => null,
                'balances' => [],
                'recent_applications' => [],
                'upcoming_holidays' => Holiday::where('date', '>=', now()->toDateString())->orderBy('date')->take(5)->get(),
            ]);
        }

        $currentYear = (int) now()->year;

        // Auto-seed default balances if not yet created for this year
        $activeTypes = LeaveType::where('is_active', true)->get();
        foreach ($activeTypes as $type) {
            LeaveBalance::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'year' => $currentYear,
                ],
                [
                    'allocated_days' => $type->default_days_per_year,
                    'used_days' => 0,
                    'pending_days' => 0,
                    'remaining_days' => $type->default_days_per_year,
                ]
            );
        }

        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->get();

        $applications = LeaveApplication::with(['leaveType', 'actionByUser'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->take(20)
            ->get();

        $holidays = Holiday::where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->take(5)
            ->get();

        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'name' => "{$employee->first_name} {$employee->last_name}",
                'employee_id' => $employee->employee_id,
            ],
            'current_year' => $currentYear,
            'balances' => $balances,
            'recent_applications' => $applications,
            'upcoming_holidays' => $holidays,
        ]);
    }

    /**
     * Employee submits a Leave Application
     */
    public function applyLeave(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee && $user->role === 'superadmin') {
            $employee = Employee::first();
        }

        if (!$employee) {
            return response()->json(['message' => 'No employee profile linked to your user account.'], 403);
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_half_day' => 'boolean',
            'half_day_type' => 'nullable|in:first_half,second_half',
            'reason' => 'required|string|min:3|max:1000',
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);

        if (!empty($validated['is_half_day'])) {
            $days = 0.5;
            $end = $start->copy();
        } else {
            // Count business calendar days (start to end inclusive)
            $days = $start->diffInDays($end) + 1;
        }

        $currentYear = $start->year;

        // Check Leave Balance
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $currentYear)
            ->first();

        if (!$balance) {
            $type = LeaveType::find($validated['leave_type_id']);
            $balance = LeaveBalance::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'year' => $currentYear,
                'allocated_days' => $type->default_days_per_year,
                'used_days' => 0,
                'pending_days' => 0,
                'remaining_days' => $type->default_days_per_year,
            ]);
        }

        if ($balance->remaining_days < $days) {
            return response()->json([
                'message' => "Insufficient leave balance. Requested {$days} day(s), but only {$balance->remaining_days} day(s) remain.",
            ], 422);
        }

        DB::transaction(function () use ($employee, $validated, $start, $end, $days, $balance, $type) {
            $application = LeaveApplication::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'total_days' => $days,
                'is_half_day' => $validated['is_half_day'] ?? false,
                'half_day_type' => $validated['half_day_type'] ?? null,
                'reason' => $validated['reason'],
                'status' => 'pending',
            ]);

            // Increment pending days and adjust remaining
            $balance->pending_days += $days;
            $balance->recalculateRemaining();

            // Send notification to all Administrators and Superadmins
            AppNotification::sendToAdmins(
                'leave_applied',
                "New Leave Application: {$employee->name}",
                "{$employee->name} ({$employee->employee_code}) submitted a {$days}-day {$type->name} request ({$start->format('M d')} to {$end->format('M d')}).",
                '/leaves',
                'calendar',
                [
                    'application_id' => $application->id,
                    'employee_id' => $employee->id,
                    'leave_type' => $type->name,
                    'days' => $days,
                ]
            );
        });

        return response()->json([
            'message' => 'Leave application submitted successfully for review.',
        ], 201);
    }

    /**
     * Admin/Superadmin: List all leave applications across the company
     */
    public function adminApplications(Request $request): JsonResponse
    {
        $query = LeaveApplication::with(['employee.designation', 'employee.department', 'leaveType', 'actionByUser'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
            ->orderByDesc('id');

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('employee_id') && $request->employee_id !== '') {
            $query->where('employee_id', $request->employee_id);
        }

        $applications = $query->paginate($request->get('per_page', 25));

        $stats = [
            'pending_count' => LeaveApplication::where('status', 'pending')->count(),
            'approved_this_month' => LeaveApplication::where('status', 'approved')
                ->whereMonth('start_date', now()->month)
                ->whereYear('start_date', now()->year)
                ->count(),
            'rejected_count' => LeaveApplication::where('status', 'rejected')->count(),
            'active_employees' => Employee::count(),
        ];

        return response()->json([
            'stats' => $stats,
            'applications' => $applications,
        ]);
    }

    /**
     * Admin/Superadmin: Approve or Reject Leave Application
     */
    public function updateApplicationStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,cancelled',
            'admin_remarks' => 'nullable|string|max:500',
        ]);

        $app = LeaveApplication::with(['leaveType', 'employee.user'])->findOrFail($id);

        if ($app->status !== 'pending' && $app->status === $validated['status']) {
            return response()->json(['message' => "Application is already marked as {$app->status}."], 422);
        }

        $startYear = Carbon::parse($app->start_date)->year;

        DB::transaction(function () use ($app, $validated, $request, $startYear) {
            $balance = LeaveBalance::where('employee_id', $app->employee_id)
                ->where('leave_type_id', $app->leave_type_id)
                ->where('year', $startYear)
                ->first();

            $prevStatus = $app->status;
            $newStatus = $validated['status'];

            // Adjust balances
            if ($balance) {
                if ($prevStatus === 'pending') {
                    $balance->pending_days = max(0, $balance->pending_days - $app->total_days);
                }

                if ($newStatus === 'approved') {
                    $balance->used_days += $app->total_days;
                } elseif ($prevStatus === 'approved' && ($newStatus === 'rejected' || $newStatus === 'cancelled')) {
                    $balance->used_days = max(0, $balance->used_days - $app->total_days);
                }

                $balance->recalculateRemaining();
            }

            $app->status = $newStatus;
            $app->admin_remarks = $validated['admin_remarks'] ?? null;
            $app->action_by_user_id = $request->user()->id;
            $app->action_at = now();
            $app->save();

            // Send notification to the employee if they have a linked user account
            if ($app->employee && $app->employee->user_id) {
                $statusUpper = strtoupper($newStatus);
                $icon = $newStatus === 'approved' ? 'check-circle' : 'x-circle';
                $remarksText = !empty($validated['admin_remarks']) ? " Note: {$validated['admin_remarks']}" : '';

                AppNotification::sendToUser(
                    $app->employee->user_id,
                    "leave_{$newStatus}",
                    "Leave Application {$statusUpper}",
                    "Your request for {$app->total_days} day(s) {$app->leaveType->name} ({$app->start_date} to {$app->end_date}) was {$newStatus} by HR.{$remarksText}",
                    '/leaves',
                    $icon,
                    [
                        'application_id' => $app->id,
                        'status' => $newStatus,
                        'admin_remarks' => $validated['admin_remarks'] ?? null,
                    ]
                );
            }
        });

        return response()->json([
            'message' => "Leave application marked as {$validated['status']}.",
            'data' => $app->fresh(['leaveType', 'employee', 'actionByUser']),
        ]);
    }

    /**
     * Superadmin / Admin: Manage Employee Leave Balances
     */
    public function getEmployeeBalances(Request $request): JsonResponse
    {
        $year = (int) $request->get('year', now()->year);
        $employeeId = $request->get('employee_id');

        $query = Employee::with(['designation', 'department', 'leaveBalances' => function ($q) use ($year) {
            $q->where('year', $year)->with('leaveType');
        }]);

        if ($employeeId) {
            $query->where('id', $employeeId);
        }

        $employees = $query->paginate($request->get('per_page', 50));
        $leaveTypes = LeaveType::where('is_active', true)->get();

        return response()->json([
            'year' => $year,
            'leave_types' => $leaveTypes,
            'employees' => $employees,
        ]);
    }

    /**
     * Superadmin: Set / Adjust Leave Balance for an Employee
     */
    public function setLeaveBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2020|max:2035',
            'allocated_days' => 'required|numeric|min:0',
        ]);

        $balance = LeaveBalance::firstOrNew([
            'employee_id' => $validated['employee_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'year' => $validated['year'],
        ]);

        $balance->allocated_days = $validated['allocated_days'];
        $balance->used_days = $balance->used_days ?? 0;
        $balance->pending_days = $balance->pending_days ?? 0;
        $balance->recalculateRemaining();

        return response()->json([
            'message' => 'Leave balance updated successfully.',
            'data' => $balance->fresh('leaveType'),
        ]);
    }

    /**
     * Superadmin: Update Leave Balance Globally Across All Employees
     */
    public function updateGlobalBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'year' => 'required|integer|min:2020|max:2035',
            'allocated_days' => 'required|numeric|min:0',
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
        $allocatedDays = (float) $validated['allocated_days'];
        $year = (int) $validated['year'];

        // 1. Update the master LeaveType default days
        $leaveType->update(['default_days_per_year' => $allocatedDays]);

        // 2. Fetch all existing active employees
        $employees = Employee::all();
        $updatedCount = 0;

        DB::transaction(function () use ($employees, $leaveType, $year, $allocatedDays, &$updatedCount) {
            foreach ($employees as $employee) {
                $balance = LeaveBalance::firstOrNew([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                ]);

                $balance->allocated_days = $allocatedDays;
                $balance->used_days = $balance->used_days ?? 0;
                $balance->pending_days = $balance->pending_days ?? 0;
                $balance->recalculateRemaining();
                $updatedCount++;
            }
        });

        return response()->json([
            'message' => "Successfully updated global {$leaveType->name} ({$leaveType->code}) balance to {$allocatedDays} days across {$updatedCount} employees for year {$year}.",
            'leave_type' => $leaveType,
            'updated_employees_count' => $updatedCount,
        ]);
    }
}

