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
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('designation_id')->constrained('shifts')->nullOnDelete();
            $table->string('location_type')->default('fixed')->after('shift_id'); // 'fixed' or 'random'
            $table->foreignId('location_id')->nullable()->after('location_type')->constrained('locations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropForeign(['location_id']);
            $table->dropColumn(['shift_id', 'location_type', 'location_id']);
        });
    }
};
