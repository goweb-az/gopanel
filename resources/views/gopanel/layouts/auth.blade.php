<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <title>{{ $title ?? 'Gopanel' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Gopanel" name="description" />
    <meta content="Proweb" name="author" />
    <link rel="shortcut icon" href="/assets/gopanel/images/favicon.ico">
    @include('gopanel.assets.styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            {{ $slot }}
        </div>
    </div>

    @include('gopanel.assets.scripts')
    <x-gopanel.toast-bridge />
</body>
</html>
