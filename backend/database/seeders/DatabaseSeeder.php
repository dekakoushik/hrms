<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@hrms.in'],
            [
                'name' => 'Rajesh Sharma',
                'mobile_number' => '9876543210',
                'password' => Hash::make('Password@123'),
                'role' => 'superadmin',
                'designation' => 'Chief Human Resources Officer & Super Admin',
                'department' => 'Executive Leadership',
                'bio' => 'Enterprise System Administrator managing nationwide India workforce operations.',
                'address' => 'DLF Cyber City, Tower B, 14th Floor',
                'city' => 'Gurugram',
                'state' => 'Haryana',
                'pincode' => '122002',
                'is_active' => true,
            ]
        );

        // Seed Standard Indian Leave Policies
        $leavePolicies = [
            [
                'name' => 'Casual Leave (CL)',
                'code' => 'CL',
                'default_annual_quota' => 12.0,
                'is_paid' => true,
                'carry_forward' => false,
                'description' => 'Casual leaves for personal commitments and short unplanned breaks.',
            ],
            [
                'name' => 'Sick / Medical Leave (SL)',
                'code' => 'SL',
                'default_annual_quota' => 12.0,
                'is_paid' => true,
                'carry_forward' => true,
                'description' => 'Medical leaves for health recovery and doctor appointments.',
            ],
            [
                'name' => 'Earned / Privilege Leave (EL)',
                'code' => 'EL',
                'default_annual_quota' => 18.0,
                'is_paid' => true,
                'carry_forward' => true,
                'description' => 'Earned / Privilege leaves for planned vacations and longer holidays.',
            ],
        ];

        foreach ($leavePolicies as $lp) {
            \App\Models\LeaveType::updateOrCreate(['code' => $lp['code']], $lp);
        }
    }
}
