<?php

declare(strict_types=1);

namespace App\Support\Settings;

/**
 * Hər adminin ŞƏXSİ tənzimləmələri - `config/gopanel/admin_settings.php`.
 *
 * Niyə ayrıca dəst: bildiriş seçimi, siyahı ölçüsü kimi seçimlər adminə
 * bağlıdır, sayta yox (`site_settings` bütün sayt üçün bir sətirdir).
 * Cast / validasiya / forma `SettingDefinitions`-dən gəlir - burada yalnız
 * config açarı var. Panel ekranı: Profil → «Şəxsi tənzimləmələr».
 */
final class AdminSettingDefinitions extends SettingDefinitions
{
    protected static function configKey(): string
    {
        return 'gopanel.admin_settings';
    }

    /**
     * Sabit scope - config faylı köçürülsə də köhnə sətirlər itməsin.
     */
    public static function scope(): string
    {
        return 'admin';
    }
}
