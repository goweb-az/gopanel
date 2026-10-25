<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipli key-value tənzimləmələrin dəyərləri (`App\Services\Settings\SettingsService`).
 *
 * Niyə key-value, sütun yox: `site_settings` kimi sütunlu cədvəldə hər yeni
 * tənzimləmə miqrasiya tələb edir. Burada tərif config-dədir
 * (`App\Support\Settings\SettingDefinitions`), cədvəldə yalnız xam `value`
 * saxlanılır; sətir yoxdursa config-dəki `default` qüvvədədir - yeni açar üçün
 * miqrasiya da, backfill də lazım deyil.
 *
 * `scope` - təriflər dəsti (məs. `gopanel.admin_settings`); `owner_*` - kimin
 * tənzimləməsi (admin, istifadəçi). Qlobal tənzimləmədə owner boşdur.
 *
 * Niyə owner sütunları NULL yox, '' / 0: unikal indeks NULL-ları saymır və
 * qlobal tənzimləmədə eyni açarın iki sətri yarana bilərdi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('setting_values')) {
            return;
        }

        Schema::create('setting_values', function (Blueprint $table): void {
            $table->id();
            $table->string('scope', 100);
            $table->string('owner_type', 150)->default('');
            $table->unsignedBigInteger('owner_id')->default(0);
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->timestamps();

            // Oxu həmişə (scope, owner) üzrə gedir - indeksin prefiksi onu örtür.
            $table->unique(['scope', 'owner_type', 'owner_id', 'key'], 'setting_values_owner_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_values');
    }
};
