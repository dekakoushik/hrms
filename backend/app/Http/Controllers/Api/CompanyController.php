<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /**
     * Display a listing of company entities.
     */
    public function index(): JsonResponse
    {
        $companies = Company::orderByDesc('is_primary')
            ->orderBy('name')
            ->get();

        return response()->json([
            'companies' => $companies,
        ]);
    }

    /**
     * Store a newly created company entity.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:100', 'unique:companies,registration_number'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'currency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:50'],
            'is_primary' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        // If this is set as primary or is the first company, ensure other companies are not primary
        $isFirst = Company::count() === 0;
        if ($isFirst || !empty($validated['is_primary'])) {
            $validated['is_primary'] = true;
            Company::where('is_primary', true)->update(['is_primary' => false]);
        }

        $company = Company::create($validated);

        return response()->json([
            'message' => 'Company entity created successfully.',
            'company' => $company,
        ], 201);
    }

    /**
     * Display the specified company entity.
     */
    public function show(Company $company): JsonResponse
    {
        return response()->json([
            'company' => $company,
        ]);
    }

    /**
     * Update the specified company entity.
     */
    public function update(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('companies', 'registration_number')->ignore($company->id),
            ],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'currency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:50'],
            'is_primary' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        if (!empty($validated['is_primary']) && !$company->is_primary) {
            Company::where('is_primary', true)->update(['is_primary' => false]);
        }

        $company->update($validated);

        return response()->json([
            'message' => 'Company entity updated successfully.',
            'company' => $company,
        ]);
    }

    /**
     * Remove the specified company entity.
     */
    public function destroy(Company $company): JsonResponse
    {
        if ($company->is_primary && Company::count() > 1) {
            return response()->json([
                'message' => 'Cannot delete the primary company entity. Please designate another primary entity first.',
            ], 422);
        }

        $company->delete();

        // If the deleted company was primary, set another one as primary
        $remaining = Company::first();
        if ($remaining) {
            $remaining->update(['is_primary' => true]);
        }

        return response()->json([
            'message' => 'Company entity deleted successfully.',
        ]);
    }
}

