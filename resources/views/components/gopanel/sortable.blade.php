@props([
    'wireMethod' => 'reorder',
    'itemSelector' => '[data-id]',
    'handle' => null,
])

<div
    {{ $attributes }}
    wire:ignore.self
    x-data
    x-init="
        new window.Sortable($el, {
            animation: 150,
            {{ $handle ? "handle: '{$handle}'," : '' }}
            draggable: '{{ $itemSelector }}',
            onEnd: () => {
                const ids = Array.from($el.querySelectorAll('{{ $itemSelector }}'))
                    .map(el => el.dataset.id);
                $wire.call('{{ $wireMethod }}', ids);
            },
        });
    "
>
    {{ $slot }}
</div>
