<?php

use App\Livewire\Forms\ContactInfoForm;
use App\Models\Contact\ContactInfo;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    public ContactInfoForm $form;

    public function mount(): void
    {
        $item = ContactInfo::latest()->first() ?? new ContactInfo();
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
        <x-gopanel.page-header :title="__('Əlaqə məlumatları')" :showCreateButton="false" />

        <form wire:submit.prevent="save">
            <div class="row">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('Əlaqə kanalları') }}</h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('Telefon') }}</label>
                                    <input type="text" class="form-control" wire:model="form.form.phone">
                                    @error('form.form.phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('Mobil') }}</label>
                                    <input type="text" class="form-control" wire:model="form.form.mobile">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('WhatsApp') }}</label>
                                    <input type="text" class="form-control" wire:model="form.form.whatsapp">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('WhatsApp (dəstək)') }}</label>
                                    <input type="text" class="form-control" wire:model="form.form.support_whatsapp">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('WhatsApp (satış)') }}</label>
                                    <input type="text" class="form-control" wire:model="form.form.sales_whatsapp">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('İnfo e-poçt') }}</label>
                                    <input type="email" class="form-control" wire:model="form.form.info_email">
                                    @error('form.form.info_email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ __('Dəstək e-poçt') }}</label>
                                    <input type="email" class="form-control" wire:model="form.form.support_email">
                                    @error('form.form.support_email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Xəritə (Google Maps embed URL)') }}</label>
                                <textarea class="form-control" rows="3" wire:model="form.form.map"></textarea>
                                @error('form.form.map') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('Səhifə tərcümələri') }}</h5>
                            <x-gopanel.translatable-fields
                                form="form"
                                :fields="[
                                    ['name' => 'page_title', 'label' => __('Səhifə başlığı'), 'type' => 'text'],
                                    ['name' => 'page_description', 'label' => __('Səhifə təsviri'), 'type' => 'textarea'],
                                    ['name' => 'adress', 'label' => __('Ünvan'), 'type' => 'textarea'],
                                ]"
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
