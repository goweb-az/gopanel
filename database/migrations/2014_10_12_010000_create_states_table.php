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
        Schema::create('states', function (Blueprint $table) {
            $table->id(); // id int [pk, increment]
            $table->unsignedBigInteger('country_id'); // country_id int [ref: > countries.id]
            $table->string('state_name', 255)->notNullable(); // state_name varchar(255) [not null]
            $table->string('state_code', 100)->notNullable(); // state_code varchar(100) [not null]
            $table->timestamps(); // created_at ve updated_at için
            $table->softDeletes();
            // Foreign key relationship
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
