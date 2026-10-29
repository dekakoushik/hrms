<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\SmtpSetting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Standard Indian corporate & industrial shifts
        $shifts = [
            [
                'name' => 'General Day Shift (General Corporate)',
                'code' => 'GEN-DAY',
                'start_time' => '09:30:00',
                'end_time' => '18:30:00',
                'grace_period_minutes' => 15,
                'half_day_hours' => 4,
                'full_day_hours' => 8,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Standard day shift for head office and tech operations with 1 hr lunch interval.',
            ],
            [
                'name' => 'Morning Shift (Early Operations)',
                'code' => 'MORN-01',
                'start_time' => '07:00:00',
                'end_time' => '15:30:00',
                'grace_period_minutes' => 10,
                'half_day_hours' => 4,
                'full_day_hours' => 8,
                'is_night_shift' => false,
                'is_active' => true,
                'description' => 'Early morning rotation for customer service and facilities.',
            ],
            [
                'name' => 'Evening / Night Shift (US/UK Sync)',
                'code' => 'NIGHT-US',
                'start_time' => '18:30:00',
                'end_time' => '03:30:00',
                'grace_period_minutes' => 15,
                'half_day_hours' => 4,
                'full_day_hours' => 8,
                'is_night_shift' => true,
                'is_active' => true,
                'description' => 'Overnight global support shift with night shift allowance compliance.',
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::updateOrCreate(['code' => $shift['code']], $shift);
        }

        // Default SMTP Configuration
        SmtpSetting::firstOrCreate(
            ['id' => 1],
            [
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => 587,
                'mail_username' => 'notifications@hrms.in',
                'mail_password' => 'hrms_app_password_2026',
                'mail_encryption' => 'tls',
                'mail_from_address' => 'no-reply@hrms.in',
                'mail_from_name' => 'Enterprise HRMS India',
                'is_active' => true,
            ]
        );
    }
}
