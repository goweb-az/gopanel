<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Admin;

use App\Support\Settings\AdminSettingDefinitions;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Adminin şəxsi tənzimləmələri - Profil → «Şəxsi tənzimləmələr».
 *
 * Qaydalar ƏL İLƏ yazılmır - `config/gopanel/admin_settings.php` tərifindən
 * qurulur. Beləliklə yeni açar əlavə edəndə forma da, validasiya da özü
 * yenilənir. Forma `settings[key]` massivi göndərir (bool üçün hidden 0 + switch 1).
 *
 * İcazə: hər admin YALNIZ öz tənzimləməsini dəyişir (route-da `{item}` yoxdur),
 * ona görə ayrıca permission tələb olunmur - profil kimi.
 */
class AdminSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('gopanel')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['settings' => ['nullable', 'array']] + AdminSettingDefinitions::validationRules('settings');
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return AdminSettingDefinitions::attributeLabels('settings');
    }

    /**
     * Native tipə çevrilmiş dəyərlər - servis Request görmür.
     *
     * @return array<string, mixed>
     */
    public function settingValues(): array
    {
        return AdminSettingDefinitions::fromForm((array) ($this->validated()['settings'] ?? []));
    }
}
