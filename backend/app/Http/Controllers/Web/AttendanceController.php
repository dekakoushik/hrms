<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Attendance Management Dashboard / Records Table (Module 4)
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $employee = Employee::where('user_id', $user->id)->first();
        $activeCompanyId = session('active_company_id');

        $query = Attendance::with(['employee.user', 'employee.department', 'employee.designation', 'shift', 'punchInLocation', 'punchOutLocation'])
            ->orderBy('attendance_date', 'desc')
            ->orderBy('punch_in_at', 'desc');

        if ($activeCompanyId) {
            $query->where('company_id', $activeCompanyId);
        }

        // Regular users/employees are strictly scoped to their own attendance records
        if (!$isAdmin) {
            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filter by date or date range
        $date = $request->input('date');
        if ($date) {
            $query->whereDate('attendance_date', $date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search') && $isAdmin) {
            $search = $request->input('search');
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $attendances = $query->paginate(20)->withQueryString();

        // Calculate Today's Stats
        $today = Carbon::today()->format('Y-m-d');
        $todayQuery = Attendance::whereDate('attendance_date', $today);
        if ($activeCompanyId) {
            $todayQuery->where('company_id', $activeCompanyId);
        }
        if (!$isAdmin && $employee) {
            $todayQuery->where('employee_id', $employee->id);
        }

        $presentCount = (clone $todayQuery)->whereIn('status', ['present', 'late'])->count();
        $halfDayCount = (clone $todayQuery)->where('status', 'half_day')->count();
        $lateCount = (clone $todayQuery)->where('is_late', true)->count();
        $totalPunched = (clone $todayQuery)->count();

        return view('attendance.index', compact(
            'attendances',
            'date',
            'presentCount',
            'halfDayCount',
            'lateCount',
            'totalPunched',
            'isAdmin',
            'employee'
        ));
    }

    /**
     * Mark Attendance Punch In (Only allowed once per day)
     */
    public function punchIn(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return $this->formatResponse($request, false, 'No employee record associated with your user account.');
        }

        if ($employee->status !== 'active') {
            return $this->formatResponse($request, false, 'Your employee profile is currently inactive.');
        }

        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_field_visit' => ['nullable'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'selfie' => ['nullable', 'file', 'image', 'max:10240'],
            'selfie_base64' => ['nullable', 'string'],
        ]);

        $isFieldVisit = $request->boolean('is_field_visit') || $request->input('is_field_visit') == '1';
        $remarks = $request->input('remarks');

        // Handle selfie storage (either file upload or webcam base64)
        $selfiePath = null;
        if ($request->hasFile('selfie')) {
            $selfiePath = $request->file('selfie')->store('selfies', 'public');
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

        if ($isFieldVisit) {
            if (!$request->filled('latitude') || !$request->filled('longitude')) {
                return $this->formatResponse($request, false, 'GPS location coordinates are required to mark a field visit.');
            }
            if (empty($remarks)) {
                return $this->formatResponse($request, false, 'Remarks are required when marking a field visit.');
            }
            if (!$selfiePath) {
                return $this->formatResponse($request, false, 'A selfie photo is required when marking a field visit.');
            }
        }

        $lat = !empty($validated['latitude']) ? (float) $validated['latitude'] : null;
        $lng = !empty($validated['longitude']) ? (float) $validated['longitude'] : null;
        $now = Carbon::now();
        $today = Carbon::today();

        // 1. Check if employee has already punched in today (Only once per day allowed)
        $existing = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($existing && $existing->punch_in_at) {
            return $this->formatResponse($request, false, "Punch In already recorded today at {$existing->punch_in_at->format('h:i A')}. Punch In is only permitted once per day.");
        }

        // 2. Geofence Verification (bypassed if field visit)
        $locationType = $isFieldVisit ? 'random' : ($employee->location_type ?: 'fixed');
        $locationId = null;
        $distanceMeters = null;

        if (!$isFieldVisit && $locationType === 'fixed') {
            if (!$lat || !$lng) {
                return $this->formatResponse($request, false, 'GPS location is required to verify the 200m office geofence. Please allow location access.');
            }

            // Find employee's assigned location or fallback to active company location
            $targetLocation = $employee->location ?: Location::where('company_id', $employee->company_id)->where('is_active', true)->first();

            if (!$targetLocation) {
                // If no specific location configured yet, fallback to any active location
                $targetLocation = Location::where('is_active', true)->first();
            }

            if ($targetLocation) {
                $distanceMeters = $targetLocation->calculateDistanceMeters($lat, $lng);
                $allowedRadius = $targetLocation->radius_meters ?: 200;

                if ($distanceMeters > $allowedRadius) {
                    return $this->formatResponse(
                        $request,
                        false,
                        "Geofence violation: You are {$distanceMeters}m away from {$targetLocation->name}. Punch In is strictly restricted to within {$allowedRadius}m radius."
                    );
                }
                $locationId = $targetLocation->id;
            }
        }

        // 3. Attendance Shift & Grace Period Logic
        $shift = $employee->shift ?: Shift::first();
        $status = 'present';
        $isLate = false;
        $lateMinutes = 0;
        $usedGracePeriod = false;

        if ($shift) {
            // Determine shift start time on today
            $shiftStartTime = Carbon::parse($today->format('Y-m-d') . ' ' . $shift->start_time);
            $graceMinutes = (int) ($shift->grace_period_minutes ?? 15);
            $graceEndTime = (clone $shiftStartTime)->addMinutes($graceMinutes);

            if ($now->gt($shiftStartTime)) {
                $lateMinutes = (int) $shiftStartTime->diffInMinutes($now);
                $isLate = true;

                if ($now->lte($graceEndTime)) {
                    // Employee arrived within grace period!
                    // Check monthly grace days quota (up to 5 days allowed in a month)
                    $graceDaysUsed = Attendance::getGraceDaysUsedInMonth($employee->id, $today);

                    if ($graceDaysUsed < 5) {
                        // Permitted grace period!
                        $usedGracePeriod = true;
                        $status = 'present';
                    } else {
                        // Exceeded 5 grace days this month -> marked as half day
                        $usedGracePeriod = false;
                        $status = 'half_day';
                    }
                } else {
                    // Came after grace period -> marked as half day
                    $status = 'half_day';
                    $usedGracePeriod = false;
                }
            }
        }

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'attendance_date' => $today->format('Y-m-d'),
            ],
            [
                'company_id' => $employee->company_id,
                'shift_id' => $shift?->id,
                'punch_in_at' => $now,
                'punch_in_lat' => $lat,
                'punch_in_lng' => $lng,
                'punch_in_location_id' => $locationId,
                'punch_in_location_type' => $locationType,
                'punch_in_distance_meters' => $distanceMeters,
                'punch_in_address' => $validated['address'] ?? null,
                'status' => $status,
                'is_late' => $isLate,
                'late_minutes' => $lateMinutes,
                'used_grace_period' => $usedGracePeriod,
                'is_field_visit' => $isFieldVisit,
                'field_visit_selfie' => $selfiePath,
                'field_visit_remarks' => $remarks,
                'field_visit_status' => $isFieldVisit ? 'approved' : null,
                'notes' => $isFieldVisit ? "Field Visit: " . ($remarks ?: 'No remarks') : null,
            ]
        );

        // If employee arrived late, trigger InAppNotification to Notification Center
        if ($isLate) {
            \App\Models\InAppNotification::createNotification([
                'company_id' => $employee->company_id,
                'type' => 'late_arrival',
                'title' => "Late Arrival: {$employee->full_name}",
                'message' => "{$employee->full_name} ({$employee->employee_id}) clocked in at {$now->format('h:i A')}, late by {$lateMinutes} minutes." . ($status === 'half_day' ? ' Marked as Half Day.' : ''),
                'link_url' => route('attendance.index', ['date' => $today->format('Y-m-d')]),
                'icon' => 'clock-alert',
                'color' => 'rose',
            ]);
        }

        if ($isFieldVisit) {
            \App\Models\InAppNotification::createNotification([
                'company_id' => $employee->company_id,
                'type' => 'field_visit',
                'title' => "Field Visit: {$employee->full_name}",
                'message' => "{$employee->full_name} ({$employee->employee_id}) marked field visit attendance at {$now->format('h:i A')}. Remarks: {$remarks}",
                'link_url' => route('attendance.index', ['date' => $today->format('Y-m-d')]),
                'icon' => 'map-pin',
                'color' => 'emerald',
            ]);
        }

        $msg = $isFieldVisit
            ? "Field Visit recorded successfully at {$now->format('h:i A')}!"
            : "Punch In recorded successfully at {$now->format('h:i A')}!";

        if ($status === 'half_day') {
            $msg .= " Marked as Half Day (Late arrival after grace period / monthly grace limit reached).";
        } elseif ($usedGracePeriod) {
            $usedCount = Attendance::getGraceDaysUsedInMonth($employee->id, $today);
            $msg .= " (Grace Period used: {$usedCount}/5 this month).";
        }

        return $this->formatResponse($request, true, $msg, ['attendance' => $attendance]);
    }

    /**
     * Mark Attendance Punch Out
     * Allowed multiple times till 12:00 PM (noon) next day/same day
     */
    public function punchOut(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return $this->formatResponse($request, false, 'No employee record associated with your user account.');
        }

        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $lat = !empty($validated['latitude']) ? (float) $validated['latitude'] : null;
        $lng = !empty($validated['longitude']) ? (float) $validated['longitude'] : null;
        $now = Carbon::now();
        $today = Carbon::today();

        // Find attendance record for today (or previous day if night shift/crossing midnight before 12:00 PM)
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (!$attendance && $now->hour < 12) {
            // Check yesterday's shift if punched in yesterday and punching out before 12:00 PM noon
            $yesterday = Carbon::yesterday();
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $yesterday)
                ->first();
        }

        if (!$attendance || !$attendance->punch_in_at) {
            return $this->formatResponse($request, false, 'Cannot Punch Out: No Punch In record found for today.');
        }

        // Check if cutoff time (12:00 PM) has elapsed for previous day
        if ($attendance->attendance_date->lt($today) && $now->hour >= 12) {
            return $this->formatResponse($request, false, 'Punch Out cutoff expired (allowed only till 12:00 PM next day).');
        }

        // Geofence check for fixed location
        $locationType = $employee->location_type ?: 'fixed';
        $locationId = null;
        $distanceMeters = null;

        if ($locationType === 'fixed') {
            if (!$lat || !$lng) {
                return $this->formatResponse($request, false, 'GPS location is required to verify the 200m office geofence. Please allow location access.');
            }

            $targetLocation = $employee->location ?: Location::where('company_id', $employee->company_id)->where('is_active', true)->first();
            if (!$targetLocation) {
                $targetLocation = Location::where('is_active', true)->first();
            }

            if ($targetLocation) {
                $distanceMeters = $targetLocation->calculateDistanceMeters($lat, $lng);
                $allowedRadius = $targetLocation->radius_meters ?: 200;

                if ($distanceMeters > $allowedRadius) {
                    return $this->formatResponse(
                        $request,
                        false,
                        "Geofence violation: You are {$distanceMeters}m away from {$targetLocation->name}. Punch Out is restricted within {$allowedRadius}m."
                    );
                }
                $locationId = $targetLocation->id;
            }
        }

        // Calculate working hours between punch_in and current punch_out
        $minutesWorked = $attendance->punch_in_at->diffInMinutes($now);
        $hoursWorked = round($minutesWorked / 60, 2);

        // Check if employee punched out before shift end time (Early Exit -> Half Day)
        $shift = $attendance->shift ?: ($employee->shift ?: Shift::first());
        $isEarlyExit = false;
        $earlyMinutes = 0;
        $newStatus = $attendance->status;

        if ($shift && $shift->end_time) {
            // Determine shift end timestamp on the attendance date
            $shiftEndTime = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $shift->end_time);

            // If shift is a night shift and end_time is earlier than start_time (crosses midnight)
            if ($shift->is_night_shift || ($shift->start_time && $shift->end_time < $shift->start_time)) {
                $shiftEndTime->addDay();
            }

            if ($now->lt($shiftEndTime)) {
                $isEarlyExit = true;
                $earlyMinutes = (int) $now->diffInMinutes($shiftEndTime);

                // If not already regularized by admin, mark status as half_day
                if (!$attendance->is_regularized) {
                    $newStatus = 'half_day';
                }
            } else {
                $isEarlyExit = false;
                $earlyMinutes = 0;
                // If they punched out at or after shift end, and their punch in was not a half-day due to late arrival, restore present
                if (!$attendance->is_regularized) {
                    // Check if punch_in itself caused half_day (arrived after grace or exceeded monthly grace)
                    $graceDaysUsed = Attendance::getGraceDaysUsedInMonth($employee->id, $attendance->attendance_date);
                    $shiftStartTime = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $shift->start_time);
                    $graceMinutes = (int) ($shift->grace_period_minutes ?? 15);
                    $graceEndTime = (clone $shiftStartTime)->addMinutes($graceMinutes);

                    $punchInLate = $attendance->punch_in_at && $attendance->punch_in_at->gt($graceEndTime);
                    if (!$punchInLate) {
                        $newStatus = 'present';
                    }
                }
            }
        }

        $attendance->update([
            'punch_out_at' => $now,
            'punch_out_lat' => $lat,
            'punch_out_lng' => $lng,
            'punch_out_location_id' => $locationId,
            'punch_out_distance_meters' => $distanceMeters,
            'punch_out_address' => $validated['address'] ?? null,
            'punch_out_count' => $attendance->punch_out_count + 1,
            'working_hours' => $hoursWorked,
            'is_early_exit' => $isEarlyExit,
            'early_minutes' => $earlyMinutes,
            'status' => $newStatus,
        ]);

        if ($isEarlyExit && $earlyMinutes >= 10) {
            \App\Models\InAppNotification::createNotification([
                'company_id' => $attendance->company_id,
                'type' => 'early_exit',
                'title' => "Early Exit: {$employee->full_name}",
                'message' => "{$employee->full_name} clocked out early at {$now->format('h:i A')} ({$earlyMinutes} minutes before shift end time).",
                'link_url' => route('attendance.index', ['date' => $attendance->attendance_date->format('Y-m-d')]),
                'icon' => 'log-out',
                'color' => 'amber',
            ]);
        }

        $msg = "Punch Out updated successfully at {$now->format('h:i A')} (Total: {$hoursWorked} hrs).";
        if ($isEarlyExit && $newStatus === 'half_day') {
            $msg .= " Marked as Half Day (Early departure {$earlyMinutes} mins before shift end time).";
        }

        return $this->formatResponse(
            $request,
            true,
            $msg,
            ['attendance' => $attendance]
        );
    }

    /**
     * Helper to return either JSON for AJAX or redirect with flash message
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

        return redirect()->back()->withErrors(['attendance_error' => $message]);
    }

    /**
     * Regularize attendance record (Superadmin / Admin only)
     */
    public function regularize(Request $request, Attendance $attendance): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (!in_array($user->role, ['superadmin', 'admin'])) {
            return $this->formatResponse($request, false, 'Unauthorized: Only superadmin or admin can regularize attendance records.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:present,half_day,absent,leave,on_duty'],
            'punch_in_time' => ['nullable', 'date_format:H:i'],
            'punch_out_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $dateStr = $attendance->attendance_date->format('Y-m-d');
        $punchInAt = $attendance->punch_in_at;
        $punchOutAt = $attendance->punch_out_at;

        if (!empty($validated['punch_in_time'])) {
            $punchInAt = Carbon::parse("{$dateStr} {$validated['punch_in_time']}");
        }

        if (!empty($validated['punch_out_time'])) {
            $punchOutAt = Carbon::parse("{$dateStr} {$validated['punch_out_time']}");
        }

        // Recalculate working hours if both punch in and out are present
        $workingHours = $attendance->working_hours;
        if ($punchInAt && $punchOutAt) {
            $workingHours = round($punchInAt->diffInMinutes($punchOutAt) / 60, 2);
        }

        $attendance->update([
            'status' => $validated['status'],
            'punch_in_at' => $punchInAt,
            'punch_out_at' => $punchOutAt,
            'working_hours' => $workingHours,
            'is_regularized' => true,
            'regularized_by' => $user->id,
            'regularized_at' => Carbon::now(),
            'regularization_reason' => $validated['reason'],
        ]);

        $employeeName = $attendance->employee?->full_name ?? 'Employee';
        $msg = "Attendance for {$employeeName} on {$attendance->attendance_date->format('d M, Y')} regularized to " . strtoupper(str_replace('_', ' ', $validated['status'])) . " successfully.";

        return $this->formatResponse($request, true, $msg, ['attendance' => $attendance->fresh()]);
    }
}
