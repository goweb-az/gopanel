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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('site_status')->default(true);
            $table->boolean('login_status')->default(true);
            $table->boolean('register_status')->default(true);
            $table->boolean('payment_status')->default(true);
            $table->string('logo_light')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('mail_logo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
