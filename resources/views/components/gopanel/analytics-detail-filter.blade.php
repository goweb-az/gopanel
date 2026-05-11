@props([
    'fromField' => 'filterDateFrom',
    'toField'   => 'filterDateTo',
])

<div class="mb-3" x-show="filterOpen" x-cloak>
    <div class="card card-body">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">{{ __('Tarix (dan)') }}</label>
                <input type="date" class="form-control" wire:model.live="{{ $fromField }}">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">{{ __('Tarix (dək)') }}</label>
                <input type="date" class="form-control" wire:model.live="{{ $toField }}">
            </div>

            {{ $slot ?? '' }}
        </div>
    </div>
</div>
