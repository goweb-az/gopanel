<?php

use App\Actions\Gopanel\Translation\DeleteTranslationAction;
use App\Enums\Gopanel\TranslationPlatfroms;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Models\Geography\Language;
use App\Models\Translations\Translation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use AuthorizesGopanel, WithPagination;

    public string $permissionEdit   = 'gopanel.settings.translations.edit';
    public string $permissionDelete = 'gopanel.settings.translations.delete';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'platform', except: '')]
    public string $filterPlatform = '';

    #[Url(as: 'pp', except: 25)]
    public int $perPage = 25;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterPlatform(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

    #[On('translation-saved')]
    public function refresh(): void {}

    /**
     * Group translations by (key, platform), returning a paginator over groups.
     */
    #[Computed]
    public function bundles()
    {
        $base = Translation::query()
            ->select('key', 'platform')
            ->distinct();

        if ($this->search !== '') {
            $base->where(function ($q) {
                $q->where('key', 'like', "%{$this->search}%")
                  ->orWhere('value', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterPlatform !== '') {
            $base->where('platform', $this->filterPlatform);
        }

        $bundles = $base->orderBy('platform')->orderBy('key')->paginate($this->perPage);

        // Eager-load all locale rows for the current page in one query.
        $pairs = collect($bundles->items())->map(fn ($r) => $r->key . '||' . $r->platform);
        $rows = Translation::query()
            ->whereIn('key', $bundles->getCollection()->pluck('key'))
            ->whereIn('platform', $bundles->getCollection()->pluck('platform'))
            ->get()
            ->groupBy(fn ($r) => $r->key . '||' . $r->platform);

        $bundles->setCollection(
            $bundles->getCollection()->map(function ($b) use ($rows) {
                $b->locale_values = $rows->get($b->key . '||' . $b->platform, collect())
                    ->keyBy('locale')
                    ->map->value
                    ->toArray();
                return $b;
            })
        );

        return $bundles;
    }

    public function delete(string $key, string $platform): void
    {
        $this->authorize($this->permissionDelete);
        DeleteTranslationAction::run($key, $platform);
        unset($this->bundles);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Tərcümələr') }}</h4>
                    <div class="page-title-right">
                        @can('gopanel.settings.translations.add')
                            <button type="button" class="btn btn-success" x-on:click="$dispatch('translation-form-open')">
                                <i class="fas fa-plus"></i> {{ __('Əlavə et') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" placeholder="{{ __('Açar və ya dəyər üzrə axtar...') }}"
                                wire:model.live.debounce.400ms="search">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" wire:model.live="filterPlatform">
                            <option value="">{{ __('Bütün platformalar') }}</option>
                            @foreach (TranslationPlatfroms::cases() as $case)
                                <option value="{{ $case->value }}">{{ ucfirst($case->value) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" wire:model.live="perPage">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}">{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="gp-datatable">
                    <div class="gp-datatable__wrapper">
                    <table class="gp-datatable__table">
                        <thead>
                            <tr>
                                <th style="width:200px;">{{ __('Açar') }}</th>
                                <th style="width:100px;">{{ __('Platforma') }}</th>
                                @foreach (\App\Models\Geography\Language::getCachedAll() as $lang)
                                    <th>{{ strtoupper($lang->code) }}</th>
                                @endforeach
                                <th style="width:120px;" class="text-center">{{ __('Əməliyyat') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->bundles as $bundle)
                                <tr wire:key="t-{{ $bundle->key }}-{{ $bundle->platform }}">
                                    <td><code>{{ $bundle->key }}</code></td>
                                    <td><span class="badge bg-info">{{ $bundle->platform }}</span></td>
                                    @foreach (\App\Models\Geography\Language::getCachedAll() as $lang)
                                        <td>{{ \Illuminate\Support\Str::limit($bundle->locale_values[$lang->code] ?? '', 60) ?: '—' }}</td>
                                    @endforeach
                                    <td class="text-center">
                                        @can('gopanel.settings.translations.edit')
                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                x-on:click="$dispatch('translation-form-open', { key: '{{ $bundle->key }}', platform: '{{ $bundle->platform }}' })"
                                                title="{{ __('Düzəliş') }}">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        @endcan
                                        @can('gopanel.settings.translations.delete')
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                wire:click="delete('{{ $bundle->key }}', '{{ $bundle->platform }}')"
                                                wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}" title="{{ __('Sil') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach

                            @if ($this->bundles->isEmpty())
                                <tr>
                                    <td colspan="{{ 3 + count(\App\Models\Geography\Language::getCachedAll()) }}" class="text-center text-muted py-4">
                                        {{ __('Heç nə tapılmadı') }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if ($this->bundles->hasPages())
                    <div class="mt-3">
                        {{ $this->bundles->onEachSide(1)->links('livewire::bootstrap') }}
                    </div>
                @endif
            </div>
        </div>

        <livewire:gopanel.translation.form />
    </div>
</div>
