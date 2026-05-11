
<script src="/assets/gopanel/libs/jquery/jquery.min.js"></script>
<script src="/assets/gopanel/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/assets/gopanel/libs/metismenu/metisMenu.min.js"></script>
<script src="/assets/gopanel/libs/simplebar/simplebar.min.js"></script>
<script src="/assets/gopanel/libs/node-waves/waves.min.js"></script>
<script src="{{asset("/assets/gopanel/libs/bootstrap-tagsinput/bootstrap-tagsinput.min.js")}}"></script>
<script src="{{asset("/assets/gopanel/libs/jquery.tagsinput/jquery.tagsinput.js")}}"></script>
<script src="{{asset("/assets/gopanel/libs/jquery-ui-dist/jquery-ui.min.js")}}"></script>
<script src="{{asset("assets/gopanel/libs/select2/js/select2.min.js")}}"></script>
<!-- Sweet Alerts js -->
<script src="/assets/gopanel/libs/sweetalert2/sweetalert2.min.js"></script>
<!-- Toastr js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="{{asset("/assets/gopanel/libs/bootstrap-switch-button/bootstrap-switch-button.min.js")}}"></script>
<script src="{{asset("/assets/gopanel/libs/magnific-popup/jquery.magnific-popup.min.js")}}"></script>
<script src="/assets/gopanel/js/app.js"></script>
<script src="/assets/gopanel/js/main.js?={{time()}}"></script>
<script src="/assets/gopanel/js/functions.js?={{time()}}"></script>
<script src="/assets/gopanel/js/crud.js?={{time()}}"></script>
@stack('scripts')
@stack('js_stack')
@livewireScripts

{{--
    Persist sidebar collapsed state across page reloads + wire:navigate.
    The pre-paint <script> in main layout sets it on first paint;
    here we wire the toggle button + re-apply on every Livewire navigation.
--}}
<script>
    (function () {
        var KEY = 'gopanel:sidebar-collapsed';

        function applyState() {
            var collapsed = localStorage.getItem(KEY) === '1';
            document.body.classList.toggle('vertical-collpsed', collapsed);
        }

        function bindToggle() {
            var btn = document.getElementById('vertical-menu-btn');
            if (!btn || btn.dataset.persistBound === '1') return;
            btn.dataset.persistBound = '1';
            btn.addEventListener('click', function () {
                // app.js already toggled the class; sync to storage on the next tick.
                setTimeout(function () {
                    var collapsed = document.body.classList.contains('vertical-collpsed');
                    localStorage.setItem(KEY, collapsed ? '1' : '0');
                }, 0);
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            applyState();
            bindToggle();
        });

        document.addEventListener('livewire:navigated', function () {
            applyState();
            bindToggle();
        });
    })();
</script>

{{--
    Native sidebar accordion. metisMenu's slideDown is way too sluggish for
    an admin panel, so we replace it with a single delegated click handler
    that just toggles .mm-show / .mm-active. CSS handles the visibility.
    Survives wire:navigate because the listener is on document.body.
--}}
<script>
    (function () {
        function disableMetisMenu() {
            if (!window.jQuery) return;
            try { jQuery('#side-menu').metisMenu('dispose'); } catch (e) { /* noop */ }
        }

        document.body.addEventListener('click', function (e) {
            var trigger = e.target.closest('#side-menu .has-arrow');
            if (!trigger) return;

            e.preventDefault();
            e.stopPropagation();

            var li = trigger.parentElement;
            var sub = trigger.nextElementSibling;
            if (!sub || !sub.classList.contains('sub-menu')) return;

            // Close sibling open menus at the same level for a clean accordion.
            var parentList = li.parentElement;
            if (parentList) {
                parentList.querySelectorAll(':scope > li.mm-active').forEach(function (other) {
                    if (other === li) return;
                    other.classList.remove('mm-active');
                    var otherSub = other.querySelector(':scope > .sub-menu');
                    if (otherSub) otherSub.classList.remove('mm-show');
                });
            }

            li.classList.toggle('mm-active');
            sub.classList.toggle('mm-show');
        });

        document.addEventListener('DOMContentLoaded', disableMetisMenu);
        document.addEventListener('livewire:navigated', disableMetisMenu);
    })();
</script>

{{--
    Bootstrap data-bs-* widgets (dropdown, tooltip, popover, tab, collapse,
    offcanvas) are bound on first page load by app.js but not after a
    wire:navigate swap. Re-instantiate them on every navigation so any
    new markup picks up its handlers.
--}}
<script>
    (function () {
        if (typeof bootstrap === 'undefined') return;

        var widgets = [
            ['[data-bs-toggle="dropdown"]', bootstrap.Dropdown],
            ['[data-bs-toggle="tooltip"]',  bootstrap.Tooltip],
            ['[data-bs-toggle="popover"]',  bootstrap.Popover],
            ['[data-bs-toggle="tab"]',      bootstrap.Tab],
            ['[data-bs-toggle="collapse"]', bootstrap.Collapse],
            ['.offcanvas',                  bootstrap.Offcanvas],
        ];

        function reinitBootstrap() {
            widgets.forEach(function (pair) {
                var selector = pair[0];
                var Cls = pair[1];
                if (!Cls) return;
                document.querySelectorAll(selector).forEach(function (el) {
                    var existing = Cls.getInstance(el);
                    if (existing) return; // already wired (initial paint)
                    new Cls(el);
                });
            });
        }

        document.addEventListener('livewire:navigated', reinitBootstrap);
    })();
</script>