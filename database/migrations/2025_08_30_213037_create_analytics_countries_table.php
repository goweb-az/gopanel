<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);       // Ülke adı
            $table->string('iso_code', 10)->nullable(); // TR, US, AZ
            $table->string('flag', 255)->nullable();    // Bayrak görseli (/uploads/flags/tr.png)

            $table->unsignedBigInteger('hit_count')->default(0);
            $table->timestamp('first_visited_at')->nullable();
            $table->timestamp('last_visited_at')->nullable();

            $table->timestamps();

            $table->unique('iso_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_countries');
    }
};
