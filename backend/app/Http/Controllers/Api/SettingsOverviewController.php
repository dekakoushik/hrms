<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Location;
use App\Models\Setting;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SettingsOverviewController extends Controller
{
    /**
     * Get all organization settings in a single fast, unified query with Redis in-memory caching.
     */
    public function overview(): JsonResponse
    {
        $payload = Cache::remember('settings:overview', 3600, function () {
            $companies = Company::orderByDesc('is_primary')->orderBy('name')->get()->toArray();
            $departments = Department::with('company:id,name')->withCount('designations')->orderBy('name')->get()->toArray();
            $designations = Designation::with('department:id,name,company_id')->orderBy('name')->get()->toArray();
            $shifts = Shift::with('company:id,name')->orderBy('start_time')->get()->toArray();
            $locations = Location::with('company:id,name')->orderBy('name')->get()->toArray();

            $mailSettings = Setting::where('group', 'mail')->pluck('value', 'key');
            $hasPassword = !empty($mailSettings['mail_password']);

            $smtp = [
                'mail_mailer' => $mailSettings['mail_mailer'] ?? 'smtp',
                'mail_host' => $mailSettings['mail_host'] ?? '',
                'mail_port' => $mailSettings['mail_port'] ?? '587',
                'mail_username' => $mailSettings['mail_username'] ?? '',
                'mail_password' => $hasPassword ? '••••••••••••' : '',
                'mail_encryption' => $mailSettings['mail_encryption'] ?? 'tls',
                'mail_from_address' => $mailSettings['mail_from_address'] ?? 'noreply@hrms.local',
                'mail_from_name' => $mailSettings['mail_from_name'] ?? 'HRMS System',
                'is_configured' => !empty($mailSettings['mail_host']) && !empty($mailSettings['mail_username']),
            ];

            return [
                'companies' => $companies,
                'departments' => $departments,
                'designations' => $designations,
                'shifts' => $shifts,
                'locations' => $locations,
                'smtp' => $smtp,
            ];
        });

        return response()->json($payload);
    }
}
