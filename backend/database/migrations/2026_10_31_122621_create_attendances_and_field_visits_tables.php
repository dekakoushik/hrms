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
            $table->date('date'); // Y-m-d

            // Punch In details
            $table->time('punch_in_time');
            $table->decimal('punch_in_latitude', 10, 7)->nullable();
            $table->decimal('punch_in_longitude', 10, 7)->nullable();
            $table->foreignId('punch_in_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('punch_in_distance_meters', 8, 2)->nullable();
            $table->enum('punch_in_status', ['present', 'late', 'half_day'])->default('present');
            $table->text('punch_in_note')->nullable();

            // Punch Out details (optional, only for fixed or when user chooses to punch out)
            $table->time('punch_out_time')->nullable();
            $table->decimal('punch_out_latitude', 10, 7)->nullable();
            $table->decimal('punch_out_longitude', 10, 7)->nullable();
            $table->foreignId('punch_out_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('punch_out_distance_meters', 8, 2)->nullable();
            $table->text('punch_out_note')->nullable();

            // Total working hours
            $table->decimal('total_working_hours', 5, 2)->default(0.00);

            // Attendance status
            $table->enum('status', ['present', 'late', 'half_day', 'absent'])->default('present');
            $table->enum('location_type', ['fixed', 'random'])->default('fixed');

            $table->timestamps();

            // One main attendance record per employee per day
            $table->unique(['employee_id', 'date']);
        });

        Schema::create('field_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->time('visit_time');

            // GPS Location
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('location_name')->nullable(); // e.g. Geocoded place or client site
            $table->text('address')->nullable();

            // Client / Purpose details
            $table->string('client_name')->nullable();
            $table->text('remarks'); // Mandatory remarks describing the visit

            // Verification Photo / Selfie
            $table->longText('selfie_image'); // Base64 or stored URL

            // Status
            $table->enum('status', ['submitted', 'approved', 'rejected'])->default('submitted');
            $table->text('admin_feedback')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_visits');
        Schema::dropIfExists('attendances');
    }
};
