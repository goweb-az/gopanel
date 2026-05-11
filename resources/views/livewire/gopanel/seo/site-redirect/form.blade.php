<?php

use App\Enums\Gopanel\Seo\RedirectMatchTypeEnum;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Forms\SiteRedirectForm as RecordForm;
use App\Models\Geography\Language;
use App\Models\Seo\SiteRedirect as RecordModel;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    use AuthorizesGopanel;

    public RecordForm $form;

    public bool $modalOpen = false;

    public string $permissionCreate = 'gopanel.seo.site-redirects.add';
    public string $permissionEdit   = 'gopanel.seo.site-redirects.edit';
    public string $eventSaved       = 'site-redirect-saved';

    public function mount(): void
    {
        $this->form->setItem(new RecordModel());
    }

    #[Computed]
    public function languages()
    {
        return Language::getCachedAll();
    }

    #[On('site-redirect-form-open')]
    public function openForm(?int $id = null): void
    {
        $this->resetValidation();

        $record = $id ? RecordModel::findOrFail($id) : new RecordModel();
        $this->authorize($id ? $this->permissionEdit : $this->permissionCreate);

        $this->form->setItem($record);
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->authorize($this->form->form['id'] ? $this->permissionEdit : $this->permissionCreate);

        $this->form->validate();

        DB::transaction(fn () => $this->form->save());

        $this->modalOpen = false;
        $this->dispatch('notify', type: 'success', message: __('Yadda saxlanıldı'));
        $this->dispatch($this->eventSaved);
    }
}; ?>

<div>
    <x-gopanel.modal
        name="site-redirect-form"
        :title="$form->form['id'] ? __('Yönləndirməyə düzəliş') : __('Yeni yönləndirmə')"
        size="lg"
        wireOpen="modalOpen"
    >
        <form wire:submit.prevent="save">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('Dil') }}</label>
                    <select class="form-select" wire:model="form.form.locale">
                        <option value="">{{ __('Hamısı') }}</option>
                        @foreach ($this->languages as $lang)
                            <option value="{{ $lang->code }}">{{ $lang->upper_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('Tip') }} <span class="text-danger">*</span></label>
                    <select class="form-select" wire:model.live="form.form.match_type">
                        @foreach (RedirectMatchTypeEnum::cases() as $case)
                            <option value="{{ $case->value }}">{{ ucfirst($case->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('Status kodu') }}</label>
                    <select class="form-select" wire:model="form.form.http_code">
                        @foreach ([301, 302, 303, 307, 308] as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">{{ __('Prioritet') }}</label>
                    <input type="number" min="0" class="form-control" wire:model="form.form.priority">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('Mənbə (source)') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" wire:model="form.form.source" placeholder="{{ $form->form['match_type'] === 'regex' ? '^/old/(.*)$' : '/old-page' }}">
                <div class="form-text small">
                    @switch($form->form['match_type'])
                        @case('exact')
                            {{ __('Tam uyğun: sadəcə bu URL yönləndiriləcək.') }}
                            <br><code>/old-page</code>
                            @break
                        @case('prefix')
                            {{ __('Başlanğıc uyğun: bu prefiks ilə başlayan bütün URL-lər yönləndiriləcək.') }}
                            <br><code>/blog</code>
                            @break
                        @case('contains')
                            {{ __('İçərisində: URL bu mətni ehtiva edirsə yönləndiriləcək.') }}
                            <br><code>category-id=5</code>
                            @break
                        @case('regex')
                            {{ __('Regex: PCRE pattern. Capture qruplarına hədəfdə $1, $2 ilə müraciət edin.') }}
                            <br><code>^/old/(.*)$</code>
                            @break
                    @endswitch
                </div>
                @error('form.form.source') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            @if ($form->form['match_type'] === 'regex')
                <div class="mb-3">
                    <label class="form-label">{{ __('Regex flags') }}</label>
                    <input type="text" class="form-control" wire:model="form.form.regex_flags" placeholder="i">
                    <div class="form-text small">
                        {{ __('Məsələn: i (case-insensitive), u (utf-8), s (dotall).') }}
                    </div>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label">{{ __('Hədəf (target)') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" wire:model="form.form.target" placeholder="/new-page or https://...">
                <div class="form-text small">
                    {{ __('Daxili yol (/new-page) və ya tam URL (https://...) ola bilər.') }}
                    @if ($form->form['match_type'] === 'regex')
                        <br>{{ __('Regex capture qrupları üçün $1, $2 istifadə edin: /new/$1') }}
                    @endif
                </div>
                @error('form.form.target') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('Başlama tarixi') }}</label>
                    <input type="datetime-local" class="form-control" wire:model="form.form.starts_at">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('Bitmə tarixi') }}</label>
                    <input type="datetime-local" class="form-control" wire:model="form.form.ends_at">
                    @error('form.form.ends_at') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select class="form-select" wire:model="form.form.is_active">
                        <option value="1">{{ __('Aktiv') }}</option>
                        <option value="0">{{ __('Deaktiv') }}</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('Qeydlər') }}</label>
                <textarea class="form-control" rows="2" wire:model="form.form.notes"></textarea>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" x-on:click="isOpen = false">
                {{ __('Bağla') }}
            </button>
            <button type="button" class="btn btn-primary" wire:click="save">
                <span class="lw-not-loading"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
            </button>
        </x-slot:footer>
    </x-gopanel.modal>
</div>
