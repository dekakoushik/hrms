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
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('is_field_visit')->default(false)->after('used_grace_period');
            $table->string('field_visit_selfie')->nullable()->after('is_field_visit');
            $table->text('field_visit_remarks')->nullable()->after('field_visit_selfie');
            $table->string('field_visit_status')->default('approved')->after('field_visit_remarks'); // 'approved', 'pending'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'is_field_visit',
                'field_visit_selfie',
                'field_visit_remarks',
                'field_visit_status',
            ]);
        });
    }
};
