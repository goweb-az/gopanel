<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_clicks', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('link_id');
            $table->unsignedBigInteger('device_id')->nullable();
            $table->unsignedBigInteger('os_id')->nullable();
            $table->unsignedBigInteger('browser_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('isp', 255)->nullable();
            $table->string("url")->nullable();
            $table->text('referer')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('link_id')->references('id')->on('analytics_links')->onDelete('cascade');
            $table->foreign('device_id')->references('id')->on('analytics_devices')->onDelete('set null');
            $table->foreign('os_id')->references('id')->on('analytics_operating_systems')->onDelete('set null');
            $table->foreign('browser_id')->references('id')->on('analytics_browsers')->onDelete('set null');
            $table->foreign('country_id')->references('id')->on('analytics_countries')->onDelete('set null');
            $table->foreign('city_id')->references('id')->on('analytics_cities')->onDelete('set null');
            $table->foreign('language_id')->references('id')->on('analytics_languages')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_clicks');
    }
};
