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
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Sayt parametrləri') }}</h4>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="save">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('Status keçidləri') }}</h5>

                            @foreach ([
                                'site_status' => __('Sayt aktiv'),
                                'login_status' => __('Giriş aktiv'),
                                'register_status' => __('Qeydiyyat aktiv'),
                                'payment_status' => __('Ödəniş aktiv'),
                                'site_redirect_status' => __('Yönləndirmələr aktiv'),
                                'site_analytics' => __('Analitika aktiv'),
                                'block_bad_bots' => __('Bot bloklama'),
                            ] as $key => $label)
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" class="form-check-input" id="sw_{{ $key }}" wire:model="form.form.{{ $key }}">
                                    <label class="form-check-label" for="sw_{{ $key }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('Loqolar') }}</h5>

                            <x-gopanel.file-upload
                                name="form.logoLightUpload"
                                :label="__('Sayt loqo (light)')"
                                accept="image/*"
                                :existing="$form->form['logo_light'] ? asset($form->form['logo_light']) : null"
                            />

                            <x-gopanel.file-upload
                                name="form.logoDarkUpload"
                                :label="__('Sayt loqo (dark)')"
                                accept="image/*"
                                :existing="$form->form['logo_dark'] ? asset($form->form['logo_dark']) : null"
                            />

                            <x-gopanel.file-upload
                                name="form.mailLogoUpload"
                                :label="__('Mail loqo')"
                                accept="image/*"
                                :existing="$form->form['mail_logo'] ? asset($form->form['mail_logo']) : null"
                            />

                            <x-gopanel.file-upload
                                name="form.gopanelLogoUpload"
                                :label="__('Gopanel loqo')"
                                accept="image/*"
                                :existing="$form->form['gopanel_logo'] ? asset($form->form['gopanel_logo']) : null"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <span class="lw-not-loading"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                    <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
