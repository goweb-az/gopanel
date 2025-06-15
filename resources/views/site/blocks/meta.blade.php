    <meta name="title" content="{{ $meta_title }}" />
    <meta name="description" content="{{ $meta_description }}" />
    <meta name="keywords" content="{{ $meta_keywords }}" />
    <meta property="og:locale" content="{{ $currentLocale ?? 'az_AZ' }}" />
    <meta property="og:image" content="{{ $meta_image }}" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $meta_title }}" />
    <meta property="og:site_name" content="{{ $meta_title }}" />
    <meta property="og:description" content="{{ $meta_description }}" />
    <meta property="og:url" content="{{ request()->url() }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:description" content="{{ $meta_description }}" />
    <meta name="twitter:title" content="{{ $meta_title }}" />
