<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\SiteSetting\SaveSiteSettingFormAction;
use App\Models\Settings\SiteSetting;

class SiteSettingForm extends BaseForm
{
    public array $form = [
        'id'                   => null,
        'site_status'          => true,
        'login_status'         => true,
        'register_status'      => true,
        'payment_status'       => false,
        'site_redirect_status' => false,
        'site_analytics'       => false,
        'block_bad_bots'       => false,
        'logo_light'           => '',
        'logo_dark'            => '',
        'mail_logo'            => '',
        'gopanel_logo'         => '',
    ];

    public mixed $logoLightUpload = null;
    public mixed $logoDarkUpload = null;
    public mixed $mailLogoUpload = null;
    public mixed $gopanelLogoUpload = null;

    protected function rules(): array
    {
        return [
            'form.site_status'          => 'boolean',
            'form.login_status'         => 'boolean',
            'form.register_status'      => 'boolean',
            'form.payment_status'       => 'boolean',
            'form.site_redirect_status' => 'boolean',
            'form.site_analytics'       => 'boolean',
            'form.block_bad_bots'       => 'boolean',
            'logoLightUpload'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'logoDarkUpload'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'mailLogoUpload'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'gopanelLogoUpload'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ] + $this->metaRules();
    }

    public function setItem(SiteSetting $item): void
    {
        $this->form = [
            'id'                   => $item->id,
            'site_status'          => (bool) ($item->site_status ?? true),
            'login_status'         => (bool) ($item->login_status ?? true),
            'register_status'      => (bool) ($item->register_status ?? true),
            'payment_status'       => (bool) ($item->payment_status ?? false),
            'site_redirect_status' => (bool) ($item->site_redirect_status ?? false),
            'site_analytics'       => (bool) ($item->site_analytics ?? false),
            'block_bad_bots'       => (bool) ($item->block_bad_bots ?? false),
            'logo_light'           => $item->logo_light ?? '',
            'logo_dark'            => $item->logo_dark ?? '',
            'mail_logo'            => $item->mail_logo ?? '',
            'gopanel_logo'         => $item->gopanel_logo ?? '',
        ];

        $this->prepareMeta($item);
    }

    public function save(): SiteSetting
    {
        $item = SaveSiteSettingFormAction::run(
            form: $this->form,
            logoLightUpload: $this->logoLightUpload,
            logoDarkUpload: $this->logoDarkUpload,
            mailLogoUpload: $this->mailLogoUpload,
            gopanelLogoUpload: $this->gopanelLogoUpload,
            meta: $this->meta,
            metaUploads: $this->metaUploads,
        );

        $this->form['id']        = $item->id;
        $this->logoLightUpload   = null;
        $this->logoDarkUpload    = null;
        $this->mailLogoUpload    = null;
        $this->gopanelLogoUpload = null;
        foreach ($this->metaUploads as $code => $_) {
            $this->metaUploads[$code] = null;
        }

        return $item;
    }
}
