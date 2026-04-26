<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_type', 50); // Desktop, Mobile, Tablet
            $table->string('icon', 255)->nullable(); // cihaz ikonu (örn. /uploads/icons/desktop.png)

            $table->unsignedBigInteger('hit_count')->default(0);
            $table->timestamp('first_visited_at')->nullable();
            $table->timestamp('last_visited_at')->nullable();

            $table->timestamps();

            $table->unique('device_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_devices');
    }
};
