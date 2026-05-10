<?php

use App\Livewire\Concerns\AuthorizesGopanel;
use App\Models\Contact\Social;
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

    #[On('social-saved')]
    public function refresh(): void
    {
        unset($this->socials);
    }

    #[Computed]
    public function socials(): Collection
    {
        return Social::orderBy('sort_order')->get();
    }

    public function delete(int $id): void
    {
        $this->authorize('gopanel.contact.socials.delete');
        Social::findOrFail($id)->delete();
        unset($this->socials);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('gopanel.contact.socials.edit');
        $social = Social::findOrFail($id);
        $social->is_active = ! $social->is_active;
        $social->save();
        unset($this->socials);
    }

    public function reorder(array $ids): void
    {
        $this->authorize('gopanel.contact.socials.edit');
        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $id) {
                Social::where('id', $id)->update(['sort_order' => $order]);
            }
        });
        $this->skipRender();
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Sosial linklər') }}</h4>
                    <div class="page-title-right">
                        @can('gopanel.contact.socials.add')
                            <button type="button" class="btn btn-success" x-on:click="$dispatch('social-form-open')">
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
                                        <th style="width:60px;">{{ __('İkon') }}</th>
                                        <th>{{ __('Adı') }}</th>
                                        <th>{{ __('URL') }}</th>
                                        <th style="width:90px;" class="text-center">{{ __('Status') }}</th>
                                        <th style="width:120px;" class="text-center">{{ __('Əməliyyat') }}</th>
                                    </tr>
                                </thead>
                                <x-gopanel.sortable wireMethod="reorder" handle=".drag-handle" tag="tbody">
                                    @foreach ($this->socials as $social)
                                        <tr data-id="{{ $social->id }}" wire:key="social-{{ $social->id }}">
                                            <td class="drag-handle" style="cursor:grab;text-align:center;vertical-align:middle;">
                                                <i class="fas fa-grip-vertical text-muted"></i>
                                            </td>
                                            <td>{{ $loop->iteration }}</td>
                                            <td class="text-center">{!! $social->icon_html !!}</td>
                                            <td><strong>{{ $social->name }}</strong></td>
                                            <td><a href="{{ $social->url }}" target="_blank" class="text-truncate d-inline-block" style="max-width:300px;">{{ $social->url }}</a></td>
                                            <td class="text-center">
                                                @can('gopanel.contact.socials.edit')
                                                    <button type="button" wire:click="toggleActive({{ $social->id }})"
                                                        class="btn btn-sm {{ $social->is_active ? 'btn-success' : 'btn-secondary' }}">
                                                        {{ $social->is_active ? __('Aktiv') : __('Deaktiv') }}
                                                    </button>
                                                @else
                                                    <span class="badge {{ $social->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $social->is_active ? __('Aktiv') : __('Deaktiv') }}
                                                    </span>
                                                @endcan
                                            </td>
                                            <td class="text-center">
                                                @can('gopanel.contact.socials.edit')
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                        x-on:click="$dispatch('social-form-open', { id: {{ $social->id }} })" title="{{ __('Düzəliş') }}">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                @endcan
                                                @can('gopanel.contact.socials.delete')
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        wire:click="delete({{ $social->id }})"
                                                        wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}" title="{{ __('Sil') }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if ($this->socials->isEmpty())
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
        </div>

        <livewire:gopanel.social.form />
    </div>
</div>
