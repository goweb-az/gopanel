<!DOCTYPE html>
<html lang="{{$currentLocale}}">
<head>
    @include("site.layouts.head")
</head>
<body>
    @if ($siteSettings?->site_status == 0)
        @include('site.component.under_construction')
    @else
        {!!$seoAnalytics->body!!}
        <!-- Header -->
        @include("site.layouts.header")
        <!-- Header -->
        <main>
            @yield('content')
        </main>
        <!-- Footer -->
        @include("site.layouts.footer")
        <!-- Footer -->
        @include("site.inc.global-ui-elements")
    @endif
        @include("site.assets.scripts")

</body>
</html>