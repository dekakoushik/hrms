<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\Payslip;
use App\Models\ItTicket;
use App\Models\CompanyDocument;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Notice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get real-time dashboard metrics (Tailored for Admin/Superadmin vs Employee self-service)
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdminOrSuper = in_array($user->role, ['superadmin', 'admin']);
        $today = Carbon::today()->toDateString();
        $currentMonth = Carbon::today()->month;
        $currentYear = Carbon::today()->year;

        // Resolve personal employee record
        $employee = Employee::with(['shift', 'location', 'company', 'department', 'designation'])
            ->where('user_id', $user->id)
            ->first();

        // ----------------------------------------------------
        // A. EMPLOYEE SELF-SERVICE DASHBOARD
        // ----------------------------------------------------
        if (!$isAdminOrSuper) {
            if (!$employee) {
                return response()->json([
                    'role' => 'employee',
                    'employee' => null,
                    'today_attendance' => null,
                    'leave_balances' => [],
                    'pending_leaves_count' => 0,
                    'my_tickets' => [],
                    'upcoming_holidays' => [],
                    'recent_history' => [],
                ]);
            }

            // 1. Today's Attendance Punch State
            $todayAttendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $today)
                ->first();

            // 2. Personal Month-to-Date Attendance Summary
            $monthStart = Carbon::today()->startOfMonth()->toDateString();
            $myAttendancesThisMonth = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$monthStart, $today])
                ->get();

            $totalPresentDays = $myAttendancesThisMonth->whereIn('status', ['present', 'evaluating'])->count();
            $totalLateDays = $myAttendancesThisMonth->where('is_late', true)->count();
            $totalHalfDays = $myAttendancesThisMonth->where('status', 'half_day')->count();
            $totalWorkingHours = round($myAttendancesThisMonth->sum('total_working_hours'), 2);

            // 3. Leave Balances for Current Year
            $leaveBalances = LeaveBalance::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('year', $currentYear)
                ->get();

            $pendingLeavesCount = LeaveApplication::where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->count();

            // 4. Employee's IT Tickets
            $myTickets = ItTicket::where('employee_id', $employee->id)
                ->orderByDesc('created_at')
                ->take(5)
                ->get();

            $myTicketsStats = [
                'open' => ItTicket::where('employee_id', $employee->id)->where('status', 'open')->count(),
                'in_progress' => ItTicket::where('employee_id', $employee->id)->where('status', 'in_progress')->count(),
                'resolved' => ItTicket::where('employee_id', $employee->id)->whereIn('status', ['resolved', 'closed'])->count(),
            ];

            // 5. Upcoming Company Holidays
            $upcomingHolidays = Holiday::where('date', '>=', $today)
                ->orderBy('date', 'asc')
                ->take(4)
                ->get();

            // 6. Recent Personal Punches
            $recentHistory = Attendance::where('employee_id', $employee->id)
                ->orderByDesc('date')
                ->take(7)
                ->get();

            // 7. Latest Payslip available
            $latestPayslip = Payslip::where('employee_id', $employee->id)
                ->orderByDesc('id')
                ->first();

            // 8. Official Notice Board Updates
            $notices = Notice::with(['creator:id,name,role', 'department:id,name'])
                ->where('is_active', true)
                ->where(function ($q) use ($today) {
                    $q->whereNull('publish_date')->orWhere('publish_date', '<=', $today);
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $today);
                })
                ->where(function ($q) use ($employee) {
                    $q->where('target_audience', 'all');
                    if ($employee && $employee->department_id) {
                        $q->orWhere(function ($sub) use ($employee) {
                            $sub->where('target_audience', 'department')
                                ->where('department_id', $employee->department_id);
                        });
                    }
                })
                ->orderBy('is_pinned', 'desc')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            return response()->json([
                'role' => 'employee',
                'employee' => $employee,
                'today_attendance' => $todayAttendance,
                'month_stats' => [
                    'present_days' => $totalPresentDays,
                    'late_days' => $totalLateDays,
                    'half_days' => $totalHalfDays,
                    'total_working_hours' => $totalWorkingHours,
                    'month_name' => Carbon::today()->format('F Y'),
                ],
                'leave_balances' => $leaveBalances,
                'pending_leaves_count' => $pendingLeavesCount,
                'my_tickets' => $myTickets,
                'my_tickets_stats' => $myTicketsStats,
                'upcoming_holidays' => $upcomingHolidays,
                'recent_history' => $recentHistory,
                'latest_payslip' => $latestPayslip,
                'notices' => $notices,
            ]);
        }

        // ----------------------------------------------------
        // B. ADMIN & SUPERADMIN EXECUTIVE DASHBOARD
        // ----------------------------------------------------
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $probationEmployees = Employee::where('status', 'active')
            ->whereNotNull('probation_duration')
            ->count();

        // Today's Attendance Real-Time Snapshot
        $presentToday = Attendance::where('date', $today)
            ->whereIn('status', ['present', 'half_day', 'evaluating'])
            ->count();
        $lateArrivalsToday = Attendance::where('date', $today)
            ->where('is_late', true)
            ->count();
        $evaluatingToday = Attendance::where('date', $today)
            ->where('status', 'evaluating')
            ->count();
        $halfDayToday = Attendance::where('date', $today)
            ->where('status', 'half_day')
            ->count();

        $attendanceRate = $activeEmployees > 0 
            ? round(($presentToday / $activeEmployees) * 100, 1) 
            : 0;

        // Leave Applications
        $pendingLeaves = LeaveApplication::where('status', 'pending')->count();
        $approvedLeavesThisMonth = LeaveApplication::where('status', 'approved')
            ->whereMonth('start_date', $currentMonth)
            ->whereYear('start_date', $currentYear)
            ->count();

        // IT Helpdesk Tickets
        $openTickets = ItTicket::where('status', 'open')->count();
        $inProgressTickets = ItTicket::where('status', 'in_progress')->count();
        $criticalTickets = ItTicket::whereIn('status', ['open', 'in_progress'])
            ->where('priority', 'critical')
            ->count();

        // Compliance & Documents
        $totalDocuments = CompanyDocument::where('is_active', true)->count();

        // Monthly Payroll Estimate / Processed
        $latestPayslips = Payslip::whereHas('payroll', function ($q) use ($currentYear, $currentMonth) {
            $q->where('year', $currentYear)->where('month', $currentMonth);
        })->get();

        $processedPayrollCount = $latestPayslips->count();
        $monthlyDisbursementEstimate = $latestPayslips->sum('net_salary');

        if ($monthlyDisbursementEstimate == 0) {
            $monthlyDisbursementEstimate = Employee::where('status', 'active')->sum('net_salary');
        }

        // Department Distribution
        $departmentDistribution = Department::withCount('employees')
            ->orderByDesc('employees_count')
            ->take(6)
            ->get(['id', 'name', 'employees_count']);

        // Recent Attendance Activity Stream
        $recentAttendance = Attendance::with(['employee.department', 'employee.designation'])
            ->where('date', $today)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        // Weekly Attendance Trend (Last 7 Days)
        $weeklyTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $dayStr = $d->toDateString();
            $dayPresent = Attendance::where('date', $dayStr)
                ->whereIn('status', ['present', 'half_day', 'evaluating'])
                ->count();
            $dayLate = Attendance::where('date', $dayStr)
                ->where('is_late', true)
                ->count();

            $weeklyTrend[] = [
                'day' => $d->format('D'),
                'date' => $d->format('M d'),
                'present' => $dayPresent,
                'late' => $dayLate,
            ];
        }

        // Notice Board (Recent Official Circulars)
        $notices = Notice::with(['creator:id,name,role', 'department:id,name'])
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'role' => 'admin',
            'workforce' => [
                'total_employees' => $totalEmployees,
                'active_employees' => $activeEmployees,
                'probation_employees' => $probationEmployees,
            ],
            'attendance_today' => [
                'present' => $presentToday,
                'late' => $lateArrivalsToday,
                'evaluating' => $evaluatingToday,
                'half_day' => $halfDayToday,
                'attendance_rate' => $attendanceRate,
            ],
            'leaves' => [
                'pending_leaves' => $pendingLeaves,
                'approved_this_month' => $approvedLeavesThisMonth,
            ],
            'it_helpdesk' => [
                'open' => $openTickets,
                'in_progress' => $inProgressTickets,
                'critical' => $criticalTickets,
            ],
            'payroll' => [
                'disbursement_estimate' => round($monthlyDisbursementEstimate, 2),
                'processed_records' => $processedPayrollCount,
                'month_name' => Carbon::today()->format('F Y'),
            ],
            'compliance' => [
                'active_documents' => $totalDocuments,
            ],
            'department_distribution' => $departmentDistribution,
            'recent_attendance' => $recentAttendance,
            'weekly_trend' => $weeklyTrend,
            'notices' => $notices,
        ]);
    }
}
