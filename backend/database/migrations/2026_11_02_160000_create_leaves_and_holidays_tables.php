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
        // 1. Leave Types (e.g. Paid Leave, Casual Leave, Sick Leave, Maternity Leave)
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('default_days_per_year', 5, 1)->default(12.0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Employee Leave Balances (Per employee, per leave type, per year)
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->integer('year');
            $table->decimal('allocated_days', 5, 1)->default(0.0);
            $table->decimal('used_days', 5, 1)->default(0.0);
            $table->decimal('pending_days', 5, 1)->default(0.0);
            $table->decimal('remaining_days', 5, 1)->default(0.0);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year'], 'emp_leave_year_unique');
        });

        // 3. Leave Applications / Requests
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 1)->default(1.0);
            $table->boolean('is_half_day')->default(false);
            $table->string('half_day_type')->nullable(); // 'first_half' or 'second_half'
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('action_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_remarks')->nullable();
            $table->timestamp('action_at')->nullable();
            $table->timestamps();
        });

        // 4. Official Holidays Calendar
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('title');
            $table->date('date');
            $table->string('day_name')->nullable();
            $table->enum('type', ['gazetted', 'restricted', 'optional', 'company'])->default('gazetted');
            $table->text('description')->nullable();
            $table->integer('year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_applications');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};

