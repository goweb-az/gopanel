<?php

use App\Livewire\Concerns\AuthorizesGopanel;
use App\Models\Site\Slider;
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

    #[On('slider-saved')]
    public function refresh(): void
    {
        unset($this->sliders);
    }

    #[Computed]
    public function sliders(): Collection
    {
        return Slider::orderBy('sort_order')->get();
    }

    public function delete(int $id): void
    {
        $this->authorize('gopanel.slider.delete');
        Slider::findOrFail($id)->delete();
        unset($this->sliders);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('gopanel.slider.edit');
        $slider = Slider::findOrFail($id);
        $slider->is_active = ! $slider->is_active;
        $slider->save();
        unset($this->sliders);
    }

    public function reorder(array $ids): void
    {
        $this->authorize('gopanel.slider.edit');
        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $id) {
                Slider::where('id', $id)->update(['sort_order' => $order]);
            }
        });
        $this->dispatch('notify', type: 'success', message: __('Sıra yeniləndi'));
        $this->skipRender();
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Slayder') }}</h4>

                    <div class="page-title-right">
                        @can('gopanel.slider.add')
                            <button
                                type="button"
                                class="btn btn-success"
                                x-on:click="$dispatch('slider-form-open')"
                            >
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
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:20px;"></th>
                                        <th style="width:50px;">#</th>
                                        <th>{{ __('Şəkil') }}</th>
                                        <th>{{ __('Başlıq') }}</th>
                                        <th>{{ __('Məlumat') }}</th>
                                        <th style="width:90px;" class="text-center">{{ __('Status') }}</th>
                                        <th style="width:130px;" class="text-center">{{ __('Əməliyyat') }}</th>
                                    </tr>
                                </thead>
                                <x-gopanel.sortable
                                    wireMethod="reorder"
                                    handle=".drag-handle"
                                    tag="tbody"
                                >
                                    @foreach ($this->sliders as $slider)
                                        <tr data-id="{{ $slider->id }}" wire:key="slider-{{ $slider->id }}">
                                            <td class="drag-handle" style="cursor:grab;text-align:center;vertical-align:middle;">
                                                <i class="fas fa-grip-vertical text-muted"></i>
                                            </td>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{!! $slider->image_view !!}</td>
                                            <td>
                                                <strong>{{ $slider->getTranslation('title', app()->getLocale(), true) ?? '—' }}</strong>
                                            </td>
                                            <td>
                                                {{ \Illuminate\Support\Str::limit(strip_tags($slider->getTranslation('description', app()->getLocale(), true) ?? ''), 80) }}
                                            </td>
                                            <td class="text-center">
                                                @can('gopanel.slider.edit')
                                                    <button
                                                        type="button"
                                                        wire:click="toggleActive({{ $slider->id }})"
                                                        class="btn btn-sm {{ $slider->is_active ? 'btn-success' : 'btn-secondary' }}"
                                                    >
                                                        {{ $slider->is_active ? __('Aktiv') : __('Deaktiv') }}
                                                    </button>
                                                @else
                                                    <span class="badge {{ $slider->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $slider->is_active ? __('Aktiv') : __('Deaktiv') }}
                                                    </span>
                                                @endcan
                                            </td>
                                            <td class="text-center">
                                                @can('gopanel.slider.edit')
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-success"
                                                        x-on:click="$dispatch('slider-form-open', { id: {{ $slider->id }} })"
                                                        title="{{ __('Düzəliş et') }}"
                                                    >
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                @endcan
                                                @can('gopanel.slider.delete')
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        wire:click="delete({{ $slider->id }})"
                                                        wire:confirm="{{ __('Silmək istədiyinizə əminsiniz?') }}"
                                                        title="{{ __('Sil') }}"
                                                    >
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if ($this->sliders->isEmpty())
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-images fa-2x mb-2 d-block"></i>
                                                {{ __('Heç bir slayd tapılmadı') }}
                                            </td>
                                        </tr>
                                    @endif
                                </x-gopanel.sortable>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <livewire:gopanel.slider.form />
    </div>
</div>
