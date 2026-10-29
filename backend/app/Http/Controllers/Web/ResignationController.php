<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\InAppNotification;
use App\Models\Resignation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResignationController extends Controller
{
    /**
     * Resignations & Exit Management Master / Register
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $employee = Employee::where('user_id', $user->id)->first();
        $activeCompanyId = session('active_company_id');

        $query = Resignation::with(['employee.department', 'employee.designation', 'employee.user', 'actionedByUser'])
            ->orderBy('created_at', 'desc');

        if ($activeCompanyId) {
            $query->where('company_id', $activeCompanyId);
        }

        // If regular user, only view own resignation history
        if (!$isAdmin && $employee) {
            $query->where('employee_id', $employee->id);
        }

        $resignations = $query->paginate(15);

        // Check if employee has an active/pending resignation
        $activeResignation = null;
        if ($employee) {
            $activeResignation = Resignation::where('employee_id', $employee->id)
                ->whereIn('status', ['pending', 'approved'])
                ->latest()
                ->first();
        }

        return view('resignations.index', compact('resignations', 'activeResignation', 'isAdmin', 'employee'));
    }

    /**
     * Employee submits exit / resignation
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return $this->formatResponse($request, false, 'No linked employee profile found to submit resignation.');
        }

        // Check if already an active resignation exists
        $existing = Resignation::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return $this->formatResponse($request, false, 'You already have an active resignation request under processing.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'requested_last_working_day' => ['required', 'date', 'after_or_equal:today'],
            'notice_period_days' => ['nullable', 'integer', 'min:0', 'max:180'],
        ]);

        $noticeDays = (int) ($validated['notice_period_days'] ?? 30);
        $resignation = Resignation::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'resignation_date' => Carbon::today(),
            'reason' => $validated['reason'],
            'requested_last_working_day' => Carbon::parse($validated['requested_last_working_day']),
            'notice_period_days' => $noticeDays,
            'status' => 'pending',
        ]);

        // Send alert to admin via Notification Center
        InAppNotification::createNotification([
            'company_id' => $employee->company_id,
            'type' => 'early_exit',
            'title' => "Resignation Submitted: {$employee->full_name}",
            'message' => "{$employee->full_name} ({$employee->employee_id}) has applied for resignation. Requested LWD: " . Carbon::parse($validated['requested_last_working_day'])->format('d M Y') . ".",
            'link_url' => route('resignations.index'),
            'icon' => 'user-minus',
            'color' => 'rose',
        ]);

        return $this->formatResponse(
            $request,
            true,
            'Resignation submitted successfully. It has been routed to administration for review.',
            ['resignation' => $resignation]
        );
    }

    /**
     * Admin / Superadmin approves, rejects, or adjusts notice period / LWD
     */
    public function action(Request $request, Resignation $resignation): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return $this->formatResponse($request, false, 'Unauthorized: Only superadmin or admin can action resignations.');
        }

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:approve,reject,extend_notice'],
            'approved_last_working_day' => ['nullable', 'date'],
            'notice_period_days' => ['nullable', 'integer', 'min:0'],
            'action_remarks' => ['required', 'string', 'max:1000'],
        ]);

        $action = $validated['action'];
        $employee = $resignation->employee;

        if ($action === 'approve') {
            $lwd = !empty($validated['approved_last_working_day'])
                ? Carbon::parse($validated['approved_last_working_day'])
                : $resignation->requested_last_working_day;

            $resignation->update([
                'status' => 'approved',
                'approved_last_working_day' => $lwd,
                'actioned_by' => $user->id,
                'actioned_at' => Carbon::now(),
                'action_remarks' => $validated['action_remarks'],
            ]);

            // Set exit date on employee profile
            $employee->update([
                'exit_date' => $lwd,
                'exit_reason' => $resignation->reason,
            ]);

            // Notify employee
            InAppNotification::createNotification([
                'user_id' => $employee->user_id,
                'company_id' => $employee->company_id,
                'type' => 'regularization',
                'title' => "Resignation Approved",
                'message' => "Your resignation has been approved. Your official Last Working Day is " . $lwd->format('d M Y') . ". Remark: " . $validated['action_remarks'],
                'link_url' => route('resignations.index'),
                'icon' => 'check-circle',
                'color' => 'indigo',
            ]);

            $msg = "Resignation for {$employee->full_name} approved. Last working day confirmed as " . $lwd->format('d M Y') . ".";
        } elseif ($action === 'extend_notice') {
            $newNotice = (int) ($validated['notice_period_days'] ?? $resignation->notice_period_days);
            $lwd = !empty($validated['approved_last_working_day'])
                ? Carbon::parse($validated['approved_last_working_day'])
                : (clone $resignation->resignation_date)->addDays($newNotice);

            $resignation->update([
                'status' => 'approved',
                'notice_period_days' => $newNotice,
                'approved_last_working_day' => $lwd,
                'actioned_by' => $user->id,
                'actioned_at' => Carbon::now(),
                'action_remarks' => $validated['action_remarks'],
            ]);

            $employee->update([
                'exit_date' => $lwd,
            ]);

            InAppNotification::createNotification([
                'user_id' => $employee->user_id,
                'company_id' => $employee->company_id,
                'type' => 'regularization',
                'title' => "Notice Period Adjusted",
                'message' => "Your notice period has been adjusted to {$newNotice} days. Revised Last Working Day: " . $lwd->format('d M Y') . ". Remark: " . $validated['action_remarks'],
                'link_url' => route('resignations.index'),
                'icon' => 'clock',
                'color' => 'amber',
            ]);

            $msg = "Notice period for {$employee->full_name} extended/adjusted to {$newNotice} days (LWD: " . $lwd->format('d M Y') . ").";
        } else {
            // Reject
            $resignation->update([
                'status' => 'rejected',
                'actioned_by' => $user->id,
                'actioned_at' => Carbon::now(),
                'action_remarks' => $validated['action_remarks'],
            ]);

            InAppNotification::createNotification([
                'user_id' => $employee->user_id,
                'company_id' => $employee->company_id,
                'type' => 'early_exit',
                'title' => "Resignation Request Rejected",
                'message' => "Your resignation request has not been accepted by management. Remark: " . $validated['action_remarks'],
                'link_url' => route('resignations.index'),
                'icon' => 'x-circle',
                'color' => 'rose',
            ]);

            $msg = "Resignation for {$employee->full_name} has been rejected.";
        }

        return $this->formatResponse($request, true, $msg, ['resignation' => $resignation->fresh()]);
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

        return redirect()->back()->withErrors(['resignation_error' => $message]);
    }
}
