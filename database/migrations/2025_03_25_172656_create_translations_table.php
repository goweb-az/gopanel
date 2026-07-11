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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            // Column widths are kept small on purpose so the composite unique
            // index below stays within MySQL's 3072-byte key limit (utf8mb4).
            $table->string('locale', 10);
            $table->string('key', 191);
            $table->text('value')->nullable();
            $table->char('platform', 15)->default('website');
            $table->string('page', 100)->default('general');
            $table->string('filename', 100)->nullable();
            $table->string('group', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['key', 'locale', 'platform', 'page', 'group', 'filename'],
                'translations_unique'
            );
            $table->index(['platform', 'page', 'locale'], 'translations_platform_page_locale_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
