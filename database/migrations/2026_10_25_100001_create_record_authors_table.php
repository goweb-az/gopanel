<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sətrin müəllifi və məsulu - bütün modellər üçün vahid morph cədvəli.
 *
 * Niyə hər cədvələ `created_by` sütunu əlavə edilmir: eyni işi onlarla
 * cədvəldə təkrarlamaq olardı. Burada bir dəfə yazılır, yeni bölməyə qoşulmaq
 * `HasAuthors` trait-inin bir sətridir (opt-in).
 *
 * Niyə `activity_log`-dan oxunmur: log silinə bilən izahatdır (təmizlənir),
 * müəllif isə sətrin daimi atributudur - siyahıda süzülür, sıralanır, Excel-ə düşür.
 *
 * Cədvəl TARİXÇƏ saxlayır: sətir silinmir və üzərinə yazılmır. Məsul dəyişəndə
 * köhnə qeyd `is_current`-i itirib `ended_at` alır və yerində qalır.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('record_authors')) {
            return;
        }

        Schema::create('record_authors', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uid')->unique();

            // Hansı sətir: authorable_type + authorable_id (indeksli)
            $table->morphs('authorable');

            // App\Enums\System\AuthorRoleEnum: creator | assignee
            $table->string('role', 30);

            // Hər ikisi nullable: idxalda admin yoxdur, sayt formasını müştəri
            // özü göndərir - biri məcburi olsa hər iki hal sınar.
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // App\Enums\System\AuthorSourceEnum
            $table->string('source', 30)->default('panel');

            // Ad snapshot-ı: hesab tam silinsə tarixçədə «-» qalmasın.
            $table->string('author_name')->nullable();

            // 1 = hazırkı qeyd, NULL = keçmiş. HEÇ VAXT 0 YAZILMIR.
            //
            // Unikal indeks NULL-ları saymır (MySQL və SQLite), ona görə aşağıdakı
            // indeks «bir sətir + bir rol üçün yalnız bir aktiv qeyd» qaydasını DB
            // səviyyəsində qoruyur: iki admin eyni anda «götür» basanda ikincisi
            // rədd olunur. `0` yazılsa indeks onu real dəyər sayar və ikinci
            // KEÇMİŞ qeyd «Duplicate entry» xətası verər.
            $table->unsignedTinyInteger('is_current')->nullable();

            $table->timestamp('assigned_at')->nullable();   // rol qüvvəyə mindi
            $table->timestamp('ended_at')->nullable();      // rol bitdi
            // App\Enums\System\AuthorEndReasonEnum: released | reassigned | expired
            $table->string('end_reason', 30)->nullable();

            $table->timestamps();

            $table->unique(
                ['authorable_type', 'authorable_id', 'role', 'is_current'],
                'record_authors_current_unique',
            );

            // «Mənim üzərimdə» süzgəci. Tarixçə oxunuşu yuxarıdakı unikal
            // indeksin prefiksi ilə örtülür - üçüncü indeks yazılmır.
            $table->index(['admin_id', 'role', 'is_current'], 'record_authors_admin_role_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_authors');
    }
};
