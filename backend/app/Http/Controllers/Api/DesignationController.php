<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Designation::with('department:id,name,company_id');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        return response()->json([
            'designations' => $query->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $designation = Designation::create($validated);

        return response()->json([
            'message' => 'Designation created successfully.',
            'designation' => $designation->load('department:id,name'),
        ], 201);
    }

    public function show(Designation $designation): JsonResponse
    {
        return response()->json([
            'designation' => $designation->load('department:id,name'),
        ]);
    }

    public function update(Request $request, Designation $designation): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $designation->update($validated);

        return response()->json([
            'message' => 'Designation updated successfully.',
            'designation' => $designation->load('department:id,name'),
        ]);
    }

    public function destroy(Designation $designation): JsonResponse
    {
        $designation->delete();

        return response()->json([
            'message' => 'Designation deleted successfully.',
        ]);
    }
}

