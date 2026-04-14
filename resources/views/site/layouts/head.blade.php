<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $site_title ?? $siteSettings?->meta()?->first()?->title ?? 'Gopanel' }}</title>
<!-- Favicon -->


<!-- Ümumi icazə (hamısı) -->
<meta name="robots" content="index, follow, all">
<!-- Google -->
<meta name="googlebot" content="index, follow, all">
<!-- Bing / Yahoo -->
<meta name="bingbot" content="index, follow, all">
<!-- Yandex -->
<meta name="yandex" content="index, follow, all">
<!-- DuckDuckGo -->
<meta name="duckduckbot" content="index, follow, all">
<!-- Baidu -->
<meta name="baiduspider" content="index, follow, all">
<!-- Applebot -->
<meta name="applebot" content="index, follow, all">
<meta name="robots" content="index, follow">
<meta name="robots" content="all">
<meta name="google-extended" content="snippet,archive">
@if (isset($meta_title) && isset($meta_description) && isset($meta_keywords))
@include("site.layouts.meta")
@endif
@include("site.inc.canonical")
<link rel="alternate" type="text/plain" href="{{ url('/llms.txt') }}" title="LLM Information">
<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
@if (isset($schema_markup_source) && $schema_markup_source && View::exists($schema_markup_source))
@include($schema_markup_source)
@endif
{!!$seoAnalytics->head!!}
@include("site.assets.styles")

