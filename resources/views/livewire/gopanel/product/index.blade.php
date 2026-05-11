<?php

use App\Actions\Gopanel\Product\DeleteProductAction;
use App\Actions\Gopanel\Product\ToggleProductActiveAction;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Concerns\WithDatatable;
use App\Models\Site\Product;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use AuthorizesGopanel, WithDatatable;

    public string $permissionEdit   = 'gopanel.products.edit';
    public string $permissionDelete = 'gopanel.products.delete';

    protected function datatableQuery(): Builder
    {
        return Product::query()->with('translations');
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',         'label' => '#',          'sortable' => true,  'width' => '60px'],
            ['key' => 'image',      'label' => __('Şəkil'),  'width' => '70px'],
            ['key' => 'title',      'label' => __('Başlıq')],
            ['key' => 'price',      'label' => __('Qiymət'), 'sortable' => true,  'width' => '110px',  'align' => 'end'],
            ['key' => 'discount',   'label' => __('Endirim'),'sortable' => true,  'width' => '110px',  'align' => 'end'],
            ['key' => 'is_active',  'label' => __('Status'), 'sortable' => true,  'width' => '90px',   'align' => 'center'],
            ['key' => 'actions',    'label' => __('Əməliyyat'), 'width' => '120px', 'align' => 'center'],
        ];
    }

    public function delete(int $id): void
    {
        $this->authorize($this->permissionDelete);
        DeleteProductAction::run($id);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function toggleActive(int $id): void
    {
        $this->authorize($this->permissionEdit);
        ToggleProductActiveAction::run($id);
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <x-gopanel.page-header
            :title="__('Məhsullar')"
            :caption="__('Məhsulları qiymət, endirim və status ilə birlikdə buradan idarə edə bilərsiniz.')"
            :createUrl="route('gopanel.products.create')"
            createPermission="gopanel.products.add"
        />

                        <x-gopanel.datatable
            :rows="$this->rows"
            :columns="$this->columns"
            :sortField="$sortField"
            :sortDirection="$sortDirection"
            :perPage="$perPage"
        >
            @foreach ($this->rows as $record)
                <tr wire:key="product-{{ $record->id }}">
                    <td>{{ $record->id }}</td>
                    <td>{!! $record->image_view !!}</td>
                    <td><strong>{{ $record->getTranslation('title', app()->getLocale(), true) ?? '—' }}</strong></td>
                    <td class="text-end">{{ number_format((float) $record->price, 2, '.', ' ') }} ₼</td>
                    <td class="text-end">
                        @if ($record->discount !== null && (float) $record->discount > 0)
                            <span class="text-danger">{{ number_format((float) $record->discount, 2, '.', ' ') }} ₼</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @can('gopanel.products.edit')
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
                        @can('gopanel.products.edit')
                            <a wire:navigate href="{{ route('gopanel.products.edit', $record) }}" class="btn btn-sm btn-outline-success" title="{{ __('Düzəliş') }}">
                                <i class="fas fa-pen"></i>
                            </a>
                        @endcan
                        @can('gopanel.products.delete')
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
