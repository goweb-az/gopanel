<?php

use App\Livewire\Concerns\AuthorizesGopanel;
use App\Models\Site\Service;
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

    #[On('service-saved')]
    public function refresh(): void
    {
        unset($this->services);
    }

    #[Computed]
    public function services(): Collection
    {
        return Service::orderBy('sort_order')->get();
    }

    public function delete(int $id): void
    {
        $this->authorize('gopanel.services.delete');
        Service::findOrFail($id)->delete();
        unset($this->services);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function reorder(array $ids): void
    {
        $this->authorize('gopanel.services.edit');
        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $id) {
                Service::where('id', $id)->update(['sort_order' => $order]);
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
                    <h4 class="mb-sm-0 font-size-18">{{ __('Xidmətlər') }}</h4>
                    <div class="page-title-right">
                        @can('gopanel.services.add')
                            <button type="button" class="btn btn-success" x-on:click="$dispatch('service-form-open')">
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
                                        <th style="width:70px;">{{ __('Şəkil') }}</th>
                                        <th>{{ __('Başlıq') }}</th>
                                        <th>{{ __('Qısa təsvir') }}</th>
                                        <th style="width:120px;" class="text-center">{{ __('Əməliyyat') }}</th>
                                    </tr>
                                </thead>
                                <x-gopanel.sortable wireMethod="reorder" handle=".drag-handle" tag="tbody">
                                    @foreach ($this->services as $service)
                                        <tr data-id="{{ $service->id }}" wire:key="svc-{{ $service->id }}">
                                            <td class="drag-handle" style="cursor:grab;text-align:center;vertical-align:middle;">
                                                <i class="fas fa-grip-vertical text-muted"></i>
                                            </td>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{!! $service->image_view !!}</td>
                                            <td><strong>{{ $service->getTranslation('title', app()->getLocale(), true) ?? '—' }}</strong></td>
                                            <td>{{ \Illuminate\Support\Str::limit(strip_tags($service->getTranslation('short_description', app()->getLocale(), true) ?? ''), 80) }}</td>
                                            <td class="text-center">
                                                @can('gopanel.services.edit')
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                        x-on:click="$dispatch('service-form-open', { id: {{ $service->id }} })" title="{{ __('Düzəliş') }}">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                @endcan
                                                @can('gopanel.services.delete')
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        wire:click="delete({{ $service->id }})"
                                                        wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}" title="{{ __('Sil') }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if ($this->services->isEmpty())
                                        <tr><td colspan="6" class="text-center text-muted py-4">{{ __('Heç bir xidmət tapılmadı') }}</td></tr>
                                    @endif
                                </x-gopanel.sortable>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <livewire:gopanel.service.form />
    </div>
</div>
