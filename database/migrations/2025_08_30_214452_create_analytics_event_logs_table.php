<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_event_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('click_id');

            $table->string('event_type', 100);   // scroll, click, time_on_page
            $table->string('event_value', 255)->nullable(); // %50 scroll, btn_buy

            $table->timestamps();

            $table->foreign('click_id')->references('id')->on('analytics_clicks')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_event_logs');
    }
};
