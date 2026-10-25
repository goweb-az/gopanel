<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `/ads.txt` məzmunu - reklam satıcılarının siyahısı (AdSense və s.).
 *
 * `robots_txt` / `ai_txt` ilə eyni sətirdə saxlanılır, çünki üçü də panelin
 * «SEO və analitika» səhifəsində redaktə olunan maşın fayllarıdır. Nullable:
 * boş qalanda `/ads.txt` 404 verir (fayl yoxdur sayılır).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seo_analytics') && !Schema::hasColumn('seo_analytics', 'ads_txt')) {
            Schema::table('seo_analytics', function (Blueprint $table) {
                $table->text('ads_txt')->nullable()->after('ai_txt');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seo_analytics') && Schema::hasColumn('seo_analytics', 'ads_txt')) {
            Schema::table('seo_analytics', function (Blueprint $table) {
                $table->dropColumn('ads_txt');
            });
        }
    }
};
