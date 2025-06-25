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

        
        <!-- JAVASCRIPT -->
        @include('gopanel.assets.scripts')
    </body>
</html>
