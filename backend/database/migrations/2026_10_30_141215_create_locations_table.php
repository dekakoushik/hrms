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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name'); // e.g. "Main Corporate Headquarters", "Branch Office"
            $table->string('code')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('India');
            $table->decimal('latitude', 10, 8); // e.g. 19.076090
            $table->decimal('longitude', 11, 8); // e.g. 72.877426
            $table->unsignedInteger('radius_meters')->default(100); // Radius in meters for geofence punch
            $table->boolean('enforce_geofence')->default(true); // Whether attendance must be within radius
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
