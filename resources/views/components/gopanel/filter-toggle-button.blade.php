@props([])

<button type="button" class="btn btn-outline-secondary" x-on:click="filterOpen = !filterOpen">
    <template x-if="filterOpen"><span><i class="fas fa-times me-1"></i> {{ __('Filteri bağla') }}</span></template>
    <template x-if="!filterOpen"><span><i class="fas fa-filter me-1"></i> {{ __('Filter') }}</span></template>
</button>
