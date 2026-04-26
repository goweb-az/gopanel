<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_operating_systems', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);   // Windows, iOS, Android
            $table->string('icon', 255)->nullable(); // ikon (örn. /uploads/icons/windows.png)

            $table->unsignedBigInteger('hit_count')->default(0);
            $table->timestamp('first_visited_at')->nullable();
            $table->timestamp('last_visited_at')->nullable();

            $table->timestamps();

            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_operating_systems');
    }
};
