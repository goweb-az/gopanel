<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Settings;

use App\DTOs\Gopanel\ContentPayload;
use App\DTOs\Gopanel\FileField;
use App\Http\Requests\Gopanel\GopanelFormRequest;

class SiteSettingsSaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.settings.site-settings';

    protected array $fileInputs = ['logo_light', 'logo_dark', 'mail_logo', 'gopanel_logo'];

    /** Tək sətirli səhifə - «əlavə» yoxdur. */
    protected function ability(): string
    {
        return $this->module . '.edit';
    }

    public function rules(): array
    {
        return [
            'site_status'          => ['nullable', 'in:0,1'],
            'login_status'         => ['nullable', 'in:0,1'],
            'register_status'      => ['nullable', 'in:0,1'],
            'payment_status'       => ['nullable', 'in:0,1'],
            'site_redirect_status' => ['nullable', 'in:0,1'],
            'site_analytics'       => ['nullable', 'in:0,1'],
            'block_bad_bots'       => ['nullable', 'in:0,1'],
            'logo_light'           => $this->logoRules(),
            'logo_dark'            => $this->logoRules(),
            'mail_logo'            => $this->logoRules(),
            'gopanel_logo'         => $this->logoRules(),
        ];
    }

    /**
     * Checkbox-lar işarələnməyəndə sorğuda ÜMUMİYYƏTLƏ olmur.
     *
     * `payload()` yalnız gələn açarları ötürsəydi, deaktiv edilmiş açar heç
     * vaxt 0 kimi yazılmazdı və düymə söndürülə bilməzdi. Ona görə üç bayraq
     * burada açıq şəkildə 0/1-ə çevrilir - eyni məntiq əvvəllər controller-in
     * içində idi.
     */
    public function payload(): ContentPayload
    {
        $payload = parent::payload();

        foreach (['site_redirect_status', 'site_analytics', 'block_bad_bots'] as $flag) {
            $payload = $payload->withAttribute($flag, $this->boolean($flag) ? 1 : 0);
        }

        return $payload;
    }

    /**
     * Loqolar `public/site/site-logo/` altında SABİT adla saxlanılır
     * (`logo-light.png` və s.) - şablon onlara birbaşa müraciət edir və
     * hər yükləmədə ad dəyişsəydi keşlənmiş səhifələr sınıq şəkil göstərərdi.
     */
    public function fileFields(): array
    {
        return [
            new FileField('logo_light', 'logo_light', folder: 'site-logo', fileName: 'logo-light'),
            new FileField('logo_dark', 'logo_dark', folder: 'site-logo', fileName: 'logo-dark'),
            new FileField('mail_logo', 'mail_logo', folder: 'site-logo', fileName: 'mail-logo'),
            new FileField('gopanel_logo', 'gopanel_logo', folder: 'site-logo', fileName: 'gopanel_logo'),
        ];
    }

    /** Loqoda SVG-yə icazə verilir - vektor loqo panel üçün normaldır. */
    private function logoRules(): array
    {
        return ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'];
    }
}
