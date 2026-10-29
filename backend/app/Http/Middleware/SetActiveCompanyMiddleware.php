<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetActiveCompanyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. If explicit query parameter company_id is provided, update or clear session
        if ($request->has('company_id')) {
            $compParam = $request->input('company_id');
            if ($compParam === 'all' || empty($compParam)) {
                $request->session()->forget('active_company_id');
            } else {
                $request->session()->put('active_company_id', (int) $compParam);
            }
        }

        $activeCompanyId = $request->session()->get('active_company_id'); // null means All Companies
        $companies = collect();
        $activeCompany = null;

        if (\Illuminate\Support\Facades\Schema::hasTable('companies')) {
            $companies = Company::where('is_active', true)->orderBy('name')->get();
            $activeCompany = $activeCompanyId ? $companies->firstWhere('id', $activeCompanyId) : null;

            // If session had an ID that doesn't exist anymore, clear it
            if ($activeCompanyId && !$activeCompany) {
                $request->session()->forget('active_company_id');
                $activeCompanyId = null;
            }
        }

        // Share globally with all Blade views
        View::share('globalCompanies', $companies);
        View::share('activeCompanyId', $activeCompanyId);
        View::share('activeCompany', $activeCompany);

        return $next($request);
    }
}
