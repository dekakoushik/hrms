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
        // 1. Leave Types / Global Policy (Casual Leave, Sick Leave, Earned/Privilege Leave, Maternity, etc.)
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Casual Leave", "Sick / Medical Leave", "Earned / Privilege Leave"
            $table->string('code')->unique(); // "CL", "SL", "EL"
            $table->decimal('default_annual_quota', 5, 1)->default(12.0); // Global leave balance per year
            $table->boolean('is_paid')->default(true);
            $table->boolean('carry_forward')->default(false);
            $table->decimal('max_consecutive_days', 4, 1)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Individual Employee Leave Balances (Initialized from Global Quota, per year)
        Schema::create('employee_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->integer('year')->default(2026);
            $table->decimal('total_allocated', 5, 1)->default(12.0);
            $table->decimal('used_days', 5, 1)->default(0.0);
            $table->decimal('pending_days', 5, 1)->default(0.0);
            $table->decimal('remaining_days', 5, 1)->default(12.0);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        // 3. Leave Requests (Application, Approvals, Status)
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_count', 4, 1)->default(1.0);
            $table->boolean('is_half_day')->default(false);
            $table->string('half_day_type')->nullable(); // 'first_half', 'second_half'
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('actioned_at')->nullable();
            $table->text('action_remarks')->nullable();
            $table->timestamps();
        });

        // 4. In-App Notification Center (Real-time tracking of late arrivals, leave applications, approvals, regularizations)
        Schema::create('in_app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete(); // null = broadcast to all superadmins/admins
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('type'); // 'late_arrival', 'leave_applied', 'leave_actioned', 'early_exit', 'regularization'
            $table->string('title');
            $table->text('message');
            $table->string('link_url')->nullable();
            $table->string('icon')->default('bell');
            $table->string('color')->default('brand'); // 'rose', 'emerald', 'amber', 'indigo'
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('in_app_notifications');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('employee_leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
