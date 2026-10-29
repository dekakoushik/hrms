<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Resignation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAppApiController extends Controller
{
    /**
     * Get mobile dashboard comprehensive state
     */
    public function getDashboardState(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $employee = Employee::with(['shift', 'location', 'department', 'designation', 'company'])
            ->where('user_id', $user->id)
            ->first();

        $today = Carbon::today();
        $now = Carbon::now();

        // 1. Attendance state
        $todayAttendance = null;
        $monthlyGraceUsed = 0;
        $userStats = [
            'present_days' => 0,
            'half_days' => 0,
            'leave_days' => 0,
            'late_arrivals' => 0,
        ];
        $recentPayslips = [];
        $activeResignation = null;

        if ($employee) {
            $todayAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $today)
                ->first();

            $monthlyGraceUsed = Attendance::getGraceDaysUsedInMonth($employee->id);

            $monthAttendances = Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $now->year)
                ->whereMonth('attendance_date', $now->month)
                ->get();

            $userStats['present_days'] = $monthAttendances->where('status', 'present')->count();
            $userStats['half_days'] = $monthAttendances->where('status', 'half_day')->count();
            $userStats['late_arrivals'] = $monthAttendances->where('is_late', true)->count();
            $userStats['leave_days'] = (float) LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $now->year)
                ->whereMonth('start_date', $now->month)
                ->sum('days_count');

            $recentPayslips = Payslip::where('employee_id', $employee->id)
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->take(5)
                ->get();

            $activeResignation = Resignation::where('employee_id', $employee->id)
                ->latest()
                ->first();
        }

        // 2. Admin Global KPIs
        $adminStats = [
            'total_workforce' => Employee::count(),
            'active_workforce' => Employee::where('status', 'active')->count(),
            'today_attendance' => Attendance::whereDate('attendance_date', $today)->whereIn('status', ['present', 'half_day'])->count(),
            'pending_leaves' => LeaveRequest::where('status', 'pending')->count(),
            'pending_resignations' => Resignation::where('status', 'pending')->count(),
        ];

        return response()->json([
            'success' => true,
            'is_admin' => $isAdmin,
            'user' => $user,
            'employee' => $employee,
            'today_attendance' => $todayAttendance,
            'monthly_grace_used' => $monthlyGraceUsed,
            'user_stats' => $userStats,
            'admin_stats' => $adminStats,
            'recent_payslips' => $recentPayslips,
            'active_resignation' => $activeResignation,
        ]);
    }

    /**
     * Mobile Punch In
     */
    public function punchIn(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'No linked employee profile found.'], 422);
        }

        if ($employee->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Your profile is currently inactive.'], 403);
        }

        $today = Carbon::today();
        $existing = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($existing && $existing->punch_in_at) {
            return response()->json([
                'success' => false,
                'message' => "Punch in already recorded today at {$existing->punch_in_at->format('h:i A')}. Only allowed once per day.",
            ], 422);
        }

        $lat = $request->filled('latitude') ? (float) $request->input('latitude') : null;
        $lng = $request->filled('longitude') ? (float) $request->input('longitude') : null;

        $isFieldVisit = $request->boolean('is_field_visit') || $request->input('is_field_visit') == '1';
        $remarks = $request->input('remarks') ?: $request->input('field_visit_remarks');

        // Handle selfie upload if field visit or camera selfie provided
        $selfiePath = null;
        if ($request->hasFile('selfie') || $request->hasFile('field_visit_selfie')) {
            $file = $request->file('selfie') ?: $request->file('field_visit_selfie');
            $selfiePath = $file->store('selfies', 'public');
        } elseif ($request->filled('selfie_base64')) {
            $base64Image = $request->input('selfie_base64');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $data = substr($base64Image, strpos($base64Image, ',') + 1);
                $type = strtolower($type[1]);
                $data = base64_decode($data);
                if ($data !== false) {
                    $filename = 'selfies/' . uniqid('field_') . '.' . $type;
                    \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $data);
                    $selfiePath = $filename;
                }
            }
        }

        // Geofence check if fixed location (bypassed if field visit)
        $locationType = $isFieldVisit ? 'random' : ($employee->location_type ?: 'fixed');
        $locationId = null;
        $distance = null;

        if (!$isFieldVisit && $locationType === 'fixed' && $employee->location_id) {
            $loc = $employee->location;
            if ($loc && $lat !== null && $lng !== null) {
                $distance = Attendance::calculateHaversineDistance($lat, $lng, (float)$loc->latitude, (float)$loc->longitude);
                if ($distance > ($loc->radius_meters ?: 200)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Out of bounds! You are " . round($distance) . "m away from {$loc->name}. Punch in is only permitted within " . ($loc->radius_meters ?: 200) . "m.",
                    ], 422);
                }
                $locationId = $loc->id;
            }
        }

        // Shift grace period check
        $now = Carbon::now();
        $shift = $employee->shift;
        $status = 'present';
        $isLate = false;
        $lateMinutes = 0;
        $usedGrace = false;

        if ($shift && $shift->start_time) {
            $shiftStart = Carbon::today()->setTimeFromTimeString($shift->start_time);
            $graceMinutes = $shift->grace_period_minutes ?? 15;
            $graceCutoff = (clone $shiftStart)->addMinutes($graceMinutes);

            if ($now->greaterThan($graceCutoff)) {
                $monthlyGraceUsed = Attendance::getGraceDaysUsedInMonth($employee->id);
                if ($monthlyGraceUsed < 5) {
                    $usedGrace = true;
                    $status = 'present';
                    $isLate = true;
                    $lateMinutes = $now->diffInMinutes($shiftStart);
                } else {
                    $status = 'half_day';
                    $isLate = true;
                    $lateMinutes = $now->diffInMinutes($shiftStart);
                }
            }
        }

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'attendance_date' => $today,
            ],
            [
                'company_id' => $employee->company_id,
                'shift_id' => $employee->shift_id,
                'punch_in_at' => $now,
                'punch_in_lat' => $lat,
                'punch_in_lng' => $lng,
                'punch_in_location_type' => $locationType,
                'punch_in_location_id' => $locationId,
                'punch_in_distance_meters' => $distance,
                'status' => $status,
                'is_late' => $isLate,
                'late_minutes' => $lateMinutes,
                'used_grace_period' => $usedGrace,
                'is_field_visit' => $isFieldVisit,
                'field_visit_selfie' => $selfiePath,
                'field_visit_remarks' => $remarks,
                'field_visit_status' => $isFieldVisit ? 'approved' : null,
                'notes' => $isFieldVisit ? "Field Visit: " . ($remarks ?: 'No remarks') : null,
            ]
        );

        $msg = $isFieldVisit
            ? 'Field Visit marked successfully at ' . $now->format('h:i A') . '!'
            : 'Punch In marked successfully at ' . $now->format('h:i A') . ' (' . ucfirst($status) . ')';

        return response()->json([
            'success' => true,
            'message' => $msg,
            'attendance' => $attendance,
        ]);
    }

    /**
     * Mobile Punch Out
     */
    public function punchOut(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'No linked employee profile found.'], 422);
        }

        $today = Carbon::today();
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (!$attendance || !$attendance->punch_in_at) {
            return response()->json(['success' => false, 'message' => 'You must Punch In before marking Punch Out.'], 422);
        }

        $now = Carbon::now();
        $lat = $request->filled('latitude') ? (float) $request->input('latitude') : null;
        $lng = $request->filled('longitude') ? (float) $request->input('longitude') : null;

        $workedHours = round($attendance->punch_in_at->diffInMinutes($now) / 60, 2);

        // Early exit check
        $isEarly = false;
        $earlyMinutes = 0;
        $status = $attendance->status;

        if ($employee->shift && $employee->shift->end_time) {
            $shiftEnd = Carbon::today()->setTimeFromTimeString($employee->shift->end_time);
            if ($now->lessThan($shiftEnd)) {
                $isEarly = true;
                $earlyMinutes = $now->diffInMinutes($shiftEnd);
                $status = 'half_day';
            }
        }

        $attendance->update([
            'punch_out_at' => $now,
            'punch_out_latitude' => $lat,
            'punch_out_longitude' => $lng,
            'working_hours' => $workedHours,
            'is_early_exit' => $isEarly,
            'early_minutes' => $earlyMinutes,
            'status' => $status,
            'punch_out_count' => ($attendance->punch_out_count ?? 0) + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Punch Out marked at ' . $now->format('h:i A') . ". Total hours: {$workedHours} hrs.",
            'attendance' => $attendance->fresh(),
        ]);
    }

    /**
     * Attendance history (Scoped for regular users, all for admins)
     */
    public function getAttendanceHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $employee = Employee::where('user_id', $user->id)->first();

        $query = Attendance::with(['employee:id,full_name,employee_id', 'shift:id,name'])
            ->orderBy('attendance_date', 'desc')
            ->orderBy('punch_in_at', 'desc');

        if (!$isAdmin) {
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                return response()->json(['success' => true, 'attendances' => []]);
            }
        }

        $attendances = $query->take(30)->get();

        return response()->json([
            'success' => true,
            'attendances' => $attendances,
        ]);
    }

    /**
     * Leave Manager API
     */
    public function getLeaveData(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $employee = Employee::with(['leaveBalances.leaveType'])->where('user_id', $user->id)->first();

        $leaveTypes = LeaveType::all();

        $historyQuery = LeaveRequest::with(['employee:id,full_name,employee_id', 'leaveType:id,name,code'])
            ->orderBy('created_at', 'desc');

        if (!$isAdmin) {
            if ($employee) {
                $historyQuery->where('employee_id', $employee->id);
            } else {
                $historyQuery->whereRaw('1 = 0');
            }
        }

        $leaves = $historyQuery->take(25)->get();

        return response()->json([
            'success' => true,
            'leave_types' => $leaveTypes,
            'employee' => $employee,
            'leave_requests' => $leaves,
        ]);
    }

    /**
     * Apply for leave
     */
    public function applyLeave(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'No employee profile linked.'], 422);
        }

        $validated = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['nullable', 'boolean'],
            'half_day_type' => ['nullable', 'in:first_half,second_half'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $isHalf = (bool)($validated['is_half_day'] ?? false);
        $daysCount = $isHalf ? 0.5 : ($start->diffInDays($end) + 1);

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $start,
            'end_date' => $end,
            'is_half_day' => $isHalf,
            'half_day_type' => $validated['half_day_type'] ?? null,
            'days_count' => $daysCount,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Leave application submitted for {$daysCount} days.",
            'leave' => $leave,
        ]);
    }

    /**
     * Get Payslips (Only own for regular user, all for admin)
     */
    public function getPayslips(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $employee = Employee::where('user_id', $user->id)->first();

        $query = Payslip::with(['employee:id,full_name,employee_id', 'company:id,name'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        if (!$isAdmin) {
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                return response()->json(['success' => true, 'payslips' => []]);
            }
        }

        $payslips = $query->take(20)->get();

        return response()->json([
            'success' => true,
            'payslips' => $payslips,
        ]);
    }

    /**
     * Resignation / Exit API
     */
    public function getResignations(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $employee = Employee::where('user_id', $user->id)->first();

        $query = Resignation::with(['employee:id,full_name,employee_id'])
            ->orderBy('created_at', 'desc');

        if (!$isAdmin) {
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                return response()->json(['success' => true, 'resignations' => []]);
            }
        }

        $resignations = $query->take(20)->get();

        return response()->json([
            'success' => true,
            'resignations' => $resignations,
        ]);
    }

    /**
     * Submit resignation
     */
    public function submitResignation(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'No employee profile linked.'], 422);
        }

        $existing = Resignation::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'An active resignation request already exists.'], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'requested_last_working_day' => ['required', 'date', 'after_or_equal:today'],
            'notice_period_days' => ['nullable', 'integer', 'min:0'],
        ]);

        $resignation = Resignation::create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'resignation_date' => Carbon::today(),
            'reason' => $validated['reason'],
            'requested_last_working_day' => Carbon::parse($validated['requested_last_working_day']),
            'notice_period_days' => (int)($validated['notice_period_days'] ?? 30),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resignation submitted successfully to HR.',
            'resignation' => $resignation,
        ]);
    }

    /**
     * Get detailed Payslip data for PDF rendering & download
     */
    public function getPayslipDetail(Request $request, Payslip $payslip): JsonResponse
    {
        $user = $request->user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$isAdmin && (!$employee || $payslip->employee_id !== $employee->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to payslip.'], 403);
        }

        $payslip->load([
            'employee.department',
            'employee.designation',
            'employee.company',
            'employee.location',
            'company',
            'payrollRun',
        ]);

        return response()->json([
            'success' => true,
            'payslip' => $payslip,
        ]);
    }

    /**
     * Get workplace contacts for mobile contact directory
     */
    public function getContacts(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $departmentId = $request->query('department_id');

        $query = Employee::with(['department', 'designation', 'location', 'company', 'manager'])
            ->where('status', 'active');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('work_email', 'like', "%{$search}%");
            });
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $contacts = $query->orderBy('full_name')->get();
        $departments = \App\Models\Department::has('employees')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'contacts' => $contacts,
            'departments' => $departments,
        ]);
    }

    /**
     * Get HR & Policies for mobile viewer
     */
    public function getPolicies(Request $request): JsonResponse
    {
        $folderId = $request->query('folder_id');
        $search = $request->query('search');

        $folders = \App\Models\PolicyFolder::withCount('documents')->orderBy('name')->get();

        $query = \App\Models\PolicyDocument::with(['folder', 'uploader']);

        if ($folderId) {
            $query->where('folder_id', $folderId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        $documents = $query->latest()->get();

        return response()->json([
            'success' => true,
            'folders' => $folders,
            'documents' => $documents,
        ]);
    }
}

