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
        Schema::create('currency', function (Blueprint $table) {
            $table->id(); // id int [pk, increment]
            $table->unsignedBigInteger('country_id'); // country_id int [ref: > countries.id]
            $table->string('code', 3)->nullable(); // code varchar(3) [not null]
            $table->string('name', 50)->nullable(); // name varchar(50) [not null]
            $table->string('symbol', 50)->nullable(); // symbol varchar(50) [not null]
            $table->timestamps(); // created_at ve updated_at için
            $table->softDeletes(); // softdeletes

            // Foreign key relationship
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency');
    }
};
