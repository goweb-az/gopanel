<?php

use App\Enums\Gopanel\Seo\RedirectMatchTypeEnum;
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
        Schema::create('site_redirects', function (Blueprint $table) {
            $table->id();

            // Hedeflenecek dil. null ise tüm diller için geçerli.
            $table->string('locale', 8)->nullable()->index(); // az, en, ru, vb.

            // Kaynak tanımı ve eşleşme türü
            // source: tam link ya da desen (match_type'a göre yorumlanır)
            $table->string('source', 2048)->index();
            $table->enum('match_type', RedirectMatchTypeEnum::values())->default(RedirectMatchTypeEnum::EXACT->value)->index();
            $table->string('regex_flags', 8)->nullable(); // i, m, u vb. (regex için)

            // Yönlenecek hedef (tam URL önerilir)
            $table->string('target', 2048)->nullable();

            // HTTP durum kodu (301 kalıcı, 302 geçici, 307/308 destekli)
            $table->unsignedSmallInteger('http_code')->default(301);

            // Etkinlik, öncelik ve zaman penceresi
            $table->boolean('is_active')->default(true);
            $table->smallInteger('priority')->default(0)->index(); // büyük olan önce çalışır
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();

            // İzleme
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();

            $table->string('notes', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Sık kullanılan kombinasyon için karma indeks
            $table->index(['is_active', 'locale', 'match_type', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_redirects');
    }
};
