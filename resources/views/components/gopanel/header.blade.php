<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <a wire:navigate href="{{ url('gopanel') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="/assets/gopanel/images/gopanel-logo-icon.png" alt="" height="60">
                    </span>
                    <span class="logo-lg">
                        <img src="/assets/gopanel/images/gopanel-logo.png" alt="" height="40">
                    </span>
                </a>

                <a wire:navigate href="{{ url('gopanel') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="/assets/gopanel/images/gopanel-logo-icon.png" alt="" height="60">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ is_null($settings?->gopanel_logo) ? '/assets/gopanel/images/gopanel-logo.png' : url($settings->gopanel_logo) }}" alt="" height="40">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>

            <livewire:gopanel.cache-clear />

        </div>

        <div class="d-flex">

            <div class="dropdown d-inline-block d-lg-none ms-2">
                <button type="button" class="btn header-item noti-icon waves-effect" id="page-header-search-dropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="mdi mdi-magnify"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                     aria-labelledby="page-header-search-dropdown">

                    <form class="p-3">
                        <div class="form-group m-0">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search ..." aria-label="Recipient's username">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i class="mdi mdi-magnify"></i></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="dropdown d-none d-lg-inline-block ms-1">
                <button type="button" class="btn header-item noti-icon waves-effect" data-bs-toggle="fullscreen">
                    <i class="bx bx-fullscreen"></i>
                </button>
            </div>

            <livewire:gopanel.locale-switcher />

            @include('gopanel.blocks.user-menu-header')

        </div>
    </div>
</header>
