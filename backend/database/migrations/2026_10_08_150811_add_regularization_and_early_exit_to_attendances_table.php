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
            $table->boolean('is_early_exit')->default(false)->after('late_minutes');
            $table->integer('early_minutes')->default(0)->after('is_early_exit');
            $table->boolean('is_regularized')->default(false)->after('used_grace_period');
            $table->foreignId('regularized_by')->nullable()->after('is_regularized')->constrained('users')->nullOnDelete();
            $table->timestamp('regularized_at')->nullable()->after('regularized_by');
            $table->string('regularization_reason')->nullable()->after('regularized_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['regularized_by']);
            $table->dropColumn([
                'is_early_exit',
                'early_minutes',
                'is_regularized',
                'regularized_by',
                'regularized_at',
                'regularization_reason',
            ]);
        });
    }
};
