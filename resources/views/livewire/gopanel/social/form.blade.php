<?php

use App\Enums\Common\SocialIconTypeEnum;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Forms\SocialForm;
use App\Models\Contact\Social;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use AuthorizesGopanel, WithFileUploads;

    public SocialForm $form;

    public bool $modalOpen = false;

    public function mount(): void
    {
        $this->form->setItem(new Social());
    }

    #[On('social-form-open')]
    public function openForm(?int $id = null): void
    {
        $this->resetValidation();

        $social = $id ? Social::findOrFail($id) : new Social();
        $perm = $id ? 'gopanel.contact.socials.edit' : 'gopanel.contact.socials.add';
        $this->authorize($perm);

        $this->form->setItem($social);
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $perm = $this->form->form['id']
            ? 'gopanel.contact.socials.edit'
            : 'gopanel.contact.socials.add';
        $this->authorize($perm);

        $this->form->validate();

        DB::transaction(fn () => $this->form->save());

        $this->modalOpen = false;
        $this->dispatch('notify', type: 'success', message: __('Yadda saxlanıldı'));
        $this->dispatch('social-saved');
    }
}; ?>

<div>
    <x-gopanel.modal
        name="social-form"
        :title="$form->form['id'] ? __('Sosial linkə düzəliş') : __('Yeni sosial link')"
        size="md"
        wireOpen="modalOpen"
    >
        <form wire:submit.prevent="save">
            <div class="mb-3">
                <label class="form-label">{{ __('Adı') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" wire:model="form.form.name">
                @error('form.form.name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('URL') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" wire:model="form.form.url" placeholder="https://...">
                @error('form.form.url') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('İkon növü') }}</label>
                <select class="form-select" wire:model.live="form.form.icon_type">
                    @foreach (SocialIconTypeEnum::cases() as $case)
                        <option value="{{ $case->value }}">{{ ucfirst($case->value) }}</option>
                    @endforeach
                </select>
            </div>

            @if ($form->form['icon_type'] === SocialIconTypeEnum::Image->value)
                <x-gopanel.file-upload
                    name="form.upload"
                    :label="__('İkon faylı')"
                    accept="image/*"
                    :existing="$form->form['icon'] ? asset($form->form['icon']) : null"
                />
            @else
                <div class="mb-3">
                    <label class="form-label">{{ __('İkon dəyəri') }} <small class="text-muted">({{ __('class adı, SVG markup və ya mətn') }})</small></label>
                    <textarea class="form-control" rows="2" wire:model="form.form.icon" placeholder="fa fa-instagram"></textarea>
                    @error('form.form.icon') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            @endif

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('Sıra') }}</label>
                    <input type="number" class="form-control" wire:model="form.form.sort_order" min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('Yeni səkmə') }}</label>
                    <select class="form-select" wire:model="form.form.target_blank">
                        <option value="1">{{ __('Bəli') }}</option>
                        <option value="0">{{ __('Xeyr') }}</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select class="form-select" wire:model="form.form.is_active">
                        <option value="1">{{ __('Aktiv') }}</option>
                        <option value="0">{{ __('Deaktiv') }}</option>
                    </select>
                </div>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" x-on:click="isOpen = false" wire:loading.attr="disabled">
                {{ __('Bağla') }}
            </button>
            <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
            </button>
        </x-slot:footer>
    </x-gopanel.modal>
</div>
