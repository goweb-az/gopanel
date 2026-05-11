<?php

use App\Actions\Gopanel\Category\DeleteCategoryAction;
use App\Actions\Gopanel\Category\ReorderCategoriesAction;
use App\Actions\Gopanel\Category\ToggleCategoryActiveAction;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Models\Navigation\Category;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use AuthorizesGopanel;

    #[Url]
    public ?int $parent_id = null;

    public string $permissionEdit   = 'gopanel.categories.edit';
    public string $permissionDelete = 'gopanel.categories.delete';

    #[On('category-saved')]
    public function refresh(): void
    {
        unset($this->records);
    }

    #[Computed]
    public function records(): Collection
    {
        return Category::query()
            ->where('parent_id', $this->parent_id)
            ->orderBy('sort_order')
            ->withCount(['children'])
            ->get();
    }

    #[Computed]
    public function parent(): ?Category
    {
        return $this->parent_id ? Category::find($this->parent_id) : null;
    }

    public function delete(int $id): void
    {
        $this->authorize($this->permissionDelete);
        DeleteCategoryAction::run($id);
        unset($this->records);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function toggleActive(int $id): void
    {
        $this->authorize($this->permissionEdit);
        ToggleCategoryActiveAction::run($id);
        unset($this->records);
    }

    public function reorder(array $ids): void
    {
        $this->authorize($this->permissionEdit);
        ReorderCategoriesAction::run($ids);
        $this->skipRender();
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-sm-0 font-size-18">{{ __('Kateqoriyalar') }}</h4>
                        @if ($this->parent)
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a wire:navigate href="{{ route('gopanel.categories.index') }}">{{ __('Kök') }}</a></li>
                                    <li class="breadcrumb-item active">
                                        {{ $this->parent->getTranslation('name', app()->getLocale(), true) ?? '#' . $this->parent->id }}
                                    </li>
                                </ol>
                            </nav>
                        @endif
                    </div>
                    <div class="page-title-right">
                        @can('gopanel.categories.add')
                            <button type="button" class="btn btn-success"
                                x-on:click="$dispatch('category-form-open', { parentId: {{ $parent_id ?? 'null' }} })">
                                <i class="fas fa-plus"></i> {{ __('Əlavə et') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="gp-datatable">
                    <div class="gp-datatable__wrapper">
                    <table class="gp-datatable__table">
                        <thead>
                            <tr>
                                <th style="width:20px;"></th>
                                <th style="width:50px;">#</th>
                                <th style="width:50px;" class="text-center">{{ __('İkon') }}</th>
                                <th>{{ __('Adı') }}</th>
                                <th style="width:90px;" class="text-center">{{ __('Status') }}</th>
                                <th style="width:90px;" class="text-center">{{ __('Alt') }}</th>
                                <th style="width:130px;" class="text-center">{{ __('Əməliyyat') }}</th>
                            </tr>
                        </thead>
                        <x-gopanel.sortable wireMethod="reorder" handle=".drag-handle" tag="tbody">
                            @foreach ($this->records as $record)
                                <tr data-id="{{ $record->id }}" wire:key="cat-{{ $record->id }}">
                                    <td class="drag-handle" style="cursor:grab;text-align:center;vertical-align:middle;">
                                        <i class="fas fa-grip-vertical text-muted"></i>
                                    </td>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-center">
                                        @if ($record->icon)
                                            <i class="{{ $record->icon }}" @if ($record->color) style="color:{{ $record->color }}" @endif></i>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $record->getTranslation('name', app()->getLocale(), true) ?? '—' }}</strong>
                                    </td>
                                    <td class="text-center">
                                        @can('gopanel.categories.edit')
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
                                        <a wire:navigate href="{{ route('gopanel.categories.index', ['parent_id' => $record->id]) }}"
                                            class="btn btn-sm btn-outline-info" title="{{ __('Alt kateqoriyalar') }}">
                                            <i class="fas fa-sitemap"></i> {{ $record->children_count }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        @can('gopanel.categories.edit')
                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                x-on:click="$dispatch('category-form-open', { id: {{ $record->id }} })" title="{{ __('Düzəliş') }}">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        @endcan
                                        @can('gopanel.categories.delete')
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                wire:click="delete({{ $record->id }})"
                                                wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}" title="{{ __('Sil') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach

                            @if ($this->records->isEmpty())
                                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Heç bir kateqoriya tapılmadı') }}</td></tr>
                            @endif
                        </x-gopanel.sortable>
                    </table>
                </div>
        </div>

        <livewire:gopanel.category.form />
    </div>
</div>
