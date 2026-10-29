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
            $table->string('employment_type', 50)->default('Probation')->after('user_type'); // Probation, Permanent/Full-time, Contract, Trainee, Intern
            $table->integer('probation_months')->default(6)->after('joining_date');
            $table->date('probation_end_date')->nullable()->after('probation_months');
            $table->date('confirmation_date')->nullable()->after('probation_end_date');
            $table->date('exit_date')->nullable()->after('status');
            $table->string('exit_reason')->nullable()->after('exit_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'employment_type',
                'probation_months',
                'probation_end_date',
                'confirmation_date',
                'exit_date',
                'exit_reason',
            ]);
        });
    }
};
