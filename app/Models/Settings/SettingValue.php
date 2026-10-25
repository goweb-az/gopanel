<?php

declare(strict_types=1);

namespace App\Models\Settings;

use App\Models\BaseModel;

/**
 * Tipli key-value tənzimləmənin bir sətri (`setting_values`).
 *
 * Tip, default, etiket və validasiya burada YOXDUR - onlar config-dəki
 * tərifdədir (`App\Support\Settings\SettingDefinitions`). Model yalnız xam
 * mətni daşıyır; çevirmə tərif sinfində aparılır ki, dəyər hər yerdə eyni
 * tipdə oxunsun.
 *
 * Oxu/yazı birbaşa yox, `App\Services\Settings\SettingsService` ilə gedir -
 * keş və default birləşdirilməsi orada.
 */
class SettingValue extends BaseModel
{
    protected $table = 'setting_values';

    /** Hər açar dəyişikliyi jurnala düşsə səs-küy olardı - söndürülüb. */
    protected $logEnabled = false;

    protected $fillable = [
        'scope',
        'owner_type',
        'owner_id',
        'key',
        'value',
    ];

    protected $casts = [
        'owner_id' => 'integer',
    ];
}
