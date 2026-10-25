<?php

namespace Tests\Feature\Gopanel\Settings\Fixtures;

use App\Support\Settings\SettingDefinitions;

/** Qlobal (owner-siz) təriflər dəsti - törəmə layihənin edəcəyi kimi. */
final class ShopSettingDefinitions extends SettingDefinitions
{
    protected static function configKey(): string
    {
        return 'testing.shop_settings';
    }
}
