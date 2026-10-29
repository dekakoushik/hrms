<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_id')->unique(); // e.g. EMP-0001
            $table->string('user_type')->default('user'); // admin or user
            
            // Org & Reporting
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            
            // Section 1: Personal Details
            $table->string('full_name');
            $table->date('dob')->nullable();
            $table->string('gender', 20)->nullable(); // Male, Female, Other
            $table->string('marital_status', 30)->nullable(); // Single, Married, Divorced, Widowed
            $table->string('blood_group', 10)->nullable(); // A+, B+, O+, AB+, etc.
            $table->string('father_or_husband_name')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('contact_number', 20);
            $table->string('alternate_number', 20)->nullable();
            
            // Present Address
            $table->string('present_street')->nullable();
            $table->string('present_city')->nullable();
            $table->string('present_state')->nullable();
            $table->string('present_pin', 15)->nullable();
            
            // Permanent Address
            $table->boolean('permanent_same_as_present')->default(false);
            $table->string('permanent_street')->nullable();
            $table->string('permanent_city')->nullable();
            $table->string('permanent_state')->nullable();
            $table->string('permanent_pin', 15)->nullable();
            
            // Section 2: Primary Emergency Contact
            $table->string('primary_contact_name')->nullable();
            $table->string('primary_contact_relationship', 50)->nullable();
            $table->string('primary_contact_phone', 20)->nullable();
            $table->text('primary_contact_address')->nullable();
            
            // Section 3: Statutory & Identity Details
            $table->string('pan_number', 20)->nullable();
            $table->string('pan_document')->nullable();
            $table->string('aadhar_number', 25)->nullable();
            $table->string('aadhar_document')->nullable();
            $table->string('uan_number', 30)->nullable();
            $table->string('esic_number', 30)->nullable();
            
            // Section 4: Bank Account Details
            $table->string('account_holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code', 20)->nullable();
            $table->string('account_type', 30)->default('Savings'); // Savings / Current
            $table->string('bank_proof_document')->nullable();
            
            // Section 5: Educational Qualifications (JSON array)
            $table->json('education_details')->nullable();
            
            // Section 6: Salary Breakdown & Calculation
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('da_amount', 12, 2)->default(0); // 20% of Basic
            $table->decimal('hra_amount', 12, 2)->default(0); // 60% of Basic
            $table->decimal('ca_amount', 12, 2)->default(0); // 20% of Basic
            $table->decimal('ea_amount', 12, 2)->default(0); // 8% of Basic
            $table->decimal('sa_amount', 12, 2)->default(0); // 20% of Basic
            $table->decimal('gross_salary', 12, 2)->default(0); // Sum of allowances
            
            $table->decimal('gratuity_amount', 12, 2)->default(0); // (Basic+DA)*4.81%
            $table->boolean('pf_enabled')->default(true);
            $table->decimal('pf_amount', 12, 2)->default(0); // (Basic+DA)*12% max 3000
            
            $table->boolean('esic_enabled')->default(false);
            $table->decimal('esic_employer_amount', 12, 2)->default(0); // Gross*3.25% if Gross <= 21000
            $table->decimal('esic_employee_amount', 12, 2)->default(0); // Gross*0.75%
            
            $table->decimal('ctc_amount', 12, 2)->default(0); // Gross + Gratuity + PF + Employer ESIC
            $table->decimal('total_deductions', 12, 2)->default(0); // PF + Employee ESIC
            $table->decimal('tax_amount', 12, 2)->default(0); // TDS / Income Tax
            $table->decimal('take_home_salary', 12, 2)->default(0); // Gross - Total Deductions - Tax
            
            // Status & Joining Date
            $table->date('joining_date')->nullable();
            $table->string('status', 30)->default('active'); // active, probation, terminated, resigned
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
