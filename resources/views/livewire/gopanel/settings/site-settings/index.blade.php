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
        <form wire:submit.prevent="save">
            <x-gopanel.tabs :tabs="[
                'general' => ['icon' => 'fas fa-sliders-h', 'label' => __('Sayt funksiyaları')],
                'logos'   => ['icon' => 'far fa-image',     'label' => __('Loqolar')],
                'seo'     => ['icon' => 'fas fa-search',    'label' => __('SEO meta')],
            ]">
                <div class="card">
                    <div class="card-body">
                        <x-gopanel.tab name="general">
                            {{-- Status: 4 ana sahə select dropdown --}}
                            <div class="d-flex align-items-center gap-2 mb-3 text-muted">
                                <i class="bx bx-toggle-left font-size-18"></i>
                                <h6 class="mb-0 fw-semibold">{{ __('Ümumi status tənzimləmələri') }}</h6>
                            </div>

                            <div class="row g-3 mb-4">
                                @foreach ([
                                    'site_status'     => __('Sayt statusu'),
                                    'login_status'    => __('Giriş statusu'),
                                    'register_status' => __('Qeydiyyat statusu'),
                                    'payment_status'  => __('Ödəniş statusu'),
                                ] as $key => $label)
                                    <div class="col-md-6">
                                        <label class="form-label">{{ $label }}</label>
                                        <select class="form-select" wire:model.live="form.form.{{ $key }}" wire:change="saveSwitches">
                                            <option value="1">✅ {{ __('Açıq') }}</option>
                                            <option value="0">⛔ {{ __('Bağlı') }}</option>
                                        </select>
                                    </div>
                                @endforeach
                            </div>

                            {{-- SEO & güvenlik: açıklayıcı card grid --}}
                            <div class="d-flex align-items-center gap-2 mb-3 text-muted">
                                <i class="bx bx-shield-quarter font-size-18"></i>
                                <h6 class="mb-0 fw-semibold">{{ __('SEO və Təhlükəsizlik') }}</h6>
                            </div>

                            <div class="row g-3">
                                @foreach ([
                                    'site_redirect_status' => ['title' => __('Yönləndirmələr'), 'desc' => __('SEO yönləndirmə qaydalarını aktiv/deaktiv edir.'),         'icon' => 'bx bx-link',           'color' => 'primary'],
                                    'site_analytics'       => ['title' => __('Analitika'),      'desc' => __('Saytda ziyarətçi izləmə (analytics) sistemini aktivləşdirir.'), 'icon' => 'bx bx-bar-chart-alt-2','color' => 'warning'],
                                    'block_bad_bots'       => ['title' => __('Bot bloklama'),   'desc' => __('Zərərli botları bloklamaq üçün JS cookie yoxlamasını aktivləşdirir.'), 'icon' => 'bx bx-bot',           'color' => 'danger'],
                                ] as $key => $card)
                                    <div class="col-md-4">
                                        <div class="card border h-100 mb-0">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="d-inline-flex align-items-center justify-content-center rounded"
                                                          style="width: 32px; height: 32px; background-color: rgba(var(--bs-{{ $card['color'] }}-rgb), 0.12);">
                                                        <i class="{{ $card['icon'] }} font-size-18 text-{{ $card['color'] }}"></i>
                                                    </span>
                                                    <h6 class="mb-0 fw-semibold">{{ $card['title'] }}</h6>
                                                </div>
                                                <p class="text-muted small mb-3">{{ $card['desc'] }}</p>
                                                <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                                    <input type="checkbox" class="form-check-input" id="sw_{{ $key }}"
                                                           wire:model.live="form.form.{{ $key }}" wire:change="saveSwitches">
                                                    <label class="form-check-label fw-medium" for="sw_{{ $key }}"
                                                           x-data x-text="document.getElementById('sw_{{ $key }}').checked ? @js(__('Aktiv')) : @js(__('Deaktiv'))">
                                                        {{ $form->form[$key] ? __('Aktiv') : __('Deaktiv') }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
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
