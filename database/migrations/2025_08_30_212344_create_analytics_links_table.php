<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_links', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5); // az, en, ru
            $table->text('url');
            $table->string('slug', 255)->nullable();

            $table->unsignedBigInteger('hit_count')->default(0); // toplam ziyaret sayısı
            $table->timestamp('first_visited_at')->nullable();   // ilk ziyaret
            $table->timestamp('last_visited_at')->nullable();    // son ziyaret

            $table->timestamps();

            $table->unique(['locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_links');
    }
};
