<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    /**
     * List holidays by year
     */
    public function index(Request $request): JsonResponse
    {
        $year = (int) $request->get('year', now()->year);

        $holidays = Holiday::where('year', $year)
            ->where('is_active', true)
            ->orderBy('date')
            ->get();

        return response()->json([
            'year' => $year,
            'holidays' => $holidays,
        ]);
    }

    /**
     * Create or update a holiday
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'date' => 'required|date',
            'type' => 'required|in:gazetted,restricted,optional,company',
            'description' => 'nullable|string|max:500',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $date = Carbon::parse($validated['date']);
        $validated['year'] = $date->year;
        $validated['day_name'] = $date->format('l');

        $holiday = Holiday::updateOrCreate(
            ['id' => $request->id],
            $validated
        );

        return response()->json([
            'message' => 'Holiday record saved successfully.',
            'data' => $holiday,
        ]);
    }

    /**
     * Delete a holiday
     */
    public function destroy($id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return response()->json(['message' => 'Holiday removed successfully.']);
    }
}

