<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paneldən çıxarılan backup-ların qeydiyyatı.
 *
 * Arxivin özü `storage/app/backups/` altındadır - bu cədvəl yalnız
 * vəziyyəti (növbədə / işləyir / hazır / xəta), ölçünü və kimin
 * başlatdığını saxlayır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();

            // database | files
            $table->string('type', 20)->index();

            // files üçün: full | incremental (database-də null)
            $table->string('mode', 20)->nullable();

            // pending | running | completed | failed
            $table->string('status', 20)->default('pending')->index();

            $table->string('file_name')->nullable();
            $table->string('path')->nullable();          // storage/app daxilində nisbi yol
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('file_count')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error')->nullable();

            // Kim başladıb - admin silinsə qeyd qalsın
            $table->foreignId('admin_id')->nullable()
                ->constrained('admins')->nullOnDelete();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
