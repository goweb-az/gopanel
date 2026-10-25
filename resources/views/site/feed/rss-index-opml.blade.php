<?= '<?xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
{{-- OPML: RSS oxuyucusu bir faylla bütün dillərin feed-inə abunə ola bilir. --}}
<opml version="2.0">
    <head>
        <title>{{ config('app.name') }} - RSS</title>
        <dateModified>{{ now()->toRfc2822String() }}</dateModified>
    </head>
    <body>
        <outline text="{{ config('app.name') }}">
@foreach ($feed_languages as $language)
            <outline type="rss"
                     text="{{ config('app.name') }} ({{ strtoupper($language->code) }})"
                     title="{{ config('app.name') }} ({{ strtoupper($language->code) }})"
                     xmlUrl="{{ route("site.{$language->code}.rss.single") }}"
                     htmlUrl="{{ url($language->code) }}" />
@endforeach
        </outline>
    </body>
</opml>
