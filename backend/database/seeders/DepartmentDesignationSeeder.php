<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use Illuminate\Database\Seeder;

class DepartmentDesignationSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Human Resources & People Ops', 'code' => 'HR', 'description' => 'Talent acquisition, payroll, compliance, and employee experience.'],
            ['name' => 'Information Technology & Engineering', 'code' => 'ENG', 'description' => 'Software engineering, cloud infrastructure, and technical operations.'],
            ['name' => 'Finance & Statutory Accounts', 'code' => 'FIN', 'description' => 'Corporate accounts, GST, TDS, EPF, and audits.'],
            ['name' => 'Sales & Business Development', 'code' => 'SALES', 'description' => 'Enterprise client acquisition and revenue operations.'],
            ['name' => 'Operations & Facilities', 'code' => 'OPS', 'description' => 'Branch administration, logistics, and workplace security.'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['name' => $dept['name']], $dept);
        }

        $designations = [
            ['name' => 'Chief Technology Officer (CTO)', 'code' => 'CTO', 'description' => 'Executive head of technology.'],
            ['name' => 'Senior HR Business Partner', 'code' => 'SR-HRBP', 'description' => 'People operations lead.'],
            ['name' => 'Full Stack Software Engineer', 'code' => 'SWE-II', 'description' => 'Core application development.'],
            ['name' => 'Finance Controller & Tax Lead', 'code' => 'FIN-MGR', 'description' => 'Statutory reporting and taxation.'],
            ['name' => 'Business Development Manager', 'code' => 'BDM', 'description' => 'Enterprise sales relationship manager.'],
            ['name' => 'Operations Associate', 'code' => 'OPS-ASC', 'description' => 'Branch office coordination.'],
        ];

        foreach ($designations as $desig) {
            Designation::firstOrCreate(['name' => $desig['name']], $desig);
        }
    }
}
