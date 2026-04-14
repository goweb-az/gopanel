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
        Schema::create('seo_analytics', function (Blueprint $table) {
            $table->id();
            $table->text('head')->nullable();
            $table->text('body')->nullable();
            $table->text('footer')->nullable();
            $table->text('robots_txt')->nullable();
            $table->text('ai_txt')->nullable();
            $table->text('other')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_analytics');
    }
};
