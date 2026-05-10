<?php

namespace App\Livewire\Forms;

use App\Helpers\Gopanel\FileUploader;
use App\Models\Settings\SiteSetting;
use Illuminate\Support\Facades\Cache;

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
            'logoLightUpload'           => 'nullable|image|max:2048',
            'logoDarkUpload'            => 'nullable|image|max:2048',
            'mailLogoUpload'            => 'nullable|image|max:2048',
            'gopanelLogoUpload'         => 'nullable|image|max:2048',
        ];
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
    }

    public function save(): SiteSetting
    {
        $item = SiteSetting::findOrNew($this->form['id']);

        if ($this->logoLightUpload) {
            $this->form['logo_light'] = FileUploader::toPublic($this->logoLightUpload, 'site-logo', 'logo-light');
        }
        if ($this->logoDarkUpload) {
            $this->form['logo_dark'] = FileUploader::toPublic($this->logoDarkUpload, 'site-logo', 'logo-dark');
        }
        if ($this->mailLogoUpload) {
            $this->form['mail_logo'] = FileUploader::toPublic($this->mailLogoUpload, 'site-logo', 'mail-logo');
        }
        if ($this->gopanelLogoUpload) {
            $this->form['gopanel_logo'] = FileUploader::toPublic($this->gopanelLogoUpload, 'site-logo', 'gopanel-logo');
        }

        $item->fill(collect($this->form)->except('id')->all());
        $item->save();

        $this->form['id'] = $item->id;
        $this->logoLightUpload = null;
        $this->logoDarkUpload = null;
        $this->mailLogoUpload = null;
        $this->gopanelLogoUpload = null;

        Cache::forget('site_settings' . app()->getLocale());

        return $item;
    }
}
