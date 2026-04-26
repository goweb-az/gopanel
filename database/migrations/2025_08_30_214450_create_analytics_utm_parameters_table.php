<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_utm_parameters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('click_id');

            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_term', 255)->nullable();
            $table->string('utm_content', 255)->nullable();

            $table->timestamps();

            $table->foreign('click_id')->references('id')->on('analytics_clicks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_utm_parameters');
    }
};
