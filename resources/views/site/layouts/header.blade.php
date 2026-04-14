
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ url($currentLocale) }}">
            @if($siteSettings?->logo_light)
                <img src="{{ asset($siteSettings->logo_light) }}" alt="Logo" height="36">
            @else
                {{ config('app.name', 'Gopanel') }}
            @endif
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="siteNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="{{ url($currentLocale) }}">{{ __('Əsas səhifə') }}</a>
                </li>
                @foreach($menus as $menu)
                    @if($menu->children->count())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">{{ $menu->title }}</a>
                            <ul class="dropdown-menu">
                                @foreach($menu->children as $child)
                                    <li><a class="dropdown-item" href="{{ $child->route }}">{{ $child->title }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ $menu->route }}">{{ $menu->title }}</a>
                        </li>
                    @endif
                @endforeach
            </ul>

            {{-- Dil seçimi --}}
            <ul class="navbar-nav me-3">
                @foreach($languages as $lang)
                    <li class="nav-item">
                        <a class="nav-link {{ $currentLocale === $lang->code ? 'active fw-bold' : '' }}" 
                           href="{{ $lang->switchLanguage() }}">{{ $lang->upper_code }}</a>
                    </li>
                @endforeach
            </ul>

            {{-- Sosial şəbəkələr --}}
            <div class="d-flex gap-2 align-items-center">
                @foreach($socials as $social)
                    @if($social->is_active)
                        <a href="{{ $social->url }}" class="text-white fs-5" 
                           @if($social->target_blank) target="_blank" @endif
                           title="{{ $social->name }}">
                            @switch($social->icon_type)
                                @case('image')
                                    <img src="{{ asset($social->icon) }}" alt="{{ $social->name }}" height="20">
                                    @break
                                @case('svg')
                                @case('font')
                                    {!! $social->icon !!}
                                    @break
                                @case('string')
                                    <span>{{ $social->icon }}</span>
                                    @break
                            @endswitch
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</nav>
