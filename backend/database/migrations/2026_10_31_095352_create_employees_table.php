<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Link to login User account
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Organization & Job Assignments
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            // Employee Identification & System Role
            $table->string('employee_id')->unique(); // e.g. EMP-2026-0001
            $table->enum('usertype', ['user', 'admin'])->default('user'); // portal role
            $table->date('date_of_joining');
            $table->string('employment_type')->default('Full-Time'); // Full-Time, Part-Time, Contract, Intern
            $table->string('probation_duration')->default('3 Months'); // No Probation, 1 Month, 3 Months, 6 Months

            // Attendance Configuration
            $table->enum('attendance_location_type', ['fixed', 'random'])->default('fixed');

            // Personal Information
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Widowed'])->nullable();
            $table->string('blood_group', 10)->nullable();
            $table->text('current_address')->nullable();
            $table->text('permanent_address')->nullable();

            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            // Statutory & Identity Details (PAN, Aadhaar/SSN, UAN, ESIC)
            $table->string('pan_number')->nullable();
            $table->string('aadhaar_number')->nullable();
            $table->string('uan_number')->nullable();
            $table->string('esic_number')->nullable();
            $table->string('passport_number')->nullable();

            // Bank Account Details
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc_code')->nullable();
            $table->string('bank_branch')->nullable();

            // Educational Qualifications (stored as JSON array)
            $table->json('qualifications')->nullable();

            // Salary & Compensation Structure (exact fields as per reference spec)
            $table->decimal('basic_salary', 12, 2)->default(0.00);
            $table->decimal('monthly_tax', 12, 2)->default(0.00); // Manual entry
            $table->boolean('enable_epf')->default(true); // 12% deduction (capped max 3000)

            // Computed / stored snapshot values for payroll auditability
            $table->decimal('da_amount', 12, 2)->default(0.00); // 20%
            $table->decimal('hra_amount', 12, 2)->default(0.00); // 60%
            $table->decimal('ca_amount', 12, 2)->default(0.00); // 20%
            $table->decimal('ea_amount', 12, 2)->default(0.00); // 8%
            $table->decimal('sa_amount', 12, 2)->default(0.00); // 20%
            $table->decimal('gross_salary', 12, 2)->default(0.00);
            $table->decimal('gratuity_amount', 12, 2)->default(0.00); // (Basic+DA)*4.81%
            $table->decimal('employee_pf', 12, 2)->default(0.00);
            $table->decimal('employee_esic', 12, 2)->default(0.00); // 0.75% if gross <= 21000
            $table->decimal('employer_esic', 12, 2)->default(0.00); // 3.25% if gross <= 21000
            $table->decimal('total_deductions', 12, 2)->default(0.00);
            $table->decimal('ctc_amount', 12, 2)->default(0.00);
            $table->decimal('net_salary', 12, 2)->default(0.00);

            // Status
            $table->enum('status', ['active', 'probation', 'notice', 'terminated', 'resigned'])->default('active');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
