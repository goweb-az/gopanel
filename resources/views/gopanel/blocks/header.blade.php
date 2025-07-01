<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <a href="{{url("gopanel")}}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="/assets/gopanel/images/gopanel-logo-icon.png" alt="" height="70">
                    </span>
                    <span class="logo-lg">
                        <img src="/assets/gopanel/images/gopanel-logo.png" alt="" height="50">
                    </span>
                </a>

                <a href="{{url("gopanel")}}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="/assets/gopanel/images/gopanel-logo-icon.png" alt="" height="70">
                    </span>
                    <span class="logo-lg">
                        <img src="{{is_null($settings->gopanel_logo) ? '/assets/gopanel/images/gopanel-logo.png' : url($settings->gopanel_logo)}}" alt="" height="50">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>

            <!-- App Search-->
            {{-- <form class="app-search d-none d-lg-block">
                <div class="position-relative">
                    <input type="text" class="form-control" placeholder="Search...">
                    <span class="bx bx-search-alt"></span>
                </div>
            </form> --}}

            <div class="clear-cache-div">
                <div class="btn-group">
                    <button type="button" class="btn btn-danger waves-effect waves-light clear-cache-btn" data-url="{{route("gopanel.general.clear.cache", ['type' => 'basic'])}}">
                       <i class="fas fa-recycle"></i>  Keşi Təmizlə
                    </button>
                    <button type="button" class="btn btn-danger dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="mdi mdi-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item clear-cache-btn" href="javascript:void(0)"
                        data-url="{{ route('gopanel.general.clear.cache', ['type' => 'route']) }}">
                            <i class="fas fa-project-diagram me-2"></i> Route Cache Təmizlə
                        </a>

                        <a class="dropdown-item clear-cache-btn" href="javascript:void(0)"
                        data-url="{{ route('gopanel.general.clear.cache', ['type' => 'config']) }}">
                            <i class="fas fa-cogs me-2"></i> Config Cache Təmizlə
                        </a>

                        <a class="dropdown-item clear-cache-btn" href="javascript:void(0)"
                        data-url="{{ route('gopanel.general.clear.cache', ['type' => 'view']) }}">
                            <i class="fas fa-eye me-2"></i> View Cache Təmizlə
                        </a>

                        <a class="dropdown-item clear-cache-btn" href="javascript:void(0)"
                        data-url="{{ route('gopanel.general.clear.cache', ['type' => 'all']) }}">
                            <i class="fas fa-broom me-2"></i> Hamısını Təmizlə
                        </a>
                    </div>
                </div>
            </div>

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

            {{-- @include("gopanel.blocks.notification-header") --}}

            @include("gopanel.blocks.user-menu-header")

        </div>
    </div>
</header>