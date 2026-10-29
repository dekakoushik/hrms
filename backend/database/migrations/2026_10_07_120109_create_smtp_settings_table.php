<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mail_mailer', 50)->default('smtp');
            $table->string('mail_host', 255)->default('smtp.gmail.com');
            $table->integer('mail_port')->default(587);
            $table->string('mail_username', 255)->nullable();
            $table->text('mail_password')->nullable();
            $table->string('mail_encryption', 20)->default('tls');
            $table->string('mail_from_address', 255)->default('no-reply@hrms.in');
            $table->string('mail_from_name', 255)->default('HRMS Enterprise');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smtp_settings');
    }
};
