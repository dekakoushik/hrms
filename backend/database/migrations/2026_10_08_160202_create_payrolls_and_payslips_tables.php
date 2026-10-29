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
        // 1. Payroll Run Batches (First of month to last of month)
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->integer('year');
            $table->integer('month'); // 1 - 12
            $table->string('cycle_period'); // e.g. "01 Oct 2026 - 31 Oct 2026"
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_gross', 12, 2)->default(0.00);
            $table->decimal('total_deductions', 12, 2)->default(0.00);
            $table->decimal('total_net_payout', 12, 2)->default(0.00);
            $table->integer('employee_count')->default(0);
            $table->enum('status', ['draft', 'processed', 'approved', 'paid'])->default('draft');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'year', 'month']);
        });

        // 2. Individual Employee Payslips
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('payslip_number')->unique(); // e.g. "PS-202610-001"
            $table->integer('year');
            $table->integer('month');
            $table->date('start_date');
            $table->date('end_date');

            // Working Days & Leave/Attendance Calculations
            $table->integer('month_calendar_days')->default(30);
            $table->decimal('full_day_leaves', 4, 1)->default(0.0);
            $table->decimal('half_day_leaves', 4, 1)->default(0.0);
            $table->decimal('total_deductible_days', 4, 1)->default(0.0); // full_day_leaves + (half_day_leaves * 0.5)
            $table->decimal('days_worked', 4, 1)->default(30.0);

            // Earnings breakdown (INR)
            $table->decimal('basic_salary', 10, 2)->default(0.00);
            $table->decimal('da_amount', 10, 2)->default(0.00);
            $table->decimal('hra_amount', 10, 2)->default(0.00);
            $table->decimal('ca_amount', 10, 2)->default(0.00);
            $table->decimal('ea_amount', 10, 2)->default(0.00);
            $table->decimal('sa_amount', 10, 2)->default(0.00);
            $table->decimal('gross_salary', 10, 2)->default(0.00);

            // Statutory deductions (PF, ESIC, Tax)
            $table->decimal('pf_deduction', 10, 2)->default(0.00);
            $table->decimal('esic_deduction', 10, 2)->default(0.00);
            $table->decimal('tax_deduction', 10, 2)->default(0.00);
            $table->decimal('statutory_deductions', 10, 2)->default(0.00);

            // Baseline Take-home before leave deduction
            $table->decimal('baseline_take_home', 10, 2)->default(0.00);

            // Exact Leave deduction rule: (takehome / 30) * total_deductible_days
            $table->decimal('daily_take_home_rate', 10, 2)->default(0.00); // takehome / 30
            $table->decimal('leave_deduction_amount', 10, 2)->default(0.00);

            // Final Net Salary Pay
            $table->decimal('net_salary', 10, 2)->default(0.00); // baseline_take_home - leave_deduction_amount

            // Payment Details
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->enum('status', ['draft', 'generated', 'paid'])->default('generated');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
    }
};
