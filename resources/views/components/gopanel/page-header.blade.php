@props([
    'title',
    'caption' => null,
    'createUrl' => null,
    'createEvent' => null,
    'createLabel' => null,
    'createPermission' => null,
    'showCreateButton' => true,
])

@php
    $canShowCreate = $showCreateButton
        && ($createUrl || $createEvent)
        && (! $createPermission || auth()->user()?->can($createPermission));
@endphp

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0 font-size-18">{{ $title }}</h4>
                @if ($caption)
                    <p class="text-muted mb-0 mt-1">{{ $caption }}</p>
                @endif
                {{ $belowTitle ?? '' }}
            </div>
            <div class="page-title-right d-flex align-items-center gap-2">
                {{ $actions ?? '' }}

                @if ($canShowCreate)
                    @if ($createUrl)
                        <a wire:navigate class="btn btn-success" href="{{ $createUrl }}">
                            <i class="fas fa-plus"></i> {{ $createLabel ?? __('Əlavə et') }}
                        </a>
                    @else
                        <button type="button" class="btn btn-success" x-on:click="$dispatch('{{ $createEvent }}')">
                            <i class="fas fa-plus"></i> {{ $createLabel ?? __('Əlavə et') }}
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
