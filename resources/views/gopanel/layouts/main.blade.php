<!doctype html>
<html lang="en">
    @include('gopanel.blocks.head')
    <body data-sidebar="dark" data-layout-mode="light">

    <!-- <body data-layout="horizontal" data-topbar="dark"> -->
        
        <!-- Begin page -->
        <div id="layout-wrapper">

            @include('gopanel.blocks.header')
            
            <!-- ========== Left Sidebar Start ========== -->
            @include('gopanel.blocks.sidebar')
            <!-- Left Sidebar End -->

            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->
            <div class="main-content">

                @yield('content')
                <!-- End Page-content -->

                @include('gopanel.blocks.footer')
            </div>
            <!-- end main content-->
        </div>
        <!-- END layout-wrapper -->

        
        {{-- PIN təsdiqi modalı - yalnız ADMIN_PIN_ENABLED=true olanda --}}
        @if (config('custom.security.admin_pin.enabled'))
            @include('gopanel.blocks.pin-confirm')
        @endif

        <!-- JAVASCRIPT -->
        @include('gopanel.assets.scripts')
    </body>
</html>
