<!doctype html>
<html lang="en">
    @include('gopanel.blocks.head')
    <body data-sidebar="dark" data-layout-mode="light">
    {{-- Restore vertical-collpsed BEFORE first paint to avoid sidebar flicker.
         Must run synchronously right after <body> opens so the class is on
         document.body before any layout-related CSS resolves. --}}
    <script>
        (function () {
            if (localStorage.getItem('gopanel:sidebar-collapsed') === '1') {
                document.body.classList.add('vertical-collpsed');
            }
        })();
    </script>

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

                @yield('content', $slot ?? '')
                <!-- End Page-content -->

                <x-gopanel.toast-bridge />

                @include('gopanel.blocks.footer')
            </div>
            <!-- end main content-->
        </div>
        <!-- END layout-wrapper -->

        
        <!-- JAVASCRIPT -->
        @include('gopanel.assets.scripts')
    </body>
</html>
