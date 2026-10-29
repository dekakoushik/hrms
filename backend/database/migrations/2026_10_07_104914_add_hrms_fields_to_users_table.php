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
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile_number', 20)->nullable()->unique()->after('email');
            $table->string('role', 30)->default('employee')->after('password');
            $table->string('designation', 100)->nullable()->after('role');
            $table->string('department', 100)->nullable()->after('designation');
            $table->string('avatar')->nullable()->after('department');
            $table->text('bio')->nullable()->after('avatar');
            $table->string('address')->nullable()->after('bio');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('pincode', 20)->nullable()->after('state');
            $table->boolean('is_active')->default(true)->after('pincode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mobile_number',
                'role',
                'designation',
                'department',
                'avatar',
                'bio',
                'address',
                'city',
                'state',
                'pincode',
                'is_active',
            ]);
        });
    }
};
