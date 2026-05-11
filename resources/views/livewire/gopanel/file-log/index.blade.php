<?php

use App\Actions\Gopanel\Activity\CleanupFileLogsAction;
use App\Actions\Gopanel\Activity\DeleteFileLogAction;
use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Concerns\WithDatatable;
use App\Models\Activity\FileLog;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use AuthorizesGopanel, WithDatatable;

    public string $permissionDelete = 'gopanel.activity.file-logs.delete';

    #[Url(as: 'level', except: '')]
    public string $filterLevel = '';

    #[Url(as: 'channel', except: '')]
    public string $filterChannel = '';

    #[Url(as: 'from', except: '')]
    public string $filterDateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $filterDateTo = '';

    public bool $cleanupOpen = false;
    public ?int $cleanupDays = 30;

    public function updatedFilterLevel(): void { $this->resetPage(); }
    public function updatedFilterChannel(): void { $this->resetPage(); }
    public function updatedFilterDateFrom(): void { $this->resetPage(); }
    public function updatedFilterDateTo(): void { $this->resetPage(); }

    protected function datatableQuery(): Builder
    {
        $query = FileLog::query()->with(['admin:id,full_name', 'user:id,name,surname']);

        if ($this->filterLevel !== '') {
            $query->where('level', $this->filterLevel);
        }

        if ($this->filterChannel !== '') {
            $query->where('channel', $this->filterChannel);
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
            ['key' => 'id',         'label' => '#',           'sortable' => true,  'width' => '70px'],
            ['key' => 'level',      'label' => __('Səviyyə'), 'sortable' => true,  'width' => '110px'],
            ['key' => 'channel',    'label' => __('Kanal'),   'sortable' => true,  'width' => '120px'],
            ['key' => 'message',    'label' => __('Mesaj'),   'searchable' => true],
            ['key' => 'admin',      'label' => __('Admin'),   'width' => '150px'],
            ['key' => 'created_at', 'label' => __('Tarix'),   'sortable' => true,  'width' => '160px'],
            ['key' => 'actions',    'label' => __('Əməliyyat'), 'width' => '90px',  'align' => 'center'],
        ];
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $query->where(function (Builder $q) use ($term) {
            $q->where('message', 'LIKE', "%{$term}%")
              ->orWhere('channel', 'LIKE', "%{$term}%");
        });
    }

    public function delete(int $id): void
    {
        $this->authorize($this->permissionDelete);
        DeleteFileLogAction::run($id);
        $this->dispatch('notify', type: 'success', message: __('Silindi'));
    }

    public function runCleanup(): void
    {
        $this->authorize($this->permissionDelete);
        $deleted = CleanupFileLogsAction::run($this->cleanupDays);
        $this->cleanupOpen = false;
        $this->resetPage();
        $this->dispatch('notify', type: 'success', message: __(':count log silindi', ['count' => $deleted]));
    }

    public function getLevelOptionsProperty(): array
    {
        return FileLog::query()->select('level')->distinct()->whereNotNull('level')->pluck('level')->all();
    }

    public function getChannelOptionsProperty(): array
    {
        return FileLog::query()->select('channel')->distinct()->whereNotNull('channel')->pluck('channel')->all();
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">{{ __('Fayl logları') }}</h4>
                    <div class="page-title-right">
                        @can('gopanel.activity.file-logs.delete')
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
                        <label class="form-label small">{{ __('Səviyyə') }}</label>
                        <select class="form-select form-select-sm" wire:model.live="filterLevel">
                            <option value="">{{ __('Hamısı') }}</option>
                            @foreach ($this->levelOptions as $level)
                                <option value="{{ $level }}">{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">{{ __('Kanal') }}</label>
                        <select class="form-select form-select-sm" wire:model.live="filterChannel">
                            <option value="">{{ __('Hamısı') }}</option>
                            @foreach ($this->channelOptions as $ch)
                                <option value="{{ $ch }}">{{ $ch }}</option>
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
                        <tr wire:key="flog-{{ $record->id }}">
                            <td>{{ $record->id }}</td>
                            <td>{!! $record->level_badge !!}</td>
                            <td><code>{{ $record->channel }}</code></td>
                            <td>{!! $record->message_short !!}</td>
                            <td>{{ $record->admin_name ?? $record->user_name ?? '—' }}</td>
                            <td>{{ $record->created_at_formatted }}</td>
                            <td class="text-center">
                                @can('gopanel.activity.file-logs.delete')
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
            name="filelog-cleanup"
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
