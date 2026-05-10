<?php

use App\Livewire\Forms\AboutUsForm;
use App\Models\Site\AboutUs;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use WithFileUploads;

    public AboutUsForm $form;

    public function mount(): void
    {
        $item = AboutUs::latest()->first() ?? new AboutUs();
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
                    <h4 class="mb-sm-0 font-size-18">{{ __('Haqqımızda') }}</h4>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="save">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('Mətn (dillər üzrə)') }}</h5>

                            <x-gopanel.translatable-fields
                                form="form"
                                :fields="[
                                    ['name' => 'title', 'label' => __('Başlıq'), 'type' => 'text'],
                                    ['name' => 'description', 'label' => __('Təsvir'), 'type' => 'textarea'],
                                ]"
                            />
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ __('Şəkil') }}</h5>
                            <x-gopanel.file-upload
                                name="form.upload"
                                :label="__('Əsas şəkil')"
                                accept="image/*"
                                :existing="$form->form['image'] ? asset($form->form['image']) : null"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                    <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
