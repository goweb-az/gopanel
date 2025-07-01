<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- Sweet Alert-->
<link href="/assets/gopanel/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
<!-- Bootstrap Css -->
<link href="/assets/gopanel/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Bootstrap tags input -->
<link href="{{asset("/assets/gopanel/libs/bootstrap-tagsinput/bootstrap-tagsinput.css")}}" rel="stylesheet" type="text/css" />
<!-- Jqury tags input -->
<link href="{{asset("/assets/gopanel/libs/jquery.tagsinput/jquery.tagsinput.css")}}" rel="stylesheet" type="text/css" />
<!-- Select2 input -->
<link href="{{asset("/assets/gopanel/libs/select2/css/select2.min.css")}}" rel="stylesheet" type="text/css" />
<!-- Icons Css -->
<link href="/assets/gopanel/css/icons.min.css" rel="stylesheet" type="text/css" />
{{-- bootstrap-switch-button  --}}
<link rel="stylesheet" href="{{asset("/assets/gopanel/libs/bootstrap-switch-button/bootstrap-switch-button.min.css")}}" >
<!-- App Css-->
<link href="/assets/gopanel/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
<link href="{{asset('/assets/gopanel/css/custom.css?v=' . time())}}" id="app-style" rel="stylesheet" type="text/css" />
@stack('styles')
@stack('css_stack')