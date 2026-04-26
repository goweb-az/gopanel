<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20);   // tr-TR, en-US
            $table->string('name', 100);  // Türkçe, English
            $table->string('flag', 255)->nullable(); // Bayrak görseli (örn. /uploads/flags/tr.png)

            $table->unsignedBigInteger('hit_count')->default(0);
            $table->timestamp('first_visited_at')->nullable();
            $table->timestamp('last_visited_at')->nullable();

            $table->timestamps();

            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_languages');
    }
};
