<!doctype html>
<html lang="en">
    @include('gopanel.blocks.head')
    <body data-sidebar="dark" data-layout-mode="light">
    <script>
        (function () {
            if (localStorage.getItem('gopanel:sidebar-collapsed') === '1') {
                document.body.classList.add('vertical-collpsed');
            }
        })();
    </script>

        <div id="layout-wrapper">

            <x-gopanel.header />

            <x-gopanel.sidebar />

            <div class="main-content">

                {{ $slot }}

                <x-gopanel.toast-bridge />

                <x-gopanel.footer />

            </div>
            
        </div>

        @include('gopanel.assets.scripts')
    </body>
</html>
