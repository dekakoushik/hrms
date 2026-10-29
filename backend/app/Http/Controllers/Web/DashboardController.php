<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show Superadmin HRMS Dashboard
     */
    public function index(): View
    {
        $user = Auth::user();
        $activeCompanyId = session('active_company_id');

        $empQuery = \App\Models\Employee::query();
        $userQuery = User::query();

        if ($activeCompanyId) {
            $empQuery->where('company_id', $activeCompanyId);
            $userQuery->where('company_id', $activeCompanyId);
        }

        $totalEmployees = $empQuery->count();
        $activeEmployees = (clone $empQuery)->where('status', 'active')->count();

        // 1. Live Today Attendance Metrics
        $today = \Carbon\Carbon::today();
        $todayAttQuery = \App\Models\Attendance::whereDate('attendance_date', $today);
        if ($activeCompanyId) {
            $todayAttQuery->where('company_id', $activeCompanyId);
        }
        $todayPunchedCount = $todayAttQuery->count();
        $todayPresentCount = (clone $todayAttQuery)->whereIn('status', ['present', 'half_day'])->count();
        $todayFieldVisits = (clone $todayAttQuery)->where('is_field_visit', true)->count();
        $todayLateCount = (clone $todayAttQuery)->where('is_late', true)->count();

        // Calculated attendance percentage based on active workforce
        $attendanceRate = $activeEmployees > 0 
            ? round(($todayPresentCount / $activeEmployees) * 100, 1) . '%' 
            : ($todayPresentCount > 0 ? '100%' : '0%');

        // 2. Pending Requests requiring Admin attention
        $pendingLeaveQuery = \App\Models\LeaveRequest::where('status', 'pending');
        $pendingResignQuery = \App\Models\Resignation::where('status', 'pending');
        if ($activeCompanyId) {
            $pendingLeaveQuery->where('company_id', $activeCompanyId);
            $pendingResignQuery->where('company_id', $activeCompanyId);
        }
        $pendingLeavesCount = $pendingLeaveQuery->count();
        $pendingResignationsCount = $pendingResignQuery->count();

        // 3. Organization Structure & Infrastructure
        $companiesCount = \App\Models\Company::where('is_active', true)->count();
        $locationsCount = \App\Models\Location::where('is_active', true)->count();
        $departmentsCount = \App\Models\Department::count();
        $shiftsCount = \App\Models\Shift::where('is_active', true)->count();

        // 4. Payroll Metrics (Production calculation from Payslips / Salaries)
        $now = \Carbon\Carbon::now();
        $currentMonthPayslipsQuery = \App\Models\Payslip::where('year', $now->year)->where('month', $now->month);
        if ($activeCompanyId) {
            $currentMonthPayslipsQuery->where('company_id', $activeCompanyId);
        }
        $currentMonthDisbursed = (float) $currentMonthPayslipsQuery->sum('net_salary');
        $currentMonthSlipCount = $currentMonthPayslipsQuery->count();

        // If current month payroll isn't generated yet, fall back to last generated month or sum of active employee gross CTC
        if ($currentMonthDisbursed <= 0) {
            $lastPayslipMonth = \App\Models\Payslip::latest('created_at')->first();
            if ($lastPayslipMonth) {
                $lastMonthQuery = \App\Models\Payslip::where('year', $lastPayslipMonth->year)->where('month', $lastPayslipMonth->month);
                if ($activeCompanyId) {
                    $lastMonthQuery->where('company_id', $activeCompanyId);
                }
                $currentMonthDisbursed = (float) $lastMonthQuery->sum('net_salary');
                $currentMonthSlipCount = $lastMonthQuery->count();
            }
        }

        // Format payroll amount cleanly in INR Lakhs / Thousands
        $formattedPayroll = '₹' . number_format($currentMonthDisbursed, 2);
        if ($currentMonthDisbursed >= 100000) {
            $formattedPayroll = '₹' . number_format($currentMonthDisbursed / 100000, 2) . ' L';
        }

        $stats = [
            'total_users' => $totalEmployees,
            'active_employees' => $activeEmployees,
            'departments_count' => $departmentsCount,
            'companies_count' => $companiesCount,
            'locations_count' => $locationsCount,
            'shifts_count' => $shiftsCount,
            'attendance_rate' => $attendanceRate,
            'today_punched' => $todayPunchedCount,
            'today_field_visits' => $todayFieldVisits,
            'today_late' => $todayLateCount,
            'pending_leaves' => $pendingLeavesCount,
            'pending_resignations' => $pendingResignationsCount,
            'payroll_amount' => $formattedPayroll,
            'payroll_slips_count' => $currentMonthSlipCount,
            'payroll_status' => $currentMonthSlipCount > 0 ? "Processed ({$currentMonthSlipCount} slips)" : 'Ready for Cycle',
        ];

        // 5. Recent Admin Feeds (Live real records)
        $recentAttendances = \App\Models\Attendance::with(['employee.user', 'shift', 'punchInLocation'])
            ->whereDate('attendance_date', $today)
            ->latest('punch_in_at')
            ->take(6)
            ->get();

        $recentLeaveRequests = \App\Models\LeaveRequest::with(['employee.user', 'leaveType'])
            ->latest('created_at')
            ->take(5)
            ->get();

        $recentEmployees = \App\Models\Employee::with(['user', 'department', 'designation'])
            ->latest('created_at')
            ->take(5)
            ->get();

        // Fetch employee profile and today's attendance punch status for the logged-in user
        $employee = \App\Models\Employee::with(['shift', 'location', 'department', 'designation', 'company'])->where('user_id', $user->id)->first();
        $todayAttendance = null;
        $monthlyGraceUsed = 0;
        $userAttendanceStats = [
            'present_days' => 0,
            'half_days' => 0,
            'leave_days' => 0,
            'late_arrivals' => 0,
        ];
        $recentPayslips = collect();
        $userResignation = null;

        if ($employee) {
            $todayAttendance = \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', \Carbon\Carbon::today())
                ->first();
            // Compute current month stats for the employee efficiently
            $monthAttendances = \App\Models\Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $now->year)
                ->whereMonth('attendance_date', $now->month)
                ->select(['id', 'status', 'is_late', 'used_grace_period'])
                ->get();
            $monthlyGraceUsed = $monthAttendances->where('used_grace_period', true)->count();

            $userAttendanceStats['present_days'] = $monthAttendances->where('status', 'present')->count();
            $userAttendanceStats['half_days'] = $monthAttendances->where('status', 'half_day')->count();
            $userAttendanceStats['late_arrivals'] = $monthAttendances->where('is_late', true)->count();

            // Count approved leaves this month
            $userAttendanceStats['leave_days'] = (float) \App\Models\LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $now->year)
                ->whereMonth('start_date', $now->month)
                ->sum('days_count');

            // Fetch user's own latest payslips
            $recentPayslips = \App\Models\Payslip::where('employee_id', $employee->id)
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->take(3)
                ->get();

            // Fetch active resignation
            $userResignation = \App\Models\Resignation::where('employee_id', $employee->id)
                ->latest()
                ->first();
        }

        return view('dashboard.index', compact(
            'user',
            'stats',
            'employee',
            'todayAttendance',
            'monthlyGraceUsed',
            'userAttendanceStats',
            'recentPayslips',
            'userResignation',
            'recentAttendances',
            'recentLeaveRequests',
            'recentEmployees'
        ));
    }
}
