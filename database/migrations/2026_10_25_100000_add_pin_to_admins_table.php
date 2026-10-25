<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin PIN-i - həssas əməliyyatların ikinci təsdiqi (bax config/custom/security.php → admin_pin).
 *
 * Şifrədən ayrı sütundur: PIN giriş vermir, yalnız daxil olmuş adminin addımını
 * təsdiqləyir. Hash saxlanılır (`Hash::make`), açıq dəyər heç yerdə qalmır.
 * `hasColumn` yoxlaması - sütunu əl ilə əlavə etmiş törəmə layihədə miqrasiya sınmasın.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'pin')) {
                $table->string('pin')->nullable()->after('password');
            }

            if (!Schema::hasColumn('admins', 'pin_updated_at')) {
                $table->timestamp('pin_updated_at')->nullable()->after('pin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            foreach (['pin_updated_at', 'pin'] as $column) {
                if (Schema::hasColumn('admins', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
