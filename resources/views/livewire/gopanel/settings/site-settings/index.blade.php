<?php

use App\Livewire\Forms\SiteSettingForm;
use App\Models\Settings\SiteSetting;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use WithFileUploads;

    public SiteSettingForm $form;

    public function mount(): void
    {
        $item = SiteSetting::latest()->first() ?? new SiteSetting();
        $this->form->setItem($item);
    }

    public function save(): void
    {
        $this->form->validate();

        DB::transaction(fn () => $this->form->save());

        $this->dispatch('notify', type: 'success', message: __('Yadda saxlanıldı'));
    }

    public function saveSwitches(): void
    {
        DB::transaction(fn () => $this->form->save());

        $this->dispatch('notify', type: 'success', message: __('Yadda saxlanıldı'));
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <x-gopanel.page-header :title="__('Sayt parametrləri')" :showCreateButton="false" />

        <form wire:submit.prevent="save">
            <x-gopanel.tabs :tabs="[
                'general' => ['icon' => 'fas fa-sliders-h', 'label' => __('Sayt funksiyaları')],
                'logos'   => ['icon' => 'far fa-image',     'label' => __('Loqolar')],
                'seo'     => ['icon' => 'fas fa-search',    'label' => __('SEO meta')],
            ]">
                <div class="card">
                    <div class="card-body">
                        <x-gopanel.tab name="general">
                            @foreach ([
                                'site_status' => __('Sayt aktiv'),
                                'login_status' => __('Giriş aktiv'),
                                'register_status' => __('Qeydiyyat aktiv'),
                                'payment_status' => __('Ödəniş aktiv'),
                                'site_redirect_status' => __('Yönləndirmələr aktiv'),
                                'site_analytics' => __('Analitika aktiv'),
                                'block_bad_bots' => __('Bot bloklama'),
                            ] as $key => $label)
                                <div class="form-checkd d-flex align-items-center form-switch mb-3 mt-0">
                                    <input type="checkbox" class="form-check-input" id="sw_{{ $key }}" wire:model.live="form.form.{{ $key }}" wire:change="saveSwitches">
                                    <label class="form-check-label" for="sw_{{ $key }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </x-gopanel.tab>

                        <x-gopanel.tab name="logos">
                            <div class="row">
                                <div class="col-md-6">
                                    <x-gopanel.file-upload
                                        name="form.logoLightUpload"
                                        :label="__('Sayt loqo (light)')"
                                        accept="image/*"
                                        :existing="$form->form['logo_light'] ? asset($form->form['logo_light']) : null"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <x-gopanel.file-upload
                                        name="form.logoDarkUpload"
                                        :label="__('Sayt loqo (dark)')"
                                        accept="image/*"
                                        :existing="$form->form['logo_dark'] ? asset($form->form['logo_dark']) : null"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <x-gopanel.file-upload
                                        name="form.mailLogoUpload"
                                        :label="__('Mail loqo')"
                                        accept="image/*"
                                        :existing="$form->form['mail_logo'] ? asset($form->form['mail_logo']) : null"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <x-gopanel.file-upload
                                        name="form.gopanelLogoUpload"
                                        :label="__('Gopanel loqo')"
                                        accept="image/*"
                                        :existing="$form->form['gopanel_logo'] ? asset($form->form['gopanel_logo']) : null"
                                    />
                                </div>

                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <span class="lw-not-loading"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                                        <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
                                    </button>
                                </div>
                            </div>
                        </x-gopanel.tab>

                        <x-gopanel.tab name="seo">
                            <x-gopanel.meta-fields form="form" />

                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <span class="lw-not-loading"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                                    <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
                                </button>
                            </div>
                        </x-gopanel.tab>
                    </div>
                </div>
            </x-gopanel.tabs>

        </form>
    </div>
</div>
