<div class="dropdown d-inline-block">
    <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown"
    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <img class="rounded-circle header-profile-user" src="{{auth('gopanel')?->user()?->avatar_url}}"
            alt="Header Avatar" style="object-fit:cover;">
        <span class="d-none d-xl-inline-block ms-1" key="t-henry">{{auth("gopanel")?->user()?->full_name}}</span>
        <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-end">
        <!-- item-->
        <a class="dropdown-item" href="/" target="_blank">
            <i class="bx bx-desktop font-size-16 align-middle me-1"></i> <span>Sayta keç</span>
        </a>
        <a class="dropdown-item" href="{{route('gopanel.profile.index')}}">
            <i class="bx bx-user font-size-16 align-middle me-1"></i> <span>Profil məlumatlarım</span>
        </a>
        <a class="dropdown-item" href="{{route('gopanel.profile.change-password.index')}}">
            <i class="bx bx-lock-open font-size-16 align-middle me-1"></i> <span>Şifrəni dəyiş</span>
        </a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item text-danger" href="{{route("gopanel.auth.logout")}}">
            <i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> <span key="t-logout">Çıxış</span>
        </a>
    </div>
</div>