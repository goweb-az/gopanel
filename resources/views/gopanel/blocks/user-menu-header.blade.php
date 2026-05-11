<div
    x-data="{ open: false }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
    class="dropdown d-inline-block position-relative"
>
    <button type="button"
            class="btn header-item waves-effect"
            x-on:click="open = !open"
            aria-haspopup="true"
            :aria-expanded="open">
        <img class="rounded-circle header-profile-user" src="{{ auth('gopanel')?->user()?->avatar_url }}"
            alt="Header Avatar" style="object-fit:cover;">
        <span class="d-none d-xl-inline-block ms-1">{{ auth('gopanel')?->user()?->full_name }}</span>
        <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
    </button>

    <div
        x-show="open"
        x-transition.opacity.duration.150ms
        x-cloak
        class="dropdown-menu dropdown-menu-end show"
        style="position:absolute; right:0; top:100%; margin-top:4px;"
    >
        <a class="dropdown-item" href="/" target="_blank" x-on:click="open = false">
            <i class="bx bx-desktop font-size-16 align-middle me-1"></i> <span>{{ __('Sayta keç') }}</span>
        </a>
        <a wire:navigate class="dropdown-item" href="{{ route('gopanel.profile.index') }}" x-on:click="open = false">
            <i class="bx bx-user font-size-16 align-middle me-1"></i> <span>{{ __('Profil məlumatlarım') }}</span>
        </a>
        <a wire:navigate class="dropdown-item" href="{{ route('gopanel.profile.change-password.index') }}" x-on:click="open = false">
            <i class="bx bx-lock-open font-size-16 align-middle me-1"></i> <span>{{ __('Şifrəni dəyiş') }}</span>
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-danger" href="{{ route('gopanel.auth.logout') }}">
            <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> <span>{{ __('Çıxış') }}</span>
        </a>
    </div>
</div>
