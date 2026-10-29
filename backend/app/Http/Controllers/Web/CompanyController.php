<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    /**
     * List all companies (Organization Module)
     */
    public function index(): View
    {
        $companies = Company::withCount(['employees', 'users'])->orderBy('name')->get();
        return view('companies.index', compact('companies'));
    }

    /**
     * Store new company
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:companies,name'],
            'code' => ['nullable', 'string', 'max:20', 'unique:companies,code'],
            'legal_type' => ['nullable', 'string', 'max:50'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'cin' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:15'],
        ], [
            'name.unique' => 'A company with this name is already registered.',
            'code.unique' => 'A company with this code already exists.',
        ]);

        $company = Company::create($validated);

        return back()->with('success', "Company '{$company->name}' added successfully!");
    }

    /**
     * AJAX quick store for modal dialogs (e.g. from Employee Onboarding)
     */
    public function quickStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:companies,name'],
            'code' => ['nullable', 'string', 'max:20', 'unique:companies,code'],
            'legal_type' => ['nullable', 'string', 'max:50'],
        ], [
            'name.unique' => 'A company with this name already exists.',
        ]);

        $company = Company::create([
            'name' => trim($validated['name']),
            'code' => $validated['code'] ? strtoupper(trim($validated['code'])) : null,
            'legal_type' => $validated['legal_type'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'company' => $company,
        ]);
    }

    /**
     * Switch active company filter globally for the session
     */
    public function switch(Request $request): RedirectResponse
    {
        $companyId = $request->input('company_id');

        if ($companyId === 'all' || empty($companyId)) {
            $request->session()->forget('active_company_id');
            return back()->with('info', 'Active filter switched to: All Companies');
        }

        $company = Company::findOrFail($companyId);
        $request->session()->put('active_company_id', $company->id);

        return back()->with('info', "Active filter switched to: {$company->name}");
    }
}
