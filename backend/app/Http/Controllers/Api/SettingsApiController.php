<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\SmtpSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsApiController extends Controller
{
    /**
     * Get list of shifts
     */
    public function getShifts(): JsonResponse
    {
        $shifts = Shift::orderBy('id', 'asc')->get();
        return response()->json([
            'success' => true,
            'shifts' => $shifts,
        ]);
    }

    /**
     * Create new shift
     */
    public function storeShift(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:shifts,code'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'half_day_hours' => ['required', 'integer', 'min:1', 'max:12'],
            'full_day_hours' => ['required', 'integer', 'min:1', 'max:24'],
            'is_night_shift' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['is_night_shift'] = $request->boolean('is_night_shift');
        $validated['is_active'] = true;

        $shift = Shift::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Shift created successfully',
            'shift' => $shift,
        ], 201);
    }

    /**
     * Update existing shift
     */
    public function updateShift(Request $request, Shift $shift): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'unique:shifts,code,' . $shift->id],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'half_day_hours' => ['required', 'integer', 'min:1', 'max:12'],
            'full_day_hours' => ['required', 'integer', 'min:1', 'max:24'],
            'is_night_shift' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['start_time'] = substr($validated['start_time'], 0, 5);
        $validated['end_time'] = substr($validated['end_time'], 0, 5);
        $validated['is_night_shift'] = $request->boolean('is_night_shift');
        $validated['is_active'] = $request->boolean('is_active');

        $shift->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Shift updated successfully',
            'shift' => $shift->fresh(),
        ]);
    }

    /**
     * Delete shift
     */
    public function deleteShift(Shift $shift): JsonResponse
    {
        $shift->delete();
        return response()->json([
            'success' => true,
            'message' => 'Shift deleted successfully',
        ]);
    }

    /**
     * Get SMTP Configuration (hides raw password)
     */
    public function getSmtp(): JsonResponse
    {
        $smtp = SmtpSetting::first();
        if ($smtp) {
            $smtp->mail_password = $smtp->mail_password ? '••••••••' : null;
        }

        return response()->json([
            'success' => true,
            'smtp' => $smtp,
        ]);
    }

    /**
     * Save / Update SMTP Settings
     */
    public function updateSmtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mail_mailer' => ['required', 'string'],
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer'],
            'mail_username' => ['nullable', 'string'],
            'mail_password' => ['nullable', 'string'],
            'mail_encryption' => ['required', 'string'],
            'mail_from_address' => ['required', 'email'],
            'mail_from_name' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $smtp = SmtpSetting::first();
        if ($smtp) {
            if (empty($validated['mail_password']) || $validated['mail_password'] === '••••••••') {
                unset($validated['mail_password']);
            }
            $smtp->update($validated);
        } else {
            $smtp = SmtpSetting::create($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'SMTP configuration updated successfully',
            'smtp' => $smtp->fresh(),
        ]);
    }
}
