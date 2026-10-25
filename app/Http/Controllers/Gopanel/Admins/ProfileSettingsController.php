<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gopanel\Admins;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Admin\AdminSettingsRequest;
use App\Services\Settings\AdminSettingsService;
use App\Support\Settings\AdminSettingDefinitions;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Profil → «Şəxsi tənzimləmələr» - cari adminin key-value tənzimləmələri.
 *
 * Forma `config/gopanel/admin_settings.php`-dən qurulur; controller yalnız
 * orkestrasiya edir (oxu → view, request → servis).
 */
class ProfileSettingsController extends GoPanelController
{
    public function __construct(private readonly AdminSettingsService $settings)
    {
        parent::__construct();
    }

    public function index(): View
    {
        $admin = auth('gopanel')->user();

        return view('gopanel.pages.profile.settings', [
            'groups' => AdminSettingDefinitions::groupedFormRows($this->settings->all($admin)),
        ]);
    }

    public function update(AdminSettingsRequest $request): JsonResponse
    {
        try {
            $values = $this->settings->saveMany(auth('gopanel')->user(), $request->settingValues());

            $this->success_response($values, 'Tənzimləmələr yadda saxlanıldı!');
        } catch (\Throwable $e) {
            report($e);
            $this->response['message'] = 'Tənzimləmələri yadda saxlamaq alınmadı.';
        }

        return $this->response_json();
    }
}
