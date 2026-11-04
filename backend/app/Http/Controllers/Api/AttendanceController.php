<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FieldVisit;
use App\Models\Location;
use App\Models\AppNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Calculate Distance between two GPS points using Haversine Formula (in meters).
     */
    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }

    /**
     * Helper to resolve the authenticated employee record.
     * Looks up strictly by the authenticated user's ID or email.
     * If a user (e.g. system superadmin) has no personal employee roster profile,
     * it returns null rather than hijacking another employee's profile!
     */
    private function getResolvedEmployee(Request $request): ?Employee
    {
        $user = $request->user();

        // 1. Try finding employee linked via user_id
        $employee = Employee::with(['shift', 'location', 'company', 'department', 'designation'])
            ->where('user_id', $user->id)
            ->first();

        // 2. If not linked by user_id, try matching by email
        if (!$employee && $user->email) {
            $employee = Employee::with(['shift', 'location', 'company', 'department', 'designation'])
                ->where('email', $user->email)
                ->first();
        }

        return $employee;
    }

    /**
     * Resolve the employee's local timezone (e.g. from company or fallback to Asia/Kolkata).
     */
    private function getEmployeeTimezone(?Employee $employee): string
    {
        return $employee?->company?->timezone ?: 'Asia/Kolkata';
    }

    /**
     * Get Today's Attendance & Field Visits Status for the current user/employee.
     */
    public function today(Request $request): JsonResponse
    {
        $employee = $this->getResolvedEmployee($request);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'No employee profile linked to this user account.',
            ], 404);
        }

        $tz = $this->getEmployeeTimezone($employee);
        $today = Carbon::now($tz)->toDateString();

        $attendance = Attendance::with(['punchInLocation', 'punchOutLocation'])
            ->where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        $fieldVisits = FieldVisit::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'name' => $employee->full_name,
                'email' => $employee->email,
                'usertype' => $employee->usertype,
                'attendance_location_type' => $employee->attendance_location_type,
                'shift' => $employee->shift,
                'assigned_location' => $employee->location,
            ],
            'attendance' => $attendance,
            'field_visits' => $fieldVisits,
            'server_time' => Carbon::now($tz)->toIso8601String(),
        ]);
    }

    /**
     * Get the authenticated employee's personal attendance history ledger.
     * Strictly scopes records to the requesting employee's ID.
     */
    public function myHistory(Request $request): JsonResponse
    {
        $employee = $this->getResolvedEmployee($request);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'No employee profile linked to this user account.',
            ], 404);
        }

        $month = $request->query('month');
        $year = $request->query('year');

        $query = Attendance::with(['punchInLocation', 'punchOutLocation'])
            ->where('employee_id', $employee->id)
            ->orderBy('date', 'desc');

        if ($year && $month) {
            $startDate = Carbon::createFromDate((int)$year, (int)$month, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromDate((int)$year, (int)$month, 1)->endOfMonth()->toDateString();
            $query->whereDate('date', '>=', $startDate)->whereDate('date', '<=', $endDate);
        }

        $records = $query->paginate(31);

        // Stats for current month
        $currentMonthStart = Carbon::now()->startOfMonth()->toDateString();
        $currentMonthEnd = Carbon::now()->endOfMonth()->toDateString();

        $monthStats = [
            'total_present' => Attendance::where('employee_id', $employee->id)
                ->whereDate('date', '>=', $currentMonthStart)
                ->whereDate('date', '<=', $currentMonthEnd)
                ->where('status', 'present')
                ->count(),
            'total_late' => Attendance::where('employee_id', $employee->id)
                ->whereDate('date', '>=', $currentMonthStart)
                ->whereDate('date', '<=', $currentMonthEnd)
                ->where('status', 'late')
                ->count(),
            'total_half_day' => Attendance::where('employee_id', $employee->id)
                ->whereDate('date', '>=', $currentMonthStart)
                ->whereDate('date', '<=', $currentMonthEnd)
                ->where('status', 'half_day')
                ->count(),
            'total_hours' => round(Attendance::where('employee_id', $employee->id)
                ->whereDate('date', '>=', $currentMonthStart)
                ->whereDate('date', '<=', $currentMonthEnd)
                ->sum('total_working_hours'), 2),
        ];

        return response()->json([
            'success' => true,
            'stats' => $monthStats,
            'data' => $records,
        ]);
    }

    /**
     * Mark Punch In (Allowed strictly once per calendar day).
     */
    public function punchIn(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'note' => 'nullable|string|max:500',
        ]);

        $employee = $this->getResolvedEmployee($request);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found.',
            ], 404);
        }

        $tz = $this->getEmployeeTimezone($employee);
        $today = Carbon::now($tz)->toDateString();
        $now = Carbon::now($tz);
        $currentTime = $now->format('H:i:s');

        // Check if already punched in today (strictly once in a day)
        $existing = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing && $existing->punch_in_time) {
            return response()->json([
                'success' => false,
                'message' => "Punch In already recorded for today at {$existing->punch_in_time}. Punch In is allowed once per day.",
                'attendance' => $existing,
            ], 422);
        }

        $locationType = $employee->attendance_location_type ?? 'fixed';
        $assignedLocation = $employee->location;
        $distanceMeters = null;
        $punchInLocationId = null;

        // Geofence Validation for Fixed Location Type
        if ($locationType === 'fixed' && $assignedLocation) {
            if (!$request->filled('latitude') || !$request->filled('longitude')) {
                return response()->json([
                    'success' => false,
                    'message' => "GPS coordinates are required for fixed work location ({$assignedLocation->name}). Please enable location access.",
                ], 422);
            }

            $userLat = (float) $request->latitude;
            $userLon = (float) $request->longitude;

            $distanceMeters = $this->calculateHaversineDistance(
                $userLat,
                $userLon,
                (float) $assignedLocation->latitude,
                (float) $assignedLocation->longitude
            );

            $punchInLocationId = $assignedLocation->id;
            $allowedRadius = $assignedLocation->radius_meters ?: 100;

            if ($assignedLocation->enforce_geofence && $distanceMeters > $allowedRadius) {
                return response()->json([
                    'success' => false,
                    'message' => "Punch in failed: You are {$distanceMeters}m away from {$assignedLocation->name}. Maximum permitted radius is {$allowedRadius}m.",
                    'distance_meters' => $distanceMeters,
                    'allowed_radius' => $allowedRadius,
                ], 422);
            }
        }

        // Determine Punctuality Status based on assigned shift
        $punchStatus = 'present';
        if ($employee->shift && $employee->shift->start_time) {
            $shiftStart = Carbon::createFromTimeString($employee->shift->start_time, $tz);
            $graceMinutes = $employee->shift->grace_period_minutes ?: 15;
            $halfDayMinutes = $employee->shift->half_day_minutes ?: 240;

            $diffMinutes = $shiftStart->diffInMinutes($now, false); // positive if late

            if ($diffMinutes > $halfDayMinutes) {
                $punchStatus = 'half_day';
            } elseif ($diffMinutes > $graceMinutes) {
                $punchStatus = 'late';
            }
        }

        // Determine Initial Attendance Status:
        // When clocked in during an ongoing shift, status is 'evaluating'.
        // For random/field employees, immediate credit applies.
        $initialStatus = ($locationType === 'random') ? $punchStatus : 'evaluating';

        if ($existing) {
            $existing->update([
                'punch_in_time' => $currentTime,
                'punch_in_latitude' => $request->latitude,
                'punch_in_longitude' => $request->longitude,
                'punch_in_location_id' => $punchInLocationId,
                'punch_in_distance_meters' => $distanceMeters,
                'punch_in_status' => $punchStatus,
                'punch_in_note' => $request->note,
                'status' => $initialStatus,
                'location_type' => $locationType,
            ]);
            $attendance = $existing;
        } else {
            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'date' => $today,
                'punch_in_time' => $currentTime,
                'punch_in_latitude' => $request->latitude,
                'punch_in_longitude' => $request->longitude,
                'punch_in_location_id' => $punchInLocationId,
                'punch_in_distance_meters' => $distanceMeters,
                'punch_in_status' => $punchStatus,
                'punch_in_note' => $request->note,
                'status' => $initialStatus,
                'location_type' => $locationType,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $locationType === 'random'
                ? "Punch in successful (Random / Field Mode)."
                : "Punched in successfully at {$currentTime} (" . strtoupper($initialStatus) . "). Complete your full shift before final status.",
            'attendance' => $attendance->load(['punchInLocation', 'employee']),
        ]);
    }

    /**
     * Mark Punch Out (Can be marked multiple times in a day to update departure / total hours).
     */
    public function punchOut(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'note' => 'nullable|string|max:500',
        ]);

        $employee = $this->getResolvedEmployee($request);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found.',
            ], 404);
        }

        $tz = $this->getEmployeeTimezone($employee);
        $today = Carbon::now($tz)->toDateString();
        $now = Carbon::now($tz);
        $currentTime = $now->format('H:i:s');

        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance || !$attendance->punch_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot punch out: No punch in record found for today.',
            ], 422);
        }

        $assignedLocation = $employee->location;
        $distanceMeters = null;
        $punchOutLocationId = null;

        // Optional geofence verification on Punch Out if fixed location
        if ($attendance->location_type === 'fixed' && $assignedLocation) {
            if ($request->filled('latitude') && $request->filled('longitude')) {
                $userLat = (float) $request->latitude;
                $userLon = (float) $request->longitude;

                $distanceMeters = $this->calculateHaversineDistance(
                    $userLat,
                    $userLon,
                    (float) $assignedLocation->latitude,
                    (float) $assignedLocation->longitude
                );

                $punchOutLocationId = $assignedLocation->id;
                $allowedRadius = $assignedLocation->radius_meters ?: 100;

                if ($assignedLocation->enforce_geofence && $distanceMeters > $allowedRadius) {
                    return response()->json([
                        'success' => false,
                        'message' => "Punch out failed: You are {$distanceMeters}m away from {$assignedLocation->name}. Maximum permitted radius is {$allowedRadius}m.",
                        'distance_meters' => $distanceMeters,
                    ], 422);
                }
            }
        }

        // Calculate total hours from initial punch_in to latest punch_out
        $inTime = Carbon::createFromTimeString($attendance->punch_in_time, $tz);
        $totalHours = round($inTime->diffInMinutes($now) / 60, 2);

        // Evaluate Final Attendance Status upon punch out:
        // Check shift requirements:
        // Full day required hours: full_day_minutes (default 480 mins / 8 hrs)
        // Half day minimum hours: half_day_minutes (default 240 mins / 4 hrs)
        $shift = $employee->shift;
        $fullDayHours = $shift && $shift->full_day_minutes ? ($shift->full_day_minutes / 60) : 8.0;
        $halfDayHours = $shift && $shift->half_day_minutes ? ($shift->half_day_minutes / 60) : 4.0;

        $finalStatus = 'present';
        if ($totalHours < $halfDayHours) {
            // Worked less than half-day minimum: mark as half_day or absent based on policy
            $finalStatus = 'half_day';
        } elseif ($totalHours < $fullDayHours) {
            // Not completing the full shift hours results in a Half Day mark
            $finalStatus = 'half_day';
        } else {
            // Full shift hours achieved; retain late mark if punch-in was late, otherwise present
            $finalStatus = ($attendance->punch_in_status === 'late') ? 'late' : 'present';
        }

        $attendance->update([
            'punch_out_time' => $currentTime,
            'punch_out_latitude' => $request->latitude,
            'punch_out_longitude' => $request->longitude,
            'punch_out_location_id' => $punchOutLocationId,
            'punch_out_distance_meters' => $distanceMeters,
            'punch_out_note' => $request->note ?: $attendance->punch_out_note,
            'total_working_hours' => $totalHours,
            'status' => $finalStatus,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Punched out successfully at {$currentTime}. Total working time: {$totalHours} hrs (" . strtoupper($finalStatus) . ").",
            'attendance' => $attendance->load(['punchInLocation', 'punchOutLocation', 'employee']),
        ]);
    }

    /**
     * Submit Field Visit with GPS Location, Camera Selfie, and Remarks.
     */
    public function recordFieldVisit(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'client_name' => 'nullable|string|max:255',
            'remarks' => 'required|string|min:5|max:1000',
            'selfie_image' => 'required|string', // Base64 data URI
        ]);

        $employee = $this->getResolvedEmployee($request);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee profile not found.',
            ], 404);
        }

        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now()->format('H:i:s');

        $visit = FieldVisit::create([
            'employee_id' => $employee->id,
            'date' => $today,
            'visit_time' => $currentTime,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'location_name' => $request->location_name ?: 'Client / Field Site',
            'address' => $request->address,
            'client_name' => $request->client_name,
            'remarks' => $request->remarks,
            'selfie_image' => $request->selfie_image,
            'status' => 'submitted',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Field visit with GPS verification and selfie recorded successfully.',
            'field_visit' => $visit->load('employee'),
        ], 201);
    }

    /**
     * Superadmin Attendance Ledger & Directory
     * Search, filter by date, company, department, status, location type.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        $companyId = $request->query('company_id');
        $departmentId = $request->query('department_id');
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Attendance::with([
            'employee.company',
            'employee.department',
            'employee.designation',
            'employee.shift',
            'punchInLocation',
            'punchOutLocation',
        ]);

        if ($date) {
            $query->where('date', $date);
        }

        if ($companyId) {
            $query->whereHas('employee', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $attendances = $query->orderBy('created_at', 'desc')->paginate(50);

        // Summary stats for the selected date
        $totalPresent = Attendance::where('date', $date)->whereIn('status', ['present', 'late'])->count();
        $totalLate = Attendance::where('date', $date)->where('status', 'late')->count();
        $totalRandom = Attendance::where('date', $date)->where('location_type', 'random')->count();
        $totalFieldVisits = FieldVisit::where('date', $date)->count();

        return response()->json([
            'success' => true,
            'date' => $date,
            'stats' => [
                'total_present' => $totalPresent,
                'total_late' => $totalLate,
                'total_random_punches' => $totalRandom,
                'total_field_visits' => $totalFieldVisits,
            ],
            'data' => $attendances,
        ]);
    }

    /**
     * Superadmin Field Visits List & Verification
     */
    public function adminFieldVisits(Request $request): JsonResponse
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        $employeeId = $request->query('employee_id');

        $query = FieldVisit::with(['employee.department', 'employee.company'])
            ->orderBy('created_at', 'desc');

        if ($date) {
            $query->where('date', $date);
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $visits = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $visits,
        ]);
    }

    /**
     * Admin/Superadmin regularize attendance
     * Can fix late marks, mispunches (punch-in without punch-out or vice-versa),
     * or create/adjust retroactive attendance for absent days.
     */
    public function regularize(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Only Admin or Superadmin can regularize attendance records.',
            ], 403);
        }

        $validated = $request->validate([
            'attendance_id' => 'nullable|exists:attendances,id',
            'employee_id' => 'required_without:attendance_id|nullable|exists:employees,id',
            'date' => 'required_without:attendance_id|nullable|date',
            'status' => 'required|in:present,late,half_day,absent,on_leave,evaluating',
            'punch_in_time' => 'nullable|string',
            'punch_out_time' => 'nullable|string',
            'regularization_reason' => 'required|string|max:100', // late, mispunch, outdoor_duty, biometric_failure, other
            'regularization_remarks' => 'required|string|max:500',
        ]);

        $attendance = null;
        if (!empty($validated['attendance_id'])) {
            $attendance = Attendance::findOrFail($validated['attendance_id']);
        } else {
            $attendance = Attendance::firstOrNew([
                'employee_id' => $validated['employee_id'],
                'date' => $validated['date'],
            ]);
        }

        // Normalize time inputs to H:i:s or null
        $normalizeTime = function (?string $timeStr): ?string {
            if (empty($timeStr)) return null;
            $timeStr = trim($timeStr);
            if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $timeStr)) {
                return Carbon::createFromFormat('H:i:s', $timeStr)->format('H:i:s');
            }
            if (preg_match('/^\d{1,2}:\d{2}$/', $timeStr)) {
                return Carbon::createFromFormat('H:i', $timeStr)->format('H:i:s');
            }
            try {
                return Carbon::parse($timeStr)->format('H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        };

        $punchInTime = $normalizeTime($validated['punch_in_time'] ?? null);
        $punchOutTime = $normalizeTime($validated['punch_out_time'] ?? null);

        // Calculate total working hours if punch_in and punch_out provided
        $totalWorkingHours = 0;
        if (!empty($punchInTime) && !empty($punchOutTime)) {
            $in = Carbon::parse($punchInTime);
            $out = Carbon::parse($punchOutTime);
            if ($out->greaterThan($in)) {
                $totalWorkingHours = round($out->diffInMinutes($in) / 60, 2);
            }
        } elseif ($attendance->punch_in_time && !empty($punchOutTime)) {
            $in = Carbon::parse($attendance->punch_in_time);
            $out = Carbon::parse($punchOutTime);
            if ($out->greaterThan($in)) {
                $totalWorkingHours = round($out->diffInMinutes($in) / 60, 2);
            }
        }

        $attendance->status = $validated['status'];
        if ($punchInTime !== null) {
            $attendance->punch_in_time = $punchInTime;
        }
        if ($punchOutTime !== null) {
            $attendance->punch_out_time = $punchOutTime;
        }
        if ($totalWorkingHours > 0) {
            $attendance->total_working_hours = $totalWorkingHours;
        }

        // Regularization audit metadata
        $attendance->is_regularized = true;
        $attendance->regularization_reason = $validated['regularization_reason'];
        $attendance->regularization_remarks = $validated['regularization_remarks'];
        $attendance->regularized_by_user_id = $user->id;
        $attendance->regularized_at = now();

        $attendance->save();

        // Load relationships for front-end refresh
        $attendance->load(['employee.department', 'employee.company', 'regularizedByUser']);

        // Send Notification to Employee
        if ($attendance->employee && $attendance->employee->user_id) {
            $dateFormatted = Carbon::parse($attendance->date)->format('M d, Y');
            AppNotification::sendToUser(
                $attendance->employee->user_id,
                'attendance_regularized',
                'Attendance Record Regularized',
                "Your attendance for {$dateFormatted} has been regularized to {$attendance->status} by Administration.",
                '/attendance',
                'clock',
                [
                    'attendance_id' => $attendance->id,
                    'date' => $attendance->date,
                    'status' => $attendance->status,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance successfully regularized.',
            'data' => $attendance,
        ]);
    }
}

