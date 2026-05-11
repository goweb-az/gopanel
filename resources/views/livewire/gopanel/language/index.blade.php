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

    public string $permissionEdit   = 'gopanel.settings.languages.edit';
    public string $permissionDelete = 'gopanel.settings.languages.delete';

    #[On('language-saved')]
    public function refresh(): void
    {
        unset($this->records);
    }

    #[Computed]
    public function records(): Collection
    {
        return Language::orderByDesc('default')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with('country:id,name')
            ->get();
    }

    public function delete(int $id): void
    {
        $this->authorize($this->permissionDelete);
        Language::findOrFail($id)->delete();
        Language::ensureFallbackDefault();
        unset($this->records);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function toggleActive(int $id): void
    {
        $this->authorize($this->permissionEdit);
        $record = Language::findOrFail($id);

        if ($record->default && $record->is_active) {
            $this->dispatch('notify', type: 'warning', message: __('Default dili deaktiv etmək olmaz'));
            return;
        }

        $record->is_active = ! $record->is_active;
        $record->save();
        unset($this->records);
    }

    public function toggleDefault(int $id): void
    {
        $this->authorize($this->permissionEdit);
        $record = Language::findOrFail($id);

        DB::transaction(function () use ($record) {
            if ($record->default) {
                $record->forceFill(['default' => false])->save();
                Language::ensureFallbackDefault();
            } else {
                Language::query()->update(['default' => false]);
                $record->forceFill(['default' => true, 'is_active' => true])->save();
            }
        });

        unset($this->records);
        $this->dispatch('notify', type: 'success', message: __('Default dil yeniləndi'));
    }

    public function reorder(array $ids): void
    {
        $this->authorize($this->permissionEdit);
        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $id) {
                Language::where('id', $id)->update(['sort_order' => $order]);
            }
        });
        // Do not invalidate $this->records — DOM is already in the correct order
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
                <div class="gp-datatable">
                    <div class="gp-datatable__wrapper">
                            <table class="gp-datatable__table">
                                <thead>
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
                                    @foreach ($this->records as $record)
                                        <tr data-id="{{ $record->id }}" wire:key="lang-{{ $record->id }}">
                                            <td class="drag-handle" style="cursor:grab;text-align:center;vertical-align:middle;">
                                                <i class="fas fa-grip-vertical text-muted"></i>
                                            </td>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><strong>{{ $record->name }}</strong></td>
                                            <td><code>{{ $record->upper_code }}</code></td>
                                            <td>{{ $record?->country?->name ?? '—' }}</td>
                                            <td class="text-center">
                                                @can('gopanel.settings.languages.edit')
                                                    <button
                                                        type="button"
                                                        wire:click="toggleDefault({{ $record->id }})"
                                                        class="btn btn-sm {{ $record->default ? 'btn-primary' : 'btn-outline-secondary' }}"
                                                    >
                                                        {{ $record->default ? __('Bəli') : __('Xeyr') }}
                                                    </button>
                                                @else
                                                    <span class="badge {{ $record->default ? 'bg-primary' : 'bg-secondary' }}">
                                                        {{ $record->default ? __('Bəli') : __('Xeyr') }}
                                                    </span>
                                                @endcan
                                            </td>
                                            <td class="text-center">
                                                @can('gopanel.settings.languages.edit')
                                                    <button
                                                        type="button"
                                                        wire:click="toggleActive({{ $record->id }})"
                                                        class="btn btn-sm {{ $record->is_active ? 'btn-success' : 'btn-secondary' }}"
                                                    >
                                                        {{ $record->is_active ? __('Aktiv') : __('Deaktiv') }}
                                                    </button>
                                                @else
                                                    <span class="badge {{ $record->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $record->is_active ? __('Aktiv') : __('Deaktiv') }}
                                                    </span>
                                                @endcan
                                            </td>
                                            <td class="text-center">
                                                @can('gopanel.settings.languages.edit')
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-success"
                                                        x-on:click="$dispatch('language-form-open', { id: {{ $record->id }} })"
                                                        title="{{ __('Düzəliş') }}"
                                                    >
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                @endcan
                                                @can('gopanel.settings.languages.delete')
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        wire:click="delete({{ $record->id }})"
                                                        wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}"
                                                        title="{{ __('Sil') }}"
                                                        @disabled($record->default)
                                                    >
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if ($this->records->isEmpty())
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

        <livewire:gopanel.language.form />
    </div>
</div>
