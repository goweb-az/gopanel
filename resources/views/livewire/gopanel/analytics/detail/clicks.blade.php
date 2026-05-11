<?php

use App\Livewire\Concerns\WithDatatable;
use App\Models\Analytics\AnalyticsBrowser;
use App\Models\Analytics\AnalyticsClick;
use App\Models\Analytics\AnalyticsDevice;
use App\Models\Analytics\AnalyticsOperatingSystem;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use WithDatatable;

    #[Url(as: 'device_id')]
    public ?int $deviceId = null;

    #[Url(as: 'os_id')]
    public ?int $osId = null;

    #[Url(as: 'browser_id')]
    public ?int $browserId = null;

    #[Url(as: 'from')]
    public ?string $from = null;

    #[Url(as: 'to')]
    public ?string $to = null;

    public function clearFilter(): void
    {
        $this->reset(['deviceId', 'osId', 'browserId', 'from', 'to']);
    }

    protected function datatableDefaultSort(): array
    {
        return ['created_at', 'desc'];
    }

    protected function datatableQuery(): Builder
    {
        $q = AnalyticsClick::query()->with(['country', 'city', 'device', 'browser', 'operatingSystem', 'language']);
        if ($this->deviceId)  $q->where('device_id',           $this->deviceId);
        if ($this->osId)      $q->where('operating_system_id', $this->osId);
        if ($this->browserId) $q->where('browser_id',          $this->browserId);
        if ($this->from)      $q->whereDate('created_at', '>=', $this->from);
        if ($this->to)        $q->whereDate('created_at', '<=', $this->to);
        return $q;
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',          'label' => '#',                    'sortable' => true, 'width' => '70px'],
            ['key' => 'url',         'label' => __('URL')],
            ['key' => 'ip',          'label' => __('IP'),               'width' => '140px'],
            ['key' => 'device',      'label' => __('Cihaz'),            'width' => '110px'],
            ['key' => 'os',          'label' => __('Əməliyyat sistemi'),'width' => '140px'],
            ['key' => 'browser',     'label' => __('Brauzer'),          'width' => '110px'],
            ['key' => 'country',     'label' => __('Ölkə'),             'width' => '140px'],
            ['key' => 'city',        'label' => __('Şəhər'),            'width' => '140px'],
            ['key' => 'created_at',  'label' => __('Tarix'),            'sortable' => true, 'width' => '160px'],
        ];
    }

    public function getDeviceOptionsProperty()  { return AnalyticsDevice::orderBy('device_type')->get(['id', 'device_type']); }
    public function getOsOptionsProperty()      { return AnalyticsOperatingSystem::orderBy('name')->get(['id', 'name']); }
    public function getBrowserOptionsProperty() { return AnalyticsBrowser::orderBy('name')->get(['id', 'name']); }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Klikləmələr üzrə statistikalar') }}</h4>
                    <div class="page-title-right d-flex align-items-center gap-2" x-data="{ filterOpen: {{ ($deviceId || $osId || $browserId || $from || $to) ? 'true' : 'false' }} }">
                        @if ($deviceId || $osId || $browserId || $from || $to)
                            <button type="button" wire:click="clearFilter" class="btn btn-outline-danger">
                                <i class="fas fa-times-circle"></i> {{ __('Filteri sil') }}
                            </button>
                        @endif
                        <button type="button" class="btn btn-outline-primary" x-on:click="filterOpen = !filterOpen">
                            <template x-if="filterOpen"><span><i class="fas fa-times"></i> {{ __('Filteri bağla') }}</span></template>
                            <template x-if="!filterOpen"><span><i class="fas fa-filter"></i> {{ __('Filteri aç') }}</span></template>
                        </button>
                        <a wire:navigate href="{{ route('gopanel.analytics.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> {{ __('Analitikə dön') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" x-data="{ open: {{ ($deviceId || $osId || $browserId || $from || $to) ? 'true' : 'false' }} }" x-show="open" x-cloak>
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Cihaz') }}</label>
                                <select class="form-select" wire:model.live="deviceId">
                                    <option value="">{{ __('Hamısı') }}</option>
                                    @foreach ($this->deviceOptions as $d)
                                        <option value="{{ $d->id }}">{{ $d->device_type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Əməliyyat sistemi') }}</label>
                                <select class="form-select" wire:model.live="osId">
                                    <option value="">{{ __('Hamısı') }}</option>
                                    @foreach ($this->osOptions as $o)
                                        <option value="{{ $o->id }}">{{ $o->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Brauzerlər') }}</label>
                                <select class="form-select" wire:model.live="browserId">
                                    <option value="">{{ __('Hamısı') }}</option>
                                    @foreach ($this->browserOptions as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Başlama tarixi') }}</label>
                                <input type="date" class="form-control" wire:model.live="from">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Bitmə tarixi') }}</label>
                                <input type="date" class="form-control" wire:model.live="to">
                            </div>
                        </div>
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
                <tr wire:key="click-{{ $record->id }}">
                    <td>{{ $record->id }}</td>
                    <td><code style="font-size:12px;">{{ \Illuminate\Support\Str::limit($record->url, 60) }}</code></td>
                    <td>{{ $record->ip_address }}</td>
                    <td>{{ $record->device?->device_type ?? '—' }}</td>
                    <td>{{ $record->operatingSystem?->name ?? '—' }}</td>
                    <td>{{ $record->browser?->name ?? '—' }}</td>
                    <td>
                        @if ($record->country?->flag)<img src="{{ $record->country->flag }}" alt="" style="width:18px;height:auto;margin-right:6px;">@endif
                        {{ $record->country?->name ?? '—' }}
                    </td>
                    <td>{{ $record->city?->name ?? '—' }}</td>
                    <td>{{ $record->created_at?->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </x-gopanel.datatable>
    </div>
</div>
