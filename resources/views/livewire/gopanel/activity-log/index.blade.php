<?php

use App\Actions\Gopanel\Activity\CleanupActivityLogsAction;
use App\Actions\Gopanel\Activity\DeleteActivityAction;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Concerns\WithDatatable;
use App\Models\Activity\Activity;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use AuthorizesGopanel, WithDatatable;

    public string $sortField     = 'id';
    public string $sortDirection = 'desc';

    public string $permissionDelete = 'gopanel.activity.activity-logs.delete';

    #[Url(as: 'event', except: '')]
    public string $filterEvent = '';

    #[Url(as: 'log', except: '')]
    public string $filterLogName = '';

    #[Url(as: 'from', except: '')]
    public string $filterDateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $filterDateTo = '';

    public bool $cleanupOpen = false;
    public ?int $cleanupDays = 30;

    public function updatedFilterEvent(): void { $this->resetPage(); }
    public function updatedFilterLogName(): void { $this->resetPage(); }
    public function updatedFilterDateFrom(): void { $this->resetPage(); }
    public function updatedFilterDateTo(): void { $this->resetPage(); }

    protected function datatableQuery(): Builder
    {
        $query = Activity::query()->with('causer');

        if ($this->filterEvent !== '') {
            $query->where('event', $this->filterEvent);
        }

        if ($this->filterLogName !== '') {
            $query->where('log_name', $this->filterLogName);
        }

        if ($this->filterDateFrom !== '') {
            $query->where('created_at', '>=', $this->filterDateFrom . ' 00:00:00');
        }

        if ($this->filterDateTo !== '') {
            $query->where('created_at', '<=', $this->filterDateTo . ' 23:59:59');
        }

        return $query;
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',          'label' => '#',                 'sortable' => true,  'width' => '70px'],
            ['key' => 'log_name',    'label' => __('Model'),         'sortable' => true,  'width' => '150px'],
            ['key' => 'event',       'label' => __('Əməliyyat'),     'sortable' => true,  'width' => '110px'],
            ['key' => 'description', 'label' => __('Mesaj'),         'searchable' => true],
            ['key' => 'causer',      'label' => __('Kim'),           'width' => '160px'],
            ['key' => 'created_at',  'label' => __('Tarix'),         'sortable' => true,  'width' => '160px'],
            ['key' => 'actions',     'label' => __('Əməliyyat'),     'width' => '90px',  'align' => 'center'],
        ];
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $query->where(function (Builder $q) use ($term) {
            $q->where('description', 'LIKE', "%{$term}%")
              ->orWhere('log_name', 'LIKE', "%{$term}%");
        });
    }

    public function delete(int $id): void
    {
        $this->authorize($this->permissionDelete);
        DeleteActivityAction::run($id);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function runCleanup(): void
    {
        $this->authorize($this->permissionDelete);
        $deleted = CleanupActivityLogsAction::run($this->cleanupDays);
        $this->cleanupOpen = false;
        $this->resetPage();
        $this->dispatch('notify', type: 'success', message: __(':count log silindi', ['count' => $deleted]));
    }

    public function getEventOptionsProperty(): array
    {
        return Activity::query()->select('event')->distinct()->whereNotNull('event')->pluck('event')->all();
    }

    public function getLogNameOptionsProperty(): array
    {
        return Activity::query()->select('log_name')->distinct()->whereNotNull('log_name')->pluck('log_name')->all();
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Tarixçə') }}</h4>
                    <div class="page-title-right">
                        @can('gopanel.activity.activity-logs.delete')
                            <button type="button" class="btn btn-outline-danger" wire:click="$set('cleanupOpen', true)">
                                <i class="fas fa-broom"></i> {{ __('Təmizləmə') }}
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">{{ __('Model') }}</label>
                        <select class="form-select form-select-sm" wire:model.live="filterLogName">
                            <option value="">{{ __('Hamısı') }}</option>
                            @foreach ($this->logNameOptions as $name)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">{{ __('Əməliyyat') }}</label>
                        <select class="form-select form-select-sm" wire:model.live="filterEvent">
                            <option value="">{{ __('Hamısı') }}</option>
                            @foreach ($this->eventOptions as $event)
                                <option value="{{ $event }}">{{ $event }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">{{ __('Tarix (dan)') }}</label>
                        <input type="date" class="form-control form-control-sm" wire:model.live="filterDateFrom">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">{{ __('Tarix (dək)') }}</label>
                        <input type="date" class="form-control form-control-sm" wire:model.live="filterDateTo">
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
                        <tr wire:key="act-{{ $record->id }}">
                            <td>{{ $record->id }}</td>
                            <td>{!! $record->log_name_badge !!}</td>
                            <td>{!! $record->event_badge !!}</td>
                            <td>{!! $record->description_short !!}</td>
                            <td>{{ $record->causer_name }}</td>
                            <td>{{ $record->created_at_formatted }}</td>
                            <td class="text-center">
                                @can('gopanel.activity.activity-logs.delete')
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

        <x-gopanel.modal
            name="activity-cleanup"
            :title="__('Logları təmizlə')"
            size="md"
            wireOpen="cleanupOpen"
        >
            <div class="mb-3">
                <label class="form-label">{{ __('Neçə gündən köhnə logları silmək?') }}</label>
                <input type="number" min="0" class="form-control" wire:model="cleanupDays">
                <small class="text-muted">{{ __('0 daxil etsəniz, bütün logları silər.') }}</small>
            </div>

            <x-slot:footer>
                <button type="button" class="btn btn-secondary" x-on:click="isOpen = false">
                    {{ __('Bağla') }}
                </button>
                <button type="button" class="btn btn-danger" wire:click="runCleanup"
                    wire:confirm="{{ __('Bu əməliyyatın geri qaytarılması yoxdur. Əminsiniz?') }}"
>
                    <span class="lw-not-loading"><i class="fas fa-broom me-1"></i> {{ __('Təmizlə') }}</span>
                    <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Silinir...') }}</span>
                </button>
            </x-slot:footer>
        </x-gopanel.modal>
    </div>
</div>
