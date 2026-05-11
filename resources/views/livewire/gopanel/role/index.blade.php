<?php

use App\Actions\Gopanel\Role\DeleteRoleAction;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Concerns\WithDatatable;
use App\Models\Gopanel\CustomRole;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use AuthorizesGopanel, WithDatatable;

    public string $permissionEdit   = 'gopanel.admins.roles.edit';
    public string $permissionDelete = 'gopanel.admins.roles.delete';

    protected function datatableDefaultSort(): array
    {
        return ['id', 'asc'];
    }

    protected function datatableQuery(): Builder
    {
        return CustomRole::query()->withCount('permissions');
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',          'label' => '#',                'sortable' => true,  'width' => '60px'],
            ['key' => 'name',        'label' => __('Vəzifə'),       'sortable' => true,  'searchable' => true],
            ['key' => 'guard_name',  'label' => __('Guard'),        'sortable' => true,  'width' => '120px'],
            ['key' => 'permissions', 'label' => __('İcazə sayı'),   'width' => '120px',  'align' => 'center'],
            ['key' => 'created_at',  'label' => __('Yaradılma'),    'sortable' => true,  'width' => '160px'],
            ['key' => 'actions',     'label' => __('Əməliyyat'),    'width' => '120px',  'align' => 'center'],
        ];
    }

    public function delete(int $id): void
    {
        $this->authorize($this->permissionDelete);
        DeleteRoleAction::run($id);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-sm-0 font-size-18">{{ __('Vəzifələr') }}</h4>
                        <p class="text-muted mb-0 mt-1">{{ __('Admin rollarını və icazələrini idarə edin.') }}</p>
                    </div>
                    <div class="page-title-right">
                        @can('gopanel.admins.roles.add')
                            <a class="btn btn-success" href="{{ route('gopanel.admins.roles.create') }}">
                                <i class="fas fa-plus"></i> {{ __('Əlavə et') }}
                            </a>
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
                        <tr wire:key="role-{{ $record->id }}">
                            <td>{{ $record->id }}</td>
                            <td><strong>{{ $record->name }}</strong></td>
                            <td><code>{{ $record->guard_name }}</code></td>
                            <td class="text-center">
                                <span class="badge bg-info">{{ $record->permissions_count }}</span>
                            </td>
                            <td>{{ $record->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="text-center">
                                @can('gopanel.admins.roles.edit')
                                    <a href="{{ route('gopanel.admins.roles.edit', $record) }}" class="btn btn-sm btn-outline-success" title="{{ __('Düzəliş') }}">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                @endcan
                                @can('gopanel.admins.roles.delete')
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
    </div>
</div>
