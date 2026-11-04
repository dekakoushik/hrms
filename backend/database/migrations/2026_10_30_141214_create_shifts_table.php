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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name'); // e.g. "General Morning Shift", "Night Shift"
            $table->string('code')->nullable();
            $table->time('start_time'); // e.g. 09:00:00
            $table->time('end_time'); // e.g. 18:00:00
            $table->unsignedInteger('grace_period_minutes')->default(15); // e.g. 15 mins late allowance
            $table->unsignedInteger('half_day_minutes')->default(240); // 4 hours min for half-day
            $table->unsignedInteger('full_day_minutes')->default(480); // 8 hours min for full-day
            $table->boolean('is_night_shift')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
