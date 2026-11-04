<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\Payroll;
use App\Models\Payslip;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    /**
     * Preview or process payroll for a designated month (from Day 1 to end of the month).
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2035',
            'month' => 'required|integer|min:1|max:12',
            'company_id' => 'nullable|exists:companies,id',
            'save' => 'nullable|boolean', // If true, saves the batch to payrolls & payslips tables
        ]);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $companyId = $validated['company_id'] ?? null;
        $save = filter_var($validated['save'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $periodEnd = (clone $periodStart)->endOfMonth()->startOfDay();
        $calendarDays = (int) $periodStart->daysInMonth;

        // Current real-world date context
        $nowTz = Carbon::now('Asia/Kolkata');
        $currentDate = $nowTz->toDateString();
        $currentTime = $nowTz->format('H:i:s');

        // Determine effective calculation cut-off date (MTD - Month to Date vs Past Month)
        // If calculating for current month, do not consider future dates!
        $effectiveCutoff = clone $periodEnd;
        if ($year === (int) $nowTz->year && $month === (int) $nowTz->month) {
            $effectiveCutoff = Carbon::createFromDate($year, $month, (int) $nowTz->day)->startOfDay();
        } elseif (Carbon::createFromDate($year, $month, 1)->isFuture()) {
            $effectiveCutoff = Carbon::createFromDate($year, $month, 1)->subDay()->startOfDay();
        }

        // Query active employees
        $empQuery = Employee::with(['company', 'department', 'designation', 'shift']);
        if ($companyId) {
            $empQuery->where('company_id', $companyId);
        }
        $employees = $empQuery->whereIn('status', ['active', 'probation'])->get();

        // 1. Identify Sundays in the month (within the effective worked/passed window)
        $sundays = [];
        $allMonthSundays = [];
        $cursor = clone $periodStart;
        while ($cursor->lte($periodEnd)) {
            $cDate = $cursor->toDateString();
            if ($cursor->isSunday()) {
                $allMonthSundays[] = $cDate;
                if ($cursor->lte($effectiveCutoff)) {
                    $sundays[] = $cDate;
                }
            }
            $cursor->addDay();
        }
        $totalSundays = count($sundays);

        // 2. Identify Holidays in the month (within effective worked/passed window, excluding Sundays)
        $holidayQuery = Holiday::where('is_active', true)
            ->whereDate('date', '>=', $periodStart->toDateString())
            ->whereDate('date', '<=', $periodEnd->toDateString());
        if ($companyId) {
            $holidayQuery->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            });
        }
        $holidayRecords = $holidayQuery->get();
        $holidays = [];
        foreach ($holidayRecords as $h) {
            $hDate = Carbon::parse($h->date)->toDateString();
            if (!in_array($hDate, $allMonthSundays)) {
                $hCarbon = Carbon::parse($h->date)->startOfDay();
                if ($hCarbon->lte($effectiveCutoff) && !in_array($hDate, $holidays)) {
                    $holidays[] = $hDate;
                }
            }
        }
        $totalHolidays = count($holidays);

        $employeeCalculations = [];
        $batchTotalGross = 0;
        $batchTotalDeductions = 0;
        $batchTotalNet = 0;

        foreach ($employees as $employee) {
            $empId = $employee->id;

            // Fetch Attendances for this month
            $attendances = Attendance::where('employee_id', $empId)
                ->whereDate('date', '>=', $periodStart->toDateString())
                ->whereDate('date', '<=', $periodEnd->toDateString())
                ->get()
                ->keyBy(fn ($item) => Carbon::parse($item->date)->toDateString());

            // Fetch Approved Leaves for this month
            $approvedLeaves = LeaveApplication::with('leaveType')
                ->where('employee_id', $empId)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $periodEnd->toDateString())
                ->whereDate('end_date', '>=', $periodStart->toDateString())
                ->get();

            $leaveDaysMap = [];
            foreach ($approvedLeaves as $leave) {
                $lStart = Carbon::parse($leave->start_date);
                if ($lStart->lt($periodStart)) $lStart = clone $periodStart;
                $lEnd = Carbon::parse($leave->end_date);
                if ($lEnd->gt($periodEnd)) $lEnd = clone $periodEnd;

                $cur = clone $lStart;
                while ($cur->lte($lEnd)) {
                    $dStr = $cur->toDateString();
                    // Don't overwrite if it falls on Sunday or Holiday
                    if (!in_array($dStr, $sundays) && !in_array($dStr, $holidays)) {
                        $leaveDaysMap[$dStr] = [
                            'is_paid' => (bool) ($leave->leaveType?->is_paid ?? true),
                            'is_half_day' => (bool) $leave->is_half_day,
                        ];
                    }
                    $cur->addDay();
                }
            }

            // Day-by-day analysis
            $presentDays = 0;
            $halfDays = 0;
            $paidLeaveDays = 0;
            $unpaidLeaveDays = 0;
            $absentDays = 0;
            $lateDaysCount = 0;

            // Consecutive Late tracking:
            // "if employee is late for 5 days continuously after 5 days late coming it will mark as half day"
            $consecutiveLateStreak = 0;
            $latePenaltyHalfDays = 0; // Each day after 5 consecutive late marks incurs 0.5 day deduction

            $dayCursor = clone $periodStart;
            while ($dayCursor->lte($effectiveCutoff)) {
                $dStr = $dayCursor->toDateString();

                if (in_array($dStr, $sundays) || in_array($dStr, $holidays)) {
                    // Paid weekend / official holiday up to current date
                    $dayCursor->addDay();
                    continue;
                }

                if (isset($attendances[$dStr])) {
                    $att = $attendances[$dStr];
                    $status = strtolower($att->status);
                    $locationType = $att->location_type ?? ($employee->attendance_location_type ?? 'fixed');

                    // Shift completion check:
                    // For fixed locations: Must have punched out, or shift must have ended for the day.
                    // If employee is currently clocked in today and shift is ongoing, it's in progress (not completed).
                    $isToday = ($dStr === $currentDate);
                    $hasPunchedOut = !empty($att->punch_out_time);

                    $shiftEndTime = $employee->shift?->end_time;
                    $shiftCompleted = true;
                    if ($locationType === 'fixed') {
                        if ($isToday) {
                            // If today and employee hasn't punched out yet and current time is before shift end,
                            // shift is not completed yet!
                            if (!$hasPunchedOut && $shiftEndTime && $currentTime < $shiftEndTime) {
                                $shiftCompleted = false;
                            }
                        }
                    }

                    if (!$shiftCompleted) {
                        // Shift currently ongoing / in-progress today; do not credit full-day payout yet
                        $dayCursor->addDay();
                        continue;
                    }

                    if ($status === 'late') {
                        $lateDaysCount++;
                        $consecutiveLateStreak++;
                        if ($consecutiveLateStreak > 5) {
                            // After 5 continuous late days, each subsequent late counts as half day penalty
                            $latePenaltyHalfDays += 0.5;
                        }
                        $presentDays += 1;
                    } elseif ($status === 'present') {
                        $presentDays += 1;
                        $consecutiveLateStreak = 0; // Streak resets on on-time arrival
                    } elseif ($status === 'half_day') {
                        $halfDays += 1;
                        $consecutiveLateStreak = 0;
                    } else { // absent or on_leave
                        $absentDays += 1;
                        $consecutiveLateStreak = 0;
                    }
                } elseif (isset($leaveDaysMap[$dStr])) {
                    $leaveInfo = $leaveDaysMap[$dStr];
                    if ($leaveInfo['is_half_day']) {
                        if ($leaveInfo['is_paid']) {
                            $paidLeaveDays += 0.5;
                            $halfDays += 0.5;
                        } else {
                            $unpaidLeaveDays += 0.5;
                            $absentDays += 0.5;
                        }
                    } else {
                        if ($leaveInfo['is_paid']) {
                            $paidLeaveDays += 1.0;
                        } else {
                            $unpaidLeaveDays += 1.0;
                            $absentDays += 1.0;
                        }
                    }
                    $consecutiveLateStreak = 0;
                } else {
                    // Only mark absent if the day has already completed (exclude today if ongoing)
                    if ($dStr < $currentDate) {
                        $absentDays += 1;
                    }
                    $consecutiveLateStreak = 0;
                }

                $dayCursor->addDay();
            }

            // Calculate Total Payable Days:
            // Paid components: Present Days + Paid Sundays + Paid Official Holidays + Paid Leave Days + (Half Days * 0.5) - Late Penalties
            $effectiveHalfDaysCredit = $halfDays * 0.5;
            $rawPayableDays = $presentDays + $totalSundays + $totalHolidays + $paidLeaveDays + $effectiveHalfDaysCredit - $latePenaltyHalfDays;
            $totalPayableDays = max(0, min($calendarDays, round($rawPayableDays, 2)));

            // Salary Calculation Formula
            $baseGross = (float) $employee->gross_salary;
            $baseBasic = (float) $employee->basic_salary;

            // Prorated factor: total_payable_days / calendar_days
            $proratedRatio = $calendarDays > 0 ? ($totalPayableDays / $calendarDays) : 1;

            $earnedGross = round($baseGross * $proratedRatio, 2);
            $earnedBasic = round($baseBasic * $proratedRatio, 2);

            // Deductions
            // EPF: 12% of basic, capped at Rs. 1800 or Rs. 3000 max as per config
            $employeePf = 0.00;
            if ($employee->enable_epf && $earnedBasic > 0) {
                $employeePf = min(3000.00, round($earnedBasic * 0.12, 2));
            }

            // ESIC: 0.75% of Gross if gross <= 21,000
            $employeeEsic = 0.00;
            if ($baseGross <= 21000.00 && $earnedGross > 0) {
                $employeeEsic = round($earnedGross * 0.0075, 2);
            }

            // Monthly Tax / TDS
            $monthlyTax = (float) $employee->monthly_tax;

            $totalDeductions = round($employeePf + $employeeEsic + $monthlyTax, 2);
            $netSalary = max(0, round($earnedGross - $totalDeductions, 2));

            $batchTotalGross += $earnedGross;
            $batchTotalDeductions += $totalDeductions;
            $batchTotalNet += $netSalary;

            $employeeCalculations[] = [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_id,
                'employee_name' => "{$employee->first_name} " . ($employee->last_name ?? ''),
                'department' => $employee->department?->name ?? 'General',
                'designation' => $employee->designation?->name ?? 'Staff',
                'calendar_days' => $calendarDays,
                'sundays_count' => $totalSundays,
                'holidays_count' => $totalHolidays,
                'present_days' => $presentDays,
                'paid_leave_days' => $paidLeaveDays,
                'unpaid_leave_days' => $unpaidLeaveDays,
                'half_days' => $halfDays,
                'late_days_count' => $lateDaysCount,
                'late_penalty_half_days' => $latePenaltyHalfDays,
                'absent_days' => $absentDays,
                'total_payable_days' => $totalPayableDays,

                'basic_salary' => $baseBasic,
                'da_amount' => (float) $employee->da_amount,
                'hra_amount' => (float) $employee->hra_amount,
                'ca_amount' => (float) $employee->ca_amount,
                'ea_amount' => (float) $employee->ea_amount,
                'sa_amount' => (float) $employee->sa_amount,
                'gross_salary' => $baseGross,

                'earned_basic' => $earnedBasic,
                'earned_gross' => $earnedGross,

                'employee_pf' => $employeePf,
                'employee_esic' => $employeeEsic,
                'monthly_tax' => $monthlyTax,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary,
            ];
        }

        // If 'save' is requested, record to database
        $payrollRecord = null;
        if ($save) {
            DB::beginTransaction();
            try {
                $payrollRecord = Payroll::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'year' => $year,
                        'month' => $month,
                    ],
                    [
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                        'calendar_days' => $calendarDays,
                        'total_sundays' => $totalSundays,
                        'total_holidays' => $totalHolidays,
                        'total_employees_processed' => count($employeeCalculations),
                        'total_gross_payout' => $batchTotalGross,
                        'total_deductions' => $batchTotalDeductions,
                        'total_net_payout' => $batchTotalNet,
                        'status' => 'processed',
                        'processed_by_user_id' => $request->user()?->id,
                        'processed_at' => now(),
                    ]
                );

                foreach ($employeeCalculations as $calc) {
                    $payslipNumber = 'PAY-' . $year . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($calc['employee_id'], 4, '0', STR_PAD_LEFT);
                    Payslip::updateOrCreate(
                        [
                            'employee_id' => $calc['employee_id'],
                            'year' => $year,
                            'month' => $month,
                        ],
                        [
                            'payroll_id' => $payrollRecord->id,
                            'payslip_number' => $payslipNumber,
                            'period_start' => $periodStart->toDateString(),
                            'period_end' => $periodEnd->toDateString(),
                            'total_month_days' => $calc['calendar_days'],
                            'sundays_count' => $calc['sundays_count'],
                            'holidays_count' => $calc['holidays_count'],
                            'present_days' => $calc['present_days'],
                            'paid_leave_days' => $calc['paid_leave_days'],
                            'unpaid_leave_days' => $calc['unpaid_leave_days'],
                            'half_days' => $calc['half_days'],
                            'late_days_count' => $calc['late_days_count'],
                            'late_penalty_half_days' => $calc['late_penalty_half_days'],
                            'absent_days' => $calc['absent_days'],
                            'total_payable_days' => $calc['total_payable_days'],

                            'basic_salary' => $calc['basic_salary'],
                            'da_amount' => $calc['da_amount'],
                            'hra_amount' => $calc['hra_amount'],
                            'ca_amount' => $calc['ca_amount'],
                            'ea_amount' => $calc['ea_amount'],
                            'sa_amount' => $calc['sa_amount'],
                            'gross_salary' => $calc['gross_salary'],

                            'earned_basic' => $calc['earned_basic'],
                            'earned_gross' => $calc['earned_gross'],

                            'employee_pf' => $calc['employee_pf'],
                            'employee_esic' => $calc['employee_esic'],
                            'monthly_tax' => $calc['monthly_tax'],
                            'other_deductions' => 0.00,
                            'total_deductions' => $calc['total_deductions'],
                            'net_salary' => $calc['net_salary'],
                            'status' => 'generated',
                        ]
                    );
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save payroll calculation: ' . $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'period' => [
                'year' => $year,
                'month' => $month,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'calendar_days' => $calendarDays,
                'sundays_count' => $totalSundays,
                'holidays_count' => $totalHolidays,
                'holidays_list' => $holidayRecords->map(fn($h) => ['title' => $h->title, 'date' => $h->date]),
            ],
            'summary' => [
                'total_employees' => count($employeeCalculations),
                'total_gross_payout' => round($batchTotalGross, 2),
                'total_deductions' => round($batchTotalDeductions, 2),
                'total_net_payout' => round($batchTotalNet, 2),
            ],
            'payroll_id' => $payrollRecord?->id,
            'data' => $employeeCalculations,
        ]);
    }

    /**
     * List historical payroll runs
     */
    public function index(Request $request): JsonResponse
    {
        $runs = Payroll::with(['company', 'processedByUser'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $runs,
        ]);
    }

    /**
     * Get single employee's payslips or individual payslip
     */
    public function employeePayslips(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee && $user->role !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'No employee profile linked to user.',
            ], 404);
        }

        $query = Payslip::with(['payroll', 'employee.department', 'employee.designation', 'employee.company'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        if ($employee) {
            $query->where('employee_id', $employee->id);
        }

        if ($request->filled('year')) {
            $query->where('year', (int) $request->year);
        }

        if ($request->filled('month')) {
            $query->where('month', (int) $request->month);
        }

        $payslips = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payslips,
        ]);
    }

    /**
     * Download individual employee payslip (HTML printable / downloadable)
     */
    public function downloadPayslip(Request $request, int $id)
    {
        $user = $request->user();
        $payslip = Payslip::with(['payroll', 'employee.department', 'employee.designation', 'employee.company'])->findOrFail($id);

        // Security authorization check: only superadmin/admin or the owning employee can access
        $isStaffOwner = false;
        if ($user->role === 'employee') {
            $employee = Employee::where('user_id', $user->id)->first();
            if (!$employee || $employee->id !== $payslip->employee_id) {
                return response()->json(['message' => 'Unauthorized access to payslip.'], 403);
            }
            $isStaffOwner = true;
        }

        $company = $payslip->employee?->company;
        $emp = $payslip->employee;
        $monthName = Carbon::createFromDate($payslip->year, $payslip->month, 1)->format('F Y');

        $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Payslip - {$payslip->payslip_number}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; margin: 0; padding: 24px; background: #fff; font-size: 13px; line-height: 1.5; }
        .container { max-width: 800px; margin: 0 auto; border: 1px solid #cbd5e1; border-radius: 8px; padding: 32px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #2563eb; padding-bottom: 20px; margin-bottom: 24px; }
        .company-name { font-size: 22px; font-weight: 800; color: #1e293b; letter-spacing: -0.5px; }
        .company-meta { font-size: 11px; color: #64748b; margin-top: 4px; }
        .payslip-badge { background: #eff6ff; color: #1d4ed8; font-weight: 700; padding: 4px 12px; border-radius: 6px; font-size: 12px; display: inline-block; }
        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; background: #f8fafc; padding: 16px; border-radius: 6px; }
        .meta-row { display: flex; justify-content: space-between; padding: 4px 0; }
        .meta-label { color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; }
        .meta-val { font-weight: 600; color: #0f172a; }
        .breakdown-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .breakdown-table th { background: #f1f5f9; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #cbd5e1; }
        .breakdown-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
        .amount { text-align: right; font-family: monospace; font-size: 13px; font-weight: 600; }
        .net-salary-card { background: #1e3a8a; color: white; border-radius: 6px; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
        .net-amount { font-size: 24px; font-weight: 800; font-family: monospace; }
        .footer { text-align: center; margin-top: 32px; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; }
        @media print {
            body { padding: 0; }
            .container { border: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class='no-print' style='max-width: 800px; margin: 0 auto 16px auto; text-align: right;'>
        <button onclick='window.print()' style='background: #2563eb; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer;'>Print / Save as PDF</button>
    </div>
    <div class='container'>
        <div class='header'>
            <div>
                <div class='company-name'>" . htmlspecialchars($company?->name ?? 'Plystory HO / HRMS Enterprise') . "</div>
                <div class='company-meta'>" . htmlspecialchars($company?->address ?? 'Corporate Headquarters') . "</div>
                <div class='company-meta'>Email: " . htmlspecialchars($company?->email ?? 'hr@hrms.local') . "</div>
            </div>
            <div style='text-align: right;'>
                <div class='payslip-badge'>PAYSLIP FOR {$monthName}</div>
                <div style='font-size: 11px; color: #64748b; margin-top: 6px;'>Payslip No: <strong>{$payslip->payslip_number}</strong></div>
                <div style='font-size: 11px; color: #64748b;'>Pay Period: {$payslip->period_start} to {$payslip->period_end}</div>
            </div>
        </div>

        <div class='meta-grid'>
            <div>
                <div class='meta-row'><span class='meta-label'>Employee Name:</span> <span class='meta-val'>" . htmlspecialchars($emp?->name ?? 'N/A') . "</span></div>
                <div class='meta-row'><span class='meta-label'>Employee ID:</span> <span class='meta-val'>" . htmlspecialchars($emp?->employee_code ?? 'N/A') . "</span></div>
                <div class='meta-row'><span class='meta-label'>Department:</span> <span class='meta-val'>" . htmlspecialchars($emp?->department?->name ?? 'N/A') . "</span></div>
                <div class='meta-row'><span class='meta-label'>Designation:</span> <span class='meta-val'>" . htmlspecialchars($emp?->designation?->title ?? 'N/A') . "</span></div>
            </div>
            <div>
                <div class='meta-row'><span class='meta-label'>Bank Account:</span> <span class='meta-val'>" . htmlspecialchars($emp?->bank_account_number ?? 'Registered Account') . "</span></div>
                <div class='meta-row'><span class='meta-label'>IFSC Code:</span> <span class='meta-val'>" . htmlspecialchars($emp?->ifsc_code ?? 'N/A') . "</span></div>
                <div class='meta-row'><span class='meta-label'>Total Month Days:</span> <span class='meta-val'>{$payslip->total_month_days} Days</span></div>
                <div class='meta-row'><span class='meta-label'>Total Payable Days:</span> <span class='meta-val'>{$payslip->total_payable_days} Days</span></div>
            </div>
        </div>

        <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 24px;'>
            <div>
                <table class='breakdown-table'>
                    <thead>
                        <tr><th>Earnings</th><th class='amount'>Amount (INR)</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Basic Salary</td><td class='amount'>₹" . number_format($payslip->earned_basic, 2) . "</td></tr>
                        <tr><td>Dearness Allowance (DA)</td><td class='amount'>₹" . number_format($payslip->da_amount, 2) . "</td></tr>
                        <tr><td>House Rent Allowance (HRA)</td><td class='amount'>₹" . number_format($payslip->hra_amount, 2) . "</td></tr>
                        <tr><td>Conveyance Allowance (CA)</td><td class='amount'>₹" . number_format($payslip->ca_amount, 2) . "</td></tr>
                        <tr><td>Medical / Other Allowance</td><td class='amount'>₹" . number_format($payslip->ea_amount + $payslip->sa_amount, 2) . "</td></tr>
                        <tr style='font-weight: 700; background: #f8fafc;'><td>Earned Gross Pay</td><td class='amount' style='color: #166534;'>₹" . number_format($payslip->earned_gross, 2) . "</td></tr>
                    </tbody>
                </table>
            </div>

            <div>
                <table class='breakdown-table'>
                    <thead>
                        <tr><th>Deductions</th><th class='amount'>Amount (INR)</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Employee Provident Fund (EPF 12%)</td><td class='amount'>₹" . number_format($payslip->employee_pf, 2) . "</td></tr>
                        <tr><td>Employee State Insurance (ESIC)</td><td class='amount'>₹" . number_format($payslip->employee_esic, 2) . "</td></tr>
                        <tr><td>Professional Tax / TDS</td><td class='amount'>₹" . number_format($payslip->monthly_tax, 2) . "</td></tr>
                        <tr><td>Other Deductions / Penalties</td><td class='amount'>₹" . number_format($payslip->other_deductions, 2) . "</td></tr>
                        <tr><td>&nbsp;</td><td class='amount'>&nbsp;</td></tr>
                        <tr style='font-weight: 700; background: #f8fafc;'><td>Total Deductions</td><td class='amount' style='color: #991b1b;'>₹" . number_format($payslip->total_deductions, 2) . "</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class='net-salary-card'>
            <div>
                <div style='font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;'>Net Salary Payable</div>
                <div style='font-size: 11px; opacity: 0.8;'>Disbursed directly into employee bank account</div>
            </div>
            <div class='net-amount'>₹" . number_format($payslip->net_salary, 2) . "</div>
        </div>

        <div class='footer'>
            This is a system-generated computer document and does not require a physical signature.<br>
            For any compensation discrepancies, please open an internal ticket under IT Helpdesk & Support.
        </div>
    </div>
</body>
</html>";

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}

