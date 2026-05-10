<?php

use App\Actions\Gopanel\SiteRedirect\DeleteSiteRedirectAction;
use App\Actions\Gopanel\SiteRedirect\ToggleSiteRedirectActiveAction;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Concerns\WithDatatable;
use App\Models\Seo\SiteRedirect;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use AuthorizesGopanel, WithDatatable;

    public string $sortField     = 'priority';
    public string $sortDirection = 'desc';

    public string $permissionEdit   = 'gopanel.seo.site-redirects.edit';
    public string $permissionDelete = 'gopanel.seo.site-redirects.delete';

    #[On('site-redirect-saved')]
    public function refresh(): void
    {
        // Datatable rebuilds rows automatically.
    }

    protected function datatableQuery(): Builder
    {
        return SiteRedirect::query();
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',         'label' => '#',           'sortable' => true,  'width' => '60px'],
            ['key' => 'locale',     'label' => __('Dil'),      'sortable' => true,  'width' => '70px'],
            ['key' => 'source',     'label' => __('Mənbə'),    'sortable' => true,  'searchable' => true],
            ['key' => 'target',     'label' => __('Hədəf'),    'searchable' => true],
            ['key' => 'http_code',  'label' => __('Kod'),      'sortable' => true,  'width' => '70px',  'align' => 'center'],
            ['key' => 'priority',   'label' => __('Pri'),      'sortable' => true,  'width' => '70px',  'align' => 'center'],
            ['key' => 'hits',       'label' => __('Hits'),     'sortable' => true,  'width' => '80px',  'align' => 'center'],
            ['key' => 'is_active',  'label' => __('Status'),   'sortable' => true,  'width' => '90px',  'align' => 'center'],
            ['key' => 'actions',    'label' => __('Əməliyyat'),'width' => '120px', 'align' => 'center'],
        ];
    }

    public function delete(int $id): void
    {
        $this->authorize($this->permissionDelete);
        DeleteSiteRedirectAction::run($id);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function toggleActive(int $id): void
    {
        $this->authorize($this->permissionEdit);
        ToggleSiteRedirectActiveAction::run($id);
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Yönləndirmələr') }}</h4>
                    <div class="page-title-right">
                        @can('gopanel.seo.site-redirects.add')
                            <button type="button" class="btn btn-success" x-on:click="$dispatch('site-redirect-form-open')">
                                <i class="fas fa-plus"></i> {{ __('Əlavə et') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <x-gopanel.datatable
                    :rows="$this->rows"
                    :columns="$this->columns"
                    :sortField="$sortField"
                    :sortDirection="$sortDirection"
                    :perPage="$perPage"
                >
                    @foreach ($this->rows as $record)
                        <tr wire:key="rd-{{ $record->id }}">
                            <td>{{ $record->id }}</td>
                            <td>{{ $record->locale ? strtoupper($record->locale) : '*' }}</td>
                            <td><code>{{ $record->source }}</code></td>
                            <td><code>{{ $record->target }}</code></td>
                            <td class="text-center"><span class="badge bg-info">{{ $record->http_code }}</span></td>
                            <td class="text-center">{{ $record->priority }}</td>
                            <td class="text-center">{{ $record->hits ?? 0 }}</td>
                            <td class="text-center">
                                @can('gopanel.seo.site-redirects.edit')
                                    <button type="button" wire:click="toggleActive({{ $record->id }})"
                                        class="btn btn-sm {{ $record->is_active ? 'btn-success' : 'btn-secondary' }}">
                                        {{ $record->is_active ? __('Aktiv') : __('Deaktiv') }}
                                    </button>
                                @else
                                    <span class="badge {{ $record->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $record->is_active ? __('Aktiv') : __('Deaktiv') }}
                                    </span>
                                @endcan
                            </td>
                            <td class="text-center">
                                @can('gopanel.seo.site-redirects.edit')
                                    <button type="button" class="btn btn-sm btn-outline-success"
                                        x-on:click="$dispatch('site-redirect-form-open', { id: {{ $record->id }} })" title="{{ __('Düzəliş') }}">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                @endcan
                                @can('gopanel.seo.site-redirects.delete')
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        wire:click="delete({{ $record->id }})"
                                        wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}" title="{{ __('Sil') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </x-gopanel.datatable>
            </div>
        </div>

        <livewire:gopanel.site-redirect.form />
    </div>
</div>
