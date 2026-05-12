@props([
    'icon' => 'bx bx-bar-chart-alt-2',
    'message' => null,
    'height' => '300px',
])

<div class="d-flex flex-column align-items-center justify-content-center text-muted text-center" style="min-height: {{ $height }};">
    <i class="{{ $icon }}" style="font-size: 48px; opacity: .25;"></i>
    <div class="mt-2 small">{{ $message ?? __('Hələ məlumat yoxdur') }}</div>
</div>
