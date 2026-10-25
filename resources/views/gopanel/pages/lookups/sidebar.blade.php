{{-- Bölmədaxili sidebar: $lookupMenu reyestrdən və icazədən hazır gəlir (BaseLookupController::lookupMenu). --}}
<div class="card lookup-sidebar-card">
    <div class="card-header bg-transparent border-bottom">
        <h5 class="mb-0 font-size-15">{{ config('gopanel.lookups.menu.title', 'Status və Tiplər') }}</h5>
    </div>
    <nav class="list-group list-group-flush lookup-sidebar" aria-label="Status və Tiplər">
        @foreach ($lookupMenu as $menu)
        <a href="{{ $menu['url'] }}" class="list-group-item list-group-item-action {{ $activeType === $menu['type'] ? 'active' : '' }}" @if($activeType === $menu['type']) aria-current="page" @endif>
            <i class="{{ $menu['icon'] }}"></i>
            <span>{{ $menu['title'] }}</span>
        </a>
        @endforeach
    </nav>
</div>
