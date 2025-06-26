
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
<script src="{{asset("/assets/gopanel/libs/bootstrap-switch-button/bootstrap-switch-button.min.js")}}"></script>
<script src="/assets/gopanel/js/app.js"></script>
<script src="/assets/gopanel/js/main.js?={{time()}}"></script>
<script src="/assets/gopanel/js/functions.js?={{time()}}"></script>
<script src="/assets/gopanel/js/crud.js?={{time()}}"></script>
@stack('scripts')
@stack('js_stack')