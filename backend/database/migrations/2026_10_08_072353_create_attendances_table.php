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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->date('attendance_date'); // Unique per employee & date
            
            // Punch In details (Allowed only once per day)
            $table->timestamp('punch_in_at')->nullable();
            $table->decimal('punch_in_lat', 10, 8)->nullable();
            $table->decimal('punch_in_lng', 11, 8)->nullable();
            $table->foreignId('punch_in_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('punch_in_location_type')->default('fixed'); // 'fixed' or 'random'
            $table->decimal('punch_in_distance_meters', 8, 2)->nullable();
            $table->string('punch_in_address')->nullable();
            
            // Punch Out details (Can be updated multiple times till 12:00 PM next day/night)
            $table->timestamp('punch_out_at')->nullable();
            $table->decimal('punch_out_lat', 10, 8)->nullable();
            $table->decimal('punch_out_lng', 11, 8)->nullable();
            $table->foreignId('punch_out_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('punch_out_distance_meters', 8, 2)->nullable();
            $table->string('punch_out_address')->nullable();
            $table->integer('punch_out_count')->default(0); // tracks number of punch outs

            // Hours & Status calculations
            $table->decimal('working_hours', 5, 2)->default(0); // Decimal hours (e.g. 8.50)
            $table->string('status')->default('present'); // 'present', 'half_day', 'absent', 'late'
            $table->boolean('is_late')->default(false);
            $table->integer('late_minutes')->default(0);
            $table->boolean('used_grace_period')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
