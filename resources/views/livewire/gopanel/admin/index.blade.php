<?php

use App\Actions\Gopanel\Admin\DeleteAdminAction;
use App\Actions\Gopanel\Admin\ToggleAdminActiveAction;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Concerns\WithDatatable;
use App\Models\Gopanel\Admin;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use AuthorizesGopanel, WithDatatable;

    public string $sortField = 'id';
    public string $sortDirection = 'asc';

    public string $permissionEdit   = 'gopanel.admins.edit';
    public string $permissionDelete = 'gopanel.admins.delete';

    protected function datatableQuery(): Builder
    {
        return Admin::query()->with('roles:id,name');
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',         'label' => '#',          'sortable' => true,  'width' => '60px'],
            ['key' => 'image',      'label' => __('Avatar'), 'width' => '70px'],
            ['key' => 'full_name',  'label' => __('Ad'),     'sortable' => true,  'searchable' => true],
            ['key' => 'email',      'label' => __('E-poçt'), 'sortable' => true,  'searchable' => true],
            ['key' => 'role',       'label' => __('Vəzifə')],
            ['key' => 'is_super',   'label' => __('Super'),  'sortable' => true,  'width' => '80px',  'align' => 'center'],
            ['key' => 'is_active',  'label' => __('Status'), 'sortable' => true,  'width' => '90px',  'align' => 'center'],
            ['key' => 'actions',    'label' => __('Əməliyyat'), 'width' => '120px', 'align' => 'center'],
        ];
    }

    public function delete(int $id): void
    {
        $this->authorize($this->permissionDelete);
        DeleteAdminAction::run($id);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function toggleActive(int $id): void
    {
        $this->authorize($this->permissionEdit);
        ToggleAdminActiveAction::run($id);
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-sm-0 font-size-18">{{ __('Adminlər') }}</h4>
                        <p class="text-muted mb-0 mt-1">{{ __('Panel adminlərini idarə edin.') }}</p>
                    </div>
                    <div class="page-title-right">
                        @can('gopanel.admins.add')
                            <a class="btn btn-success" href="{{ route('gopanel.admins.create') }}">
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
                        <tr wire:key="admin-{{ $record->id }}">
                            <td>{{ $record->id }}</td>
                            <td>
                                @if ($record->image)
                                    <img src="{{ \App\Helpers\Gopanel\FileUploader::url($record->image) }}" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                @else
                                    <span class="badge bg-light text-muted">—</span>
                                @endif
                            </td>
                            <td><strong>{{ $record->full_name }}</strong></td>
                            <td>{{ $record->email }}</td>
                            <td>
                                @forelse ($record->roles as $role)
                                    <span class="badge bg-info">{{ $role->name }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="text-center">
                                @if ($record->is_super)
                                    <span class="badge bg-warning text-dark">{{ __('Super') }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @can('gopanel.admins.edit')
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
                                @can('gopanel.admins.edit')
                                    <a href="{{ route('gopanel.admins.edit', $record) }}" class="btn btn-sm btn-outline-success" title="{{ __('Düzəliş') }}">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                @endcan
                                @can('gopanel.admins.delete')
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
