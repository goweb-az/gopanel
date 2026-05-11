@props(['name'])

<div x-show="tab === @js($name)" x-cloak>
    {{ $slot }}
</div>
