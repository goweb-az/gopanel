<?php

use App\Actions\Gopanel\Social\DeleteSocialAction;
use App\Actions\Gopanel\Social\ReorderSocialsAction;
use App\Actions\Gopanel\Social\ToggleSocialActiveAction;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Models\Contact\Social;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use AuthorizesGopanel;

    public string $permissionEdit   = 'gopanel.contact.socials.edit';
    public string $permissionDelete = 'gopanel.contact.socials.delete';

    #[On('social-saved')]
    public function refresh(): void
    {
        unset($this->records);
    }

    #[Computed]
    public function records(): Collection
    {
        return Social::orderBy('sort_order')->get();
    }

    public function delete(int $id): void
    {
        $this->authorize($this->permissionDelete);
        DeleteSocialAction::run($id);
        unset($this->records);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function toggleActive(int $id): void
    {
        $this->authorize($this->permissionEdit);
        ToggleSocialActiveAction::run($id);
        unset($this->records);
    }

    public function reorder(array $ids): void
    {
        $this->authorize($this->permissionEdit);
        ReorderSocialsAction::run($ids);
        $this->skipRender();
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <x-gopanel.page-header
            :title="__('Sosial linklər')"
            createEvent="social-form-open"
            createPermission="gopanel.contact.socials.add"
        />

        <div class="row">
            <div class="col-xl-12">
                <div class="gp-datatable">
                    <div class="gp-datatable__wrapper">
                            <table class="gp-datatable__table">
                                <thead>
                                    <tr>
                                        <th style="width:20px;"></th>
                                        <th style="width:50px;">#</th>
                                        <th style="width:60px;">{{ __('İkon') }}</th>
                                        <th>{{ __('Adı') }}</th>
                                        <th>{{ __('URL') }}</th>
                                        <th style="width:90px;" class="text-center">{{ __('Status') }}</th>
                                        <th style="width:120px;" class="text-center">{{ __('Əməliyyat') }}</th>
                                    </tr>
                                </thead>
                                <x-gopanel.sortable wireMethod="reorder" handle=".drag-handle" tag="tbody">
                                    @foreach ($this->records as $record)
                                        <tr data-id="{{ $record->id }}" wire:key="social-{{ $record->id }}">
                                            <td class="drag-handle" style="cursor:grab;text-align:center;vertical-align:middle;">
                                                <i class="fas fa-grip-vertical text-muted"></i>
                                            </td>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="text-center">{!! $record->icon_html !!}</td>
                                            <td><strong>{{ $record->name }}</strong></td>
                                            <td><a href="{{ $record->url }}" target="_blank" class="text-truncate d-inline-block" style="max-width:300px;">{{ $record->url }}</a></td>
                                            <td class="text-center">
                                                @can('gopanel.contact.socials.edit')
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
                                                @can('gopanel.contact.socials.edit')
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                        x-on:click="$dispatch('social-form-open', { id: {{ $record->id }} })" title="{{ __('Düzəliş') }}">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                @endcan
                                                @can('gopanel.contact.socials.delete')
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
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">{{ __('Heç bir link tapılmadı') }}</td>
                                        </tr>
                                    @endif
                                </x-gopanel.sortable>
                            </table>
                        </div>
                </div>
            </div>
        </div>

        <livewire:gopanel.social.form />
    </div>
</div>
