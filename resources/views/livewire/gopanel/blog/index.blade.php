<?php

use App\Actions\Gopanel\Blog\DeleteBlogAction;
use App\Actions\Gopanel\Blog\ToggleBlogActiveAction;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Concerns\WithDatatable;
use App\Models\Site\Blog;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use AuthorizesGopanel, WithDatatable;

    public string $permissionEdit   = 'gopanel.blog.edit';
    public string $permissionDelete = 'gopanel.blog.delete';

    protected function datatableQuery(): Builder
    {
        return Blog::query()->with('translations');
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',         'label' => '#',        'sortable' => true,  'width' => '60px'],
            ['key' => 'image',      'label' => __('Şəkil'), 'width' => '70px'],
            ['key' => 'title',      'label' => __('Başlıq')],
            ['key' => 'date_time',  'label' => __('Tarix'), 'sortable' => true,  'width' => '150px'],
            ['key' => 'views',      'label' => __('Baxış'), 'sortable' => true,  'width' => '90px',  'align' => 'center'],
            ['key' => 'is_active',  'label' => __('Status'),'sortable' => true,  'width' => '90px',  'align' => 'center'],
            ['key' => 'actions',    'label' => __('Əməliyyat'), 'width' => '120px', 'align' => 'center'],
        ];
    }

    public function delete(int $id): void
    {
        $this->authorize($this->permissionDelete);
        DeleteBlogAction::run($id);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function toggleActive(int $id): void
    {
        $this->authorize($this->permissionEdit);
        ToggleBlogActiveAction::run($id);
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-sm-0 font-size-18">{{ __('Bloq') }}</h4>
                        <p class="text-muted mb-0 mt-1">{{ __('Bloq yazılarını şəkil, məzmun, tarix və status ilə birlikdə buradan idarə edə bilərsiniz.') }}</p>
                    </div>
                    <div class="page-title-right">
                        @can('gopanel.blog.add')
                            <a wire:navigate class="btn btn-success" href="{{ route('gopanel.blog.create') }}">
                                <i class="fas fa-plus"></i> {{ __('Əlavə et') }}
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

                        <x-gopanel.datatable
            :rows="$this->rows"
            :columns="$this->columns"
            :sortField="$sortField"
            :sortDirection="$sortDirection"
            :perPage="$perPage"
        >
            @foreach ($this->rows as $record)
                <tr wire:key="blog-{{ $record->id }}">
                    <td>{{ $record->id }}</td>
                    <td>{!! $record->image_view !!}</td>
                    <td><strong>{{ $record->getTranslation('title', app()->getLocale(), true) ?? '—' }}</strong></td>
                    <td>{{ optional($record->date_time)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="text-center">{{ $record->views }}</td>
                    <td class="text-center">
                        @can('gopanel.blog.edit')
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
                        @can('gopanel.blog.edit')
                            <a wire:navigate href="{{ route('gopanel.blog.edit', $record) }}" class="btn btn-sm btn-outline-success" title="{{ __('Düzəliş') }}">
                                <i class="fas fa-pen"></i>
                            </a>
                        @endcan
                        @can('gopanel.blog.delete')
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
