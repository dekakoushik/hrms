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
        // 1. Monthly Payroll Processing Batch
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->integer('year');
            $table->integer('month'); // 1 - 12
            $table->date('period_start'); // Day 1 of month (e.g. 2026-11-01)
            $table->date('period_end');   // Last day of month (e.g. 2026-11-30)
            $table->integer('calendar_days')->default(30);
            $table->integer('total_sundays')->default(4);
            $table->integer('total_holidays')->default(0);
            $table->integer('total_employees_processed')->default(0);
            $table->decimal('total_gross_payout', 14, 2)->default(0.00);
            $table->decimal('total_deductions', 14, 2)->default(0.00);
            $table->decimal('total_net_payout', 14, 2)->default(0.00);
            $table->enum('status', ['draft', 'processed', 'approved', 'paid'])->default('draft');
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'year', 'month'], 'payroll_company_year_month_unique');
        });

        // 2. Individual Employee Monthly Payslip
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('payslip_number')->unique();
            $table->integer('year');
            $table->integer('month');
            $table->date('period_start');
            $table->date('period_end');

            // Attendance & Day Accounting Breakdown
            $table->integer('total_month_days')->default(30);
            $table->integer('sundays_count')->default(4); // Counted as paid day off
            $table->integer('holidays_count')->default(0); // Counted as paid holiday
            $table->decimal('present_days', 5, 2)->default(0.00);
            $table->decimal('paid_leave_days', 5, 2)->default(0.00);
            $table->decimal('unpaid_leave_days', 5, 2)->default(0.00);
            $table->decimal('half_days', 5, 2)->default(0.00);
            $table->integer('late_days_count')->default(0);
            $table->decimal('late_penalty_half_days', 5, 2)->default(0.00); // 0.5 day deduction for each consecutive late after 5 days
            $table->decimal('absent_days', 5, 2)->default(0.00);
            $table->decimal('total_payable_days', 5, 2)->default(0.00); // present + sundays + holidays + paid_leaves + (half_days * 0.5) - late_penalty

            // Base Master Earnings
            $table->decimal('basic_salary', 12, 2)->default(0.00);
            $table->decimal('da_amount', 12, 2)->default(0.00);
            $table->decimal('hra_amount', 12, 2)->default(0.00);
            $table->decimal('ca_amount', 12, 2)->default(0.00);
            $table->decimal('ea_amount', 12, 2)->default(0.00);
            $table->decimal('sa_amount', 12, 2)->default(0.00);
            $table->decimal('gross_salary', 12, 2)->default(0.00);

            // Prorated Earned Amounts for the Month
            $table->decimal('earned_basic', 12, 2)->default(0.00);
            $table->decimal('earned_gross', 12, 2)->default(0.00);

            // Deductions
            $table->decimal('employee_pf', 12, 2)->default(0.00);
            $table->decimal('employee_esic', 12, 2)->default(0.00);
            $table->decimal('monthly_tax', 12, 2)->default(0.00);
            $table->decimal('other_deductions', 12, 2)->default(0.00);
            $table->decimal('total_deductions', 12, 2)->default(0.00);

            // Net Payout
            $table->decimal('net_salary', 12, 2)->default(0.00);
            $table->enum('status', ['draft', 'generated', 'published', 'paid'])->default('generated');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'year', 'month'], 'employee_payslip_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payrolls');
    }
};
