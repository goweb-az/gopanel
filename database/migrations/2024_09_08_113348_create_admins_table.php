<?php

use App\Models\Gopanel\Admin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean("is_super")->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Admin::create([
            'full_name' => "Super Admin",
            'email'     => "admin@gmail.com",
            'password'  => Hash::make('12345'),
            'is_super'  => 1,
        ]);

        Admin::create([
            'full_name' => "Test Admin",
            'email'     => "test@gmail.com",
            'password'  => Hash::make('12345'),
            'is_super'  => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
