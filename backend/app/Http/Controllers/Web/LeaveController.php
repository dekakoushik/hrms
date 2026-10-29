<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\InAppNotification;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LeaveController extends Controller
{
    /**
     * Leave Manager Main Screen (Module 5)
     * Shows:
     * - Pending Leave Approvals for Superadmin / Admin
     * - All Leave Records / History
     * - Global Leave Balance Policies & Quotas
     * - Employee Leave Application Modal & Direct Action
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $activeCompanyId = session('active_company_id');
        $employee = Employee::where('user_id', $user->id)->first();

        // 1. Fetch Leave Types (Global Policies)
        $leaveTypes = LeaveType::orderBy('id')->get();

        // Ensure current employee has initialized leave balances
        if ($employee) {
            EmployeeLeaveBalance::ensureBalancesForEmployee($employee->id, (int) date('Y'));
            $employee->load('leaveBalances.leaveType');
        }

        // 2. Fetch Pending Approvals (for Admin/Superadmin or Managers)
        $pendingQuery = LeaveRequest::with(['employee.user', 'employee.department', 'employee.designation', 'leaveType'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc');

        if ($activeCompanyId) {
            $pendingQuery->where('company_id', $activeCompanyId);
        }

        // If regular user/employee, pending is empty or only their team
        $isAdminOrSuperAdmin = in_array($user->role, ['superadmin', 'admin']);
        $pendingApprovals = $isAdminOrSuperAdmin ? $pendingQuery->get() : collect();

        // 3. Fetch All Leave History / Register
        $historyQuery = LeaveRequest::with(['employee.user', 'employee.department', 'leaveType', 'actionedByUser'])
            ->orderBy('created_at', 'desc');

        if ($activeCompanyId) {
            $historyQuery->where('company_id', $activeCompanyId);
        }

        // Filter by status if provided
        if ($request->filled('status')) {
            $historyQuery->where('status', $request->input('status'));
        }

        // Search by employee name/code
        if ($request->filled('search')) {
            $search = $request->input('search');
            $historyQuery->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // If normal employee, show their own requests unless admin
        if (!$isAdminOrSuperAdmin && $employee) {
            $historyQuery->where('employee_id', $employee->id);
        }

        $leaveRequests = $historyQuery->paginate(15)->withQueryString();

        // 4. Leave Statistics
        $currentYear = (int) date('Y');
        $totalPendingCount = LeaveRequest::where('status', 'pending')
            ->when($activeCompanyId, fn($q) => $q->where('company_id', $activeCompanyId))
            ->count();

        $approvedThisMonthCount = LeaveRequest::where('status', 'approved')
            ->whereMonth('start_date', Carbon::today()->month)
            ->whereYear('start_date', $currentYear)
            ->when($activeCompanyId, fn($q) => $q->where('company_id', $activeCompanyId))
            ->count();

        $onLeaveTodayCount = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->when($activeCompanyId, fn($q) => $q->where('company_id', $activeCompanyId))
            ->count();

        return view('leave.index', compact(
            'leaveTypes',
            'employee',
            'pendingApprovals',
            'leaveRequests',
            'totalPendingCount',
            'approvedThisMonthCount',
            'onLeaveTodayCount',
            'isAdminOrSuperAdmin'
        ));
    }

    /**
     * Submit New Leave Application (Employee / Admin on behalf)
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        // If admin applying on behalf of employee
        if (in_array($user->role, ['superadmin', 'admin']) && $request->filled('target_employee_id')) {
            $employee = Employee::find($request->input('target_employee_id'));
        }

        if (!$employee) {
            return $this->formatResponse($request, false, 'No valid employee profile found for leave application.');
        }

        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['nullable', 'boolean'],
            'half_day_type' => ['nullable', 'string', 'in:first_half,second_half'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $isHalfDay = !empty($validated['is_half_day']);
        $daysCount = $isHalfDay ? 0.5 : ($startDate->diffInDays($endDate) + 1);

        // Ensure balance exists
        EmployeeLeaveBalance::ensureBalancesForEmployee($employee->id, (int) $startDate->year);
        $balance = EmployeeLeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', (int) $startDate->year)
            ->first();

        $leaveType = LeaveType::find($validated['leave_type_id']);

        // Check if sufficient balance
        if ($balance && $balance->remaining_days < $daysCount) {
            return $this->formatResponse(
                $request,
                false,
                "Insufficient leave balance! You requested {$daysCount} days, but remaining balance for {$leaveType->name} is only {$balance->remaining_days} days."
            );
        }

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'days_count' => $daysCount,
            'is_half_day' => $isHalfDay,
            'half_day_type' => $isHalfDay ? ($validated['half_day_type'] ?? 'first_half') : null,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        // Update pending days in balance
        if ($balance) {
            $balance->increment('pending_days', $daysCount);
        }

        // Send In-App Notification to Admins / Superadmins
        InAppNotification::createNotification([
            'company_id' => $employee->company_id,
            'type' => 'leave_applied',
            'title' => "New Leave Application: {$employee->full_name}",
            'message' => "{$employee->full_name} applied for {$daysCount} day(s) {$leaveType->name} from {$startDate->format('d M')} to {$endDate->format('d M, Y')}. Reason: {$validated['reason']}",
            'link_url' => route('leave.index'),
            'icon' => 'calendar',
            'color' => 'indigo',
        ]);

        return $this->formatResponse(
            $request,
            true,
            "Leave application for {$daysCount} day(s) submitted successfully! It is pending approval from management.",
            ['leave_request' => $leaveRequest]
        );
    }

    /**
     * Approve or Reject Leave Request (Admin / Superadmin only)
     */
    public function action(Request $request, LeaveRequest $leaveRequest): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return $this->formatResponse($request, false, 'Unauthorized: Only superadmins or admins can approve or reject leaves.');
        }

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:approve,reject'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $action = $validated['action'];
        $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
        $remarks = $validated['remarks'] ?? ($action === 'approve' ? 'Approved by management' : 'Rejected by management');

        $employee = $leaveRequest->employee;
        $leaveType = $leaveRequest->leaveType;
        $year = (int) $leaveRequest->start_date->year;

        // Balance adjustment
        $balance = EmployeeLeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', $year)
            ->first();

        if ($balance) {
            // Deduct from pending days
            $balance->pending_days = max(0, $balance->pending_days - $leaveRequest->days_count);

            if ($action === 'approve') {
                $balance->used_days += $leaveRequest->days_count;
                $balance->remaining_days = max(0, $balance->total_allocated - $balance->used_days);
            }
            $balance->save();
        }

        $leaveRequest->update([
            'status' => $newStatus,
            'actioned_by' => $user->id,
            'actioned_at' => Carbon::now(),
            'action_remarks' => $remarks,
        ]);

        // Send InAppNotification to the employee
        InAppNotification::createNotification([
            'user_id' => $employee->user_id,
            'company_id' => $employee->company_id,
            'type' => 'leave_actioned',
            'title' => "Leave Request " . ucfirst($newStatus),
            'message' => "Your leave application for {$leaveRequest->days_count} day(s) {$leaveType->name} ({$leaveRequest->start_date->format('d M')} - {$leaveRequest->end_date->format('d M')}) has been {$newStatus}. Remark: {$remarks}",
            'link_url' => route('leave.index'),
            'icon' => ($action === 'approve') ? 'check-circle' : 'x-circle',
            'color' => ($action === 'approve') ? 'emerald' : 'rose',
        ]);

        $statusText = ($action === 'approve') ? 'approved' : 'rejected';
        return $this->formatResponse(
            $request,
            true,
            "Leave application for {$employee->full_name} has been {$statusText} successfully.",
            ['leave_request' => $leaveRequest->fresh()]
        );
    }

    /**
     * Update Global Leave Balance Policy Quotas (Superadmin / Admin only)
     * Sets leave quota globally and synchronizes all active employee balances
     */
    public function updateGlobalPolicy(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return $this->formatResponse($request, false, 'Unauthorized: Only superadmin or admin can configure global leave balances.');
        }

        $validated = $request->validate([
            'policies' => ['required', 'array'],
            'policies.*.id' => ['required', 'exists:leave_types,id'],
            'policies.*.default_annual_quota' => ['required', 'numeric', 'min:0', 'max:365'],
            'policies.*.carry_forward' => ['nullable', 'boolean'],
            'policies.*.description' => ['nullable', 'string', 'max:255'],
            'sync_existing_employees' => ['nullable', 'boolean'],
        ]);

        $syncEmployees = !empty($validated['sync_existing_employees']);
        $currentYear = (int) date('Y');

        foreach ($validated['policies'] as $pData) {
            $leaveType = LeaveType::find($pData['id']);
            if (!$leaveType) continue;

            $oldQuota = $leaveType->default_annual_quota;
            $newQuota = (float) $pData['default_annual_quota'];

            $leaveType->update([
                'default_annual_quota' => $newQuota,
                'carry_forward' => !empty($pData['carry_forward']),
                'description' => $pData['description'] ?? $leaveType->description,
            ]);

            // If sync requested, update current year balances for all employees
            if ($syncEmployees) {
                $balances = EmployeeLeaveBalance::where('leave_type_id', $leaveType->id)
                    ->where('year', $currentYear)
                    ->get();

                foreach ($balances as $b) {
                    $b->total_allocated = $newQuota;
                    $b->remaining_days = max(0, $newQuota - $b->used_days);
                    $b->save();
                }
            }
        }

        return $this->formatResponse(
            $request,
            true,
            "Global leave quotas updated successfully!" . ($syncEmployees ? " All employee leave balances have been synchronized." : "")
        );
    }

    /**
     * Helper to return JSON or flash redirect
     */
    protected function formatResponse(Request $request, bool $success, string $message, array $extra = []): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(array_merge([
                'success' => $success,
                'message' => $message,
            ], $extra), $success ? 200 : 422);
        }

        if ($success) {
            return redirect()->back()->with('success', $message);
        }

        return redirect()->back()->withErrors(['leave_error' => $message]);
    }
}
