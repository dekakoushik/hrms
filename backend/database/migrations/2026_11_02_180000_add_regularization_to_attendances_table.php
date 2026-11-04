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
            $table->boolean('is_regularized')->default(false)->after('status');
            $table->string('regularization_reason')->nullable()->after('is_regularized'); // 'late_forgiven', 'mispunch_corrected', 'on_duty_travel', 'system_error'
            $table->text('regularization_remarks')->nullable()->after('regularization_reason');
            $table->foreignId('regularized_by_user_id')->nullable()->after('regularization_remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('regularized_at')->nullable()->after('regularized_by_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['regularized_by_user_id']);
            $table->dropColumn([
                'is_regularized',
                'regularization_reason',
                'regularization_remarks',
                'regularized_by_user_id',
                'regularized_at',
            ]);
        });
    }
};

