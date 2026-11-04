<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Shift::with('company:id,name');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        return response()->json([
            'shifts' => $query->orderBy('start_time')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'grace_period_minutes' => ['nullable', 'integer', 'min:0'],
            'half_day_minutes' => ['nullable', 'integer', 'min:0'],
            'full_day_minutes' => ['nullable', 'integer', 'min:0'],
            'is_night_shift' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $shift = Shift::create($validated);

        return response()->json([
            'message' => 'Shift created successfully.',
            'shift' => $shift->load('company:id,name'),
        ], 201);
    }

    public function show(Shift $shift): JsonResponse
    {
        return response()->json([
            'shift' => $shift->load('company:id,name'),
        ]);
    }

    public function update(Request $request, Shift $shift): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'start_time' => ['required', 'string'],
            'end_time' => ['required', 'string'],
            'grace_period_minutes' => ['nullable', 'integer', 'min:0'],
            'half_day_minutes' => ['nullable', 'integer', 'min:0'],
            'full_day_minutes' => ['nullable', 'integer', 'min:0'],
            'is_night_shift' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $shift->update($validated);

        return response()->json([
            'message' => 'Shift updated successfully.',
            'shift' => $shift->load('company:id,name'),
        ]);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        $shift->delete();

        return response()->json([
            'message' => 'Shift deleted successfully.',
        ]);
    }
}

