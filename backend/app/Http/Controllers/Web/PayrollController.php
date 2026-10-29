<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\InAppNotification;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayrollController extends Controller
{
    /**
     * Payroll Module Dashboard / List (Module 6)
     * Cycle: 1st of every month to last day of the month
     * Leave Salary Deduction Rule: (takehome / 30) * (full_day_leaves + (half_day_leaves * 0.5))
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $activeCompanyId = session('active_company_id');

        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);

        // Fetch all payroll runs
        $runsQuery = PayrollRun::with(['company', 'processedByUser'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        if ($activeCompanyId) {
            $runsQuery->where('company_id', $activeCompanyId);
        }

        $payrollRuns = $runsQuery->paginate(12);

        // Check if current month cycle run exists
        $currentRun = PayrollRun::where('year', $year)
            ->where('month', $month)
            ->when($activeCompanyId, fn($q) => $q->where('company_id', $activeCompanyId))
            ->first();

        // If employee viewing their own payslips
        $employee = Employee::where('user_id', $user->id)->first();
        $myPayslips = collect();
        if ($employee) {
            $myPayslips = Payslip::where('employee_id', $employee->id)
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();
        }

        // Active employees count for preview
        $activeEmployeesCount = Employee::where('status', 'active')
            ->when($activeCompanyId, fn($q) => $q->where('company_id', $activeCompanyId))
            ->count();

        return view('payroll.index', compact(
            'payrollRuns',
            'currentRun',
            'year',
            'month',
            'isAdmin',
            'employee',
            'myPayslips',
            'activeEmployeesCount'
        ));
    }

    /**
     * View Details of a Specific Payroll Run with all Generated Employee Payslips
     */
    public function show(Request $request, PayrollRun $payrollRun): View
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $employee = Employee::where('user_id', $user->id)->first();

        $query = $payrollRun->payslips()->with(['employee.user', 'employee.department', 'employee.designation']);

        if (!$isAdmin && $employee) {
            $query->where('employee_id', $employee->id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $payslips = $query->paginate(25)->withQueryString();

        return view('payroll.show', compact('payrollRun', 'payslips', 'isAdmin'));
    }

    /**
     * View Individual Payslip (Digital Slip format)
     */
    public function showPayslip(Payslip $payslip): View
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$isAdmin && (!$employee || $payslip->employee_id !== $employee->id)) {
            abort(403, 'Unauthorized access to payslip.');
        }

        $payslip->load([
            'employee.department',
            'employee.designation',
            'employee.company',
            'employee.location',
            'company',
            'payrollRun',
        ]);

        return view('payroll.payslip', compact('payslip', 'isAdmin'));
    }

    /**
     * Process / Generate Monthly Payroll
     * Cycle: 1st of month to last of month
     * Deduction Rule:
     * - Full day leave = 1.0 day
     * - Half day leave / attendance = 0.5 day
     * - Total deductible days = full_day_leaves + (half_day_leaves * 0.5)
     * - Deduction = round((takehome / 30) * total_deductible_days, 2)
     * - Net payout = max(0, takehome - deduction)
     */
    public function process(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return $this->formatResponse($request, false, 'Unauthorized: Only superadmins or admins can process monthly payroll.');
        }

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2030'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ]);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $companyId = $validated['company_id'] ?: session('active_company_id');

        // Cycle Period: First of the month to last of the month
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = (clone $startDate)->endOfMonth()->startOfDay();
        $cyclePeriod = $startDate->format('01 M Y') . ' - ' . $endDate->format('d M Y');

        // Query active employees
        $employeesQuery = Employee::where('status', 'active');
        if ($companyId) {
            $employeesQuery->where('company_id', $companyId);
        }
        $employees = $employeesQuery->get();

        if ($employees->isEmpty()) {
            return $this->formatResponse($request, false, 'No active employees found to process payroll for this cycle.');
        }

        // Create or update PayrollRun
        $payrollRun = PayrollRun::updateOrCreate(
            [
                'company_id' => $companyId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'cycle_period' => $cyclePeriod,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'status' => 'processed',
                'processed_by' => $user->id,
                'processed_at' => Carbon::now(),
                'employee_count' => $employees->count(),
            ]
        );

        $totalGross = 0.00;
        $totalDeductions = 0.00;
        $totalNetPayout = 0.00;

        foreach ($employees as $index => $emp) {
            // 1. Calculate Full Day Leaves and Half Day Leaves in this cycle
            // A. Count approved leave requests
            $approvedLeaves = LeaveRequest::where('employee_id', $emp->id)
                ->where('status', 'approved')
                ->where('start_date', '<=', $endDate->format('Y-m-d'))
                ->where('end_date', '>=', $startDate->format('Y-m-d'))
                ->get();

            $fullDayLeavesCount = 0.0;
            $halfDayLeavesCount = 0.0;

            foreach ($approvedLeaves as $al) {
                if ($al->is_half_day) {
                    $halfDayLeavesCount += 1.0;
                } else {
                    $fullDayLeavesCount += (float) $al->days_count;
                }
            }

            // B. Also count attendance records marked as half_day or absent in this month
            $halfDayAttendances = Attendance::where('employee_id', $emp->id)
                ->whereBetween('attendance_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->where('status', 'half_day')
                ->where('is_regularized', false)
                ->count();

            // Add unregularized half-day attendances
            $halfDayLeavesCount += (float) $halfDayAttendances;

            // Rule: Total deductible days = full_day_leaves + (half_day_leaves * 0.5)
            $totalDeductibleDays = $fullDayLeavesCount + ($halfDayLeavesCount * 0.5);
            $daysWorked = max(0, 30.0 - $totalDeductibleDays);

            // 2. Base Salary Structure
            $basic = (float) $emp->basic_salary;
            $gross = (float) $emp->gross_salary;
            $baselineTakeHome = (float) $emp->take_home_salary;

            // If baseline take-home isn't pre-computed, calculate it
            if ($baselineTakeHome <= 0) {
                $breakdown = Employee::calculateSalaryBreakdown($basic, (bool) $emp->pf_enabled);
                $gross = $breakdown['gross'];
                $baselineTakeHome = $breakdown['take_home'];
            }

            // Statutory deductions (PF, ESIC, Tax)
            $pf = (float) ($emp->pf_amount ?? 0);
            $esic = (float) ($emp->esic_employee_amount ?? 0);
            $tax = (float) ($emp->tax_amount ?? 0);
            $statutoryDeductions = round($pf + $esic + $tax, 2);

            // 3. User's Exact Formula:
            // "if full day leave then deduction = takehome/30 * no. of days, for half days we can take 0.5"
            $dailyTakeHomeRate = round($baselineTakeHome / 30.0, 2);
            $leaveDeductionAmount = round($dailyTakeHomeRate * $totalDeductibleDays, 2);
            $netSalary = max(0.00, round($baselineTakeHome - $leaveDeductionAmount, 2));

            $totalGross += $gross;
            $totalDeductions += ($statutoryDeductions + $leaveDeductionAmount);
            $totalNetPayout += $netSalary;

            $payslipNumber = sprintf('PS-%04d%02d-%04d', $year, $month, $emp->id);

            Payslip::updateOrCreate(
                [
                    'employee_id' => $emp->id,
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'payroll_run_id' => $payrollRun->id,
                    'company_id' => $emp->company_id,
                    'payslip_number' => $payslipNumber,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'month_calendar_days' => 30,
                    'full_day_leaves' => $fullDayLeavesCount,
                    'half_day_leaves' => $halfDayLeavesCount,
                    'total_deductible_days' => $totalDeductibleDays,
                    'days_worked' => $daysWorked,
                    'basic_salary' => $basic,
                    'da_amount' => (float) $emp->da_amount,
                    'hra_amount' => (float) $emp->hra_amount,
                    'ca_amount' => (float) $emp->ca_amount,
                    'ea_amount' => (float) $emp->ea_amount,
                    'sa_amount' => (float) $emp->sa_amount,
                    'gross_salary' => $gross,
                    'pf_deduction' => $pf,
                    'esic_deduction' => $esic,
                    'tax_deduction' => $tax,
                    'statutory_deductions' => $statutoryDeductions,
                    'baseline_take_home' => $baselineTakeHome,
                    'daily_take_home_rate' => $dailyTakeHomeRate,
                    'leave_deduction_amount' => $leaveDeductionAmount,
                    'net_salary' => $netSalary,
                    'bank_name' => $emp->bank_name,
                    'bank_account_number' => $emp->account_number,
                    'ifsc_code' => $emp->ifsc_code,
                    'status' => 'generated',
                ]
            );
        }

        // Update run totals
        $payrollRun->update([
            'total_gross' => round($totalGross, 2),
            'total_deductions' => round($totalDeductions, 2),
            'total_net_payout' => round($totalNetPayout, 2),
        ]);

        // Broadcast notification to Notification Center
        InAppNotification::createNotification([
            'company_id' => $companyId,
            'type' => 'regularization',
            'title' => "Payroll Processed: {$startDate->format('M Y')}",
            'message' => "Monthly payroll batch for {$cyclePeriod} processed ({$employees->count()} employees, Net Payout: ₹" . number_format($totalNetPayout, 2) . ").",
            'link_url' => route('payroll.show', $payrollRun->id),
            'icon' => 'indian-rupee',
            'color' => 'emerald',
        ]);

        return $this->formatResponse(
            $request,
            true,
            "Monthly Payroll for {$cyclePeriod} successfully calculated and generated for {$employees->count()} active employees!",
            ['payroll_run' => $payrollRun]
        );
    }

    /**
     * Mark Payroll Run as Approved / Paid
     */
    public function markPaid(Request $request, PayrollRun $payrollRun): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return $this->formatResponse($request, false, 'Unauthorized.');
        }

        $payrollRun->update(['status' => 'paid']);
        $payrollRun->payslips()->update([
            'status' => 'paid',
            'paid_at' => Carbon::now(),
        ]);

        return $this->formatResponse($request, true, "Payroll for {$payrollRun->cycle_period} marked as PAID. All employee payslips updated.");
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

        return redirect()->back()->withErrors(['payroll_error' => $message]);
    }
}
