<?php

use App\Livewire\Concerns\AuthorizesGopanel;
use App\Models\Geography\Language;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use AuthorizesGopanel;

    #[On('language-saved')]
    public function refresh(): void
    {
        unset($this->languages);
    }

    #[Computed]
    public function languages(): Collection
    {
        return Language::orderByDesc('default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with('country:id,name')
            ->get();
    }

    public function delete(int $id): void
    {
        $this->authorize('gopanel.settings.languages.delete');
        Language::findOrFail($id)->delete();
        Language::ensureFallbackDefault();
        unset($this->languages);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('gopanel.settings.languages.edit');
        $language = Language::findOrFail($id);

        if ($language->default && $language->is_active) {
            $this->dispatch('notify', type: 'warning', message: __('Default dili deaktiv etmək olmaz'));
            return;
        }

        $language->is_active = ! $language->is_active;
        $language->save();
        unset($this->languages);
    }

    public function toggleDefault(int $id): void
    {
        $this->authorize('gopanel.settings.languages.edit');
        $language = Language::findOrFail($id);

        DB::transaction(function () use ($language) {
            if ($language->default) {
                $language->forceFill(['default' => false])->save();
                Language::ensureFallbackDefault();
            } else {
                Language::query()->update(['default' => false]);
                $language->forceFill(['default' => true, 'is_active' => true])->save();
            }
        });

        unset($this->languages);
        $this->dispatch('notify', type: 'success', message: __('Default dil yeniləndi'));
    }

    public function reorder(array $ids): void
    {
        $this->authorize('gopanel.settings.languages.edit');
        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $id) {
                Language::where('id', $id)->update(['sort_order' => $order]);
            }
        });
        // Do not invalidate $this->languages — DOM is already in the correct order
        // from SortableJS. A re-render here fights the morph algorithm and can
        // wipe sibling regions (e.g. page-title-box) due to wire:ignore.self.
        $this->skipRender();
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Dillər') }}</h4>
                    <div class="page-title-right">
                        @can('gopanel.settings.languages.add')
                            <button type="button" class="btn btn-success" x-on:click="$dispatch('language-form-open')">
                                <i class="fas fa-plus"></i> {{ __('Əlavə et') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:20px;"></th>
                                        <th style="width:50px;">#</th>
                                        <th>{{ __('Adı') }}</th>
                                        <th style="width:80px;">{{ __('Kod') }}</th>
                                        <th>{{ __('Ölkə') }}</th>
                                        <th style="width:90px;" class="text-center">{{ __('Default') }}</th>
                                        <th style="width:90px;" class="text-center">{{ __('Status') }}</th>
                                        <th style="width:120px;" class="text-center">{{ __('Əməliyyat') }}</th>
                                    </tr>
                                </thead>
                                <x-gopanel.sortable wireMethod="reorder" handle=".drag-handle" tag="tbody">
                                    @foreach ($this->languages as $language)
                                        <tr data-id="{{ $language->id }}" wire:key="lang-{{ $language->id }}">
                                            <td class="drag-handle" style="cursor:grab;text-align:center;vertical-align:middle;">
                                                <i class="fas fa-grip-vertical text-muted"></i>
                                            </td>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><strong>{{ $language->name }}</strong></td>
                                            <td><code>{{ $language->upper_code }}</code></td>
                                            <td>{{ $language?->country?->name ?? '—' }}</td>
                                            <td class="text-center">
                                                @can('gopanel.settings.languages.edit')
                                                    <button
                                                        type="button"
                                                        wire:click="toggleDefault({{ $language->id }})"
                                                        class="btn btn-sm {{ $language->default ? 'btn-primary' : 'btn-outline-secondary' }}"
                                                    >
                                                        {{ $language->default ? __('Bəli') : __('Xeyr') }}
                                                    </button>
                                                @else
                                                    <span class="badge {{ $language->default ? 'bg-primary' : 'bg-secondary' }}">
                                                        {{ $language->default ? __('Bəli') : __('Xeyr') }}
                                                    </span>
                                                @endcan
                                            </td>
                                            <td class="text-center">
                                                @can('gopanel.settings.languages.edit')
                                                    <button
                                                        type="button"
                                                        wire:click="toggleActive({{ $language->id }})"
                                                        class="btn btn-sm {{ $language->is_active ? 'btn-success' : 'btn-secondary' }}"
                                                    >
                                                        {{ $language->is_active ? __('Aktiv') : __('Deaktiv') }}
                                                    </button>
                                                @else
                                                    <span class="badge {{ $language->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $language->is_active ? __('Aktiv') : __('Deaktiv') }}
                                                    </span>
                                                @endcan
                                            </td>
                                            <td class="text-center">
                                                @can('gopanel.settings.languages.edit')
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-success"
                                                        x-on:click="$dispatch('language-form-open', { id: {{ $language->id }} })"
                                                        title="{{ __('Düzəliş') }}"
                                                    >
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                @endcan
                                                @can('gopanel.settings.languages.delete')
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        wire:click="delete({{ $language->id }})"
                                                        wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}"
                                                        title="{{ __('Sil') }}"
                                                        @disabled($language->default)
                                                    >
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if ($this->languages->isEmpty())
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                {{ __('Heç bir dil tapılmadı') }}
                                            </td>
                                        </tr>
                                    @endif
                                </x-gopanel.sortable>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <livewire:gopanel.language.form />
    </div>
</div>
