<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Location::with('company:id,name');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        return response()->json([
            'locations' => $query->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:50000'],
            'enforce_geofence' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $location = Location::create($validated);

        return response()->json([
            'message' => 'Location created successfully.',
            'location' => $location->load('company:id,name'),
        ], 201);
    }

    public function show(Location $location): JsonResponse
    {
        return response()->json([
            'location' => $location->load('company:id,name'),
        ]);
    }

    public function update(Request $request, Location $location): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:50000'],
            'enforce_geofence' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $location->update($validated);

        return response()->json([
            'message' => 'Location updated successfully.',
            'location' => $location->load('company:id,name'),
        ]);
    }

    public function destroy(Location $location): JsonResponse
    {
        $location->delete();

        return response()->json([
            'message' => 'Location deleted successfully.',
        ]);
    }
}

