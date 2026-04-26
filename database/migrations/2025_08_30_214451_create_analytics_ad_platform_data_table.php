<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_ad_platform_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('click_id');
            $table->unsignedBigInteger('platform_id');

            $table->string('param_key', 100)->nullable();   // gclid, fbclid, li_fat_id
            $table->string('param_value', 255)->nullable(); // değer

            $table->timestamps();

            $table->foreign('click_id')->references('id')->on('analytics_clicks')->onDelete('cascade');
            $table->foreign('platform_id')->references('id')->on('analytics_ad_platforms')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_ad_platform_data');
    }
};
