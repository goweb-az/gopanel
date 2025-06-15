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
        Schema::create('countries', function (Blueprint $table) {
            $table->id(); // id int [pk, increment]
            $table->char('code', 5)->notNullable(); // code char(2) [not null]
            $table->string('name', 100)->notNullable(); // name varchar(100) [not null]
            $table->integer('phone')->nullable(); // phone int [not null]
            $table->string('symbol', 10)->nullable(); // symbol varchar(10)
            $table->string('capital', 80)->nullable(); // capital varchar(80)
            $table->char('currency', 5)->nullable(); // currency varchar(3)
            $table->string('continent', 30)->nullable(); // continent varchar(30)
            $table->char('continent_code', 50)->nullable(); // continent_code varchar(2)
            $table->char('alpha_3', 3)->nullable(); // alpha_3 char(3)
            $table->boolean("is_active")->default(true);
            $table->timestamps(); // created_at timestamp
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
