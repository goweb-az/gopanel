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
        Schema::create('languages', function (Blueprint $table) {
            $table->id(); // id int [pk, increment]
            $table->unsignedBigInteger('country_id'); // country_id int [ref: > countries.id]
            $table->string('code', 10)->notNullable(); // code varchar(10) [not null]
            $table->string('name', 100)->notNullable(); // name varchar(100) [not null]
            $table->boolean('is_active')->default(true); // is_active bool [default: true]
            $table->boolean('is_show')->default(true); // is_active bool [default: true]
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
        Schema::dropIfExists('languages');
    }
};
