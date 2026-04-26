<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_ad_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Google, Facebook, LinkedIn
            $table->string('slug', 100)->nullable(); // google, fb, li
            $table->string('logo', 255)->nullable(); // platform logosu (örn. /uploads/logos/google.png)

            $table->unsignedBigInteger('hit_count')->default(0);
            $table->timestamp('first_visited_at')->nullable();
            $table->timestamp('last_visited_at')->nullable();

            $table->timestamps();

            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_ad_platforms');
    }
};
