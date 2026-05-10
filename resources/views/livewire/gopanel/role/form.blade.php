<?php

use App\Livewire\Concerns\AuthorizesGopanel;
use App\Livewire\Forms\RoleForm as RecordForm;
use App\Models\Gopanel\CustomPermission;
use App\Models\Gopanel\CustomRole as RecordModel;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    use AuthorizesGopanel;

    public RecordForm $form;

    public ?int $recordId = null;

    public string $search = '';

    public string $permissionCreate = 'gopanel.admins.roles.add';
    public string $permissionEdit   = 'gopanel.admins.roles.edit';
    public string $indexRoute       = 'gopanel.admins.roles.index';

    public function mount(): void
    {
        $record = $this->recordId ? RecordModel::with('permissions')->findOrFail($this->recordId) : new RecordModel();

        $this->authorize($this->recordId ? $this->permissionEdit : $this->permissionCreate);

        $this->form->setItem($record);
    }

    #[Computed]
    public function permissionGroups()
    {
        $all = CustomPermission::orderBy('group')->orderBy('name')->get();

        if ($this->search !== '') {
            $term = mb_strtolower($this->search);
            $all = $all->filter(function ($p) use ($term) {
                return str_contains(mb_strtolower($p->name), $term)
                    || str_contains(mb_strtolower($p->title ?? ''), $term)
                    || str_contains(mb_strtolower($p->group ?? ''), $term);
            });
        }

        return $all->groupBy('group');
    }

    public function toggleGroup(string $group, bool $checked): void
    {
        $names = CustomPermission::where('group', $group)->pluck('name')->all();

        if ($checked) {
            $this->form->permissions = array_values(array_unique(array_merge($this->form->permissions, $names)));
        } else {
            $this->form->permissions = array_values(array_diff($this->form->permissions, $names));
        }
    }

    public function toggleAll(bool $checked): void
    {
        if ($checked) {
            $this->form->permissions = CustomPermission::pluck('name')->all();
        } else {
            $this->form->permissions = [];
        }
    }

    public function save(): void
    {
        $this->authorize($this->form->form['id'] ? $this->permissionEdit : $this->permissionCreate);

        $this->form->validate();

        DB::transaction(fn () => $this->form->save());

        $this->dispatch('notify', type: 'success', message: __('Yadda saxlanıldı'));

        $this->redirectRoute($this->indexRoute, navigate: true);
    }
}; ?>

<div>
    <form wire:submit.prevent="save">
        <div class="row g-3 mb-4">
            <div class="col-lg-6 col-md-6">
                <label class="form-label fw-semibold">{{ __('Vəzifə adı') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-lg" wire:model="form.form.name" placeholder="{{ __('Mis: Menecer') }}">
                @error('form.form.name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>

            <div class="col-lg-6 col-md-6">
                <label class="form-label fw-semibold">{{ __('Guard') }}</label>
                <select class="form-select form-select-lg" wire:model="form.form.guard_name">
                    @foreach (config('auth.guards') as $key => $guard)
                        <option value="{{ $key }}">{{ $guard['name'] ?? ucfirst($key) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card bg-light border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch"
                            x-on:change="$wire.toggleAll($event.target.checked)"
                            style="width:2.5em; height:1.25em;">
                        <label class="form-check-label fw-bold ms-1">
                            {{ __('Bütün icazələri seç / ləğv et') }}
                        </label>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill">{{ count($form->permissions) }}</span>
                        <span class="text-muted small">{{ __('seçilib') }}</span>
                        <input type="search" class="form-control form-control-sm" style="max-width: 220px"
                            placeholder="{{ __('İcazə axtar...') }}" wire:model.live.debounce.300ms="search">
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            @foreach ($this->permissionGroups as $group => $items)
                @php
                    $groupNames = $items->pluck('name')->all();
                    $checkedInGroup = count(array_intersect($groupNames, $form->permissions));
                    $allChecked = $checkedInGroup === count($groupNames);
                @endphp
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12" wire:key="grp-{{ $group }}">
                    <div class="card border shadow-sm h-100">
                        <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-pill bg-primary">
                                    {{ $checkedInGroup }}/{{ count($groupNames) }}
                                </span>
                                <h6 class="mb-0 fw-semibold">{{ ucfirst($group) }}</h6>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input type="checkbox" class="form-check-input" role="switch"
                                    @checked($allChecked)
                                    x-on:change="$wire.toggleGroup('{{ $group }}', $event.target.checked)"
                                    style="width:2em; height:1em;">
                            </div>
                        </div>
                        <div class="card-body py-2">
                            @foreach ($items as $permission)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                        id="perm-{{ $permission->id }}"
                                        value="{{ $permission->name }}"
                                        wire:model="form.permissions">
                                    <label class="form-check-label" for="perm-{{ $permission->id }}">
                                        {{ $permission->title ?? $permission->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-end mt-4">
            <a href="{{ route($indexRoute) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> {{ __('Geri') }}
            </a>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
                <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
            </button>
        </div>
    </form>
</div>
