{{-- Dəyərlər `MetaService`-dən, `og:locale` siyahısı `ViewServiceProvider`-dən hazır gəlir. --}}
<meta name="title" content="{{ $meta_title }}">
<meta name="description" content="{{ $meta_description ?? '' }}">
@if (!empty($meta_keywords))
<meta name="keywords" content="{{ $meta_keywords }}">
@endif
{{-- `og:locale` dil KODU deyil, `az_AZ` formatını istəyir (config/seo/meta.php) --}}
<meta property="og:locale" content="{{ $og_locale ?? 'az_AZ' }}">
@foreach (($og_locale_alternates ?? []) as $ogAlternate)
<meta property="og:locale:alternate" content="{{ $ogAlternate }}">
@endforeach
@if (!empty($meta_image))
<meta property="og:image" content="{{ $meta_image }}">
@endif
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $meta_title }}">
{{-- Əvvəl burada `$meta_title` yazılırdı - paylaşımda saytın adı əvəzinə
     səhifənin başlığı görünürdü. Ad JSON-LD `Organization` ilə eyni mənbədəndir. --}}
<meta property="og:site_name" content="{{ $og_site_name ?? config('app.name') }}">
<meta property="og:description" content="{{ $meta_description ?? '' }}">
<meta property="og:url" content="{{ $canonical ?? request()->url() }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $meta_title }}">
<meta name="twitter:description" content="{{ $meta_description ?? '' }}">
@if (!empty($meta_image))
<meta name="twitter:image" content="{{ $meta_image }}">
@endif
