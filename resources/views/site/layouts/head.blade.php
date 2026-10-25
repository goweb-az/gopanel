<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $site_title ?? $meta_title ?? config('app.name', 'Gopanel') }}</title>
<!-- Favicon -->

{{-- Robots: TƏK mənbə `$page_robots` (default `config('seo.meta.robots')`,
     texniki səhifə controller-də `setPageRobots('noindex, follow')` çağırır).
     Əvvəl burada `robots` teqi üç dəfə (`index, follow, all` / `index, follow` /
     `all`) yazılırdı - eyni adlı teqlər axtarış sistemində BİRLƏŞDİRİLİR, ona
     görə `noindex` heç vaxt qüvvəyə minmirdi. --}}
<meta name="robots" content="{{ $page_robots }}">
<meta name="googlebot" content="{{ $page_robots }}">
<meta name="bingbot" content="{{ $page_robots }}">
<meta name="yandex" content="{{ $page_robots }}">
<meta name="google-extended" content="snippet,archive">
@isset($meta_title)
@include("site.layouts.meta")
@endisset
{{-- canonical + bütün hreflang alternate linkləri (AlternatesService) --}}
@include("site.inc.canonical")
<link rel="alternate" type="text/plain" href="{{ url('/llms.txt') }}" title="LLM Information">
{{-- Maşın üçün alternativ nüsxələr - ikisi də `ViewServiceProvider`-dən gəlir;
     mövcud deyilsə dəyər `null` olur və link çap olunmur. --}}
@if (!empty($markdown_url))
<link rel="alternate" type="text/markdown" href="{{ $markdown_url }}" title="Markdown">
@endif
@if (!empty($rss_feed_url))
<link rel="alternate" type="application/rss+xml" title="{{ config('app.name') }} RSS" href="{{ $rss_feed_url }}">
@endif
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
{{-- JSON-LD: controller `setSchemaGraph()` / `setSchema()` çağırmayıbsa
     `ViewServiceProvider` baza qrafını (Organization + WebSite) verir --}}
@if (!empty($schema_markup_source) && View::exists($schema_markup_source))
@include($schema_markup_source)
@endif
{!! $seoAnalytics?->head !!}
@include("site.assets.styles")
