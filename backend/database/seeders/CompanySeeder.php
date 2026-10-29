<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Company::firstOrCreate(
            ['name' => 'Riangdo Venners Pvt. Ltd.'],
            [
                'code' => 'RVPL',
                'legal_type' => 'Private Limited',
                'city' => 'Guwahati',
                'state' => 'Assam',
                'is_active' => true,
            ]
        );

        \App\Models\Company::firstOrCreate(
            ['name' => 'North East Veneers & Plywood LLP'],
            [
                'code' => 'NEVP',
                'legal_type' => 'LLP',
                'city' => 'Guwahati',
                'state' => 'Assam',
                'is_active' => true,
            ]
        );
    }
}
