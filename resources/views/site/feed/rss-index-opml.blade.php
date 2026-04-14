<?='<?xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
<opml version="2.0">
  <head>
    <title>{{ config('app.name') }} – RSS Feeds</title>
  </head>
  <body>
    <outline text="Feeds">
      @foreach ($languages as $language)
        <outline type="rss"
                 text="{{ config('app.name') }} ({{ strtoupper($language->code) }})"
                 title="{{ config('app.name') }} ({{ strtoupper($language->code) }})"
                 xmlUrl="{{ route("site.{$language->code}.rss.single") }}"
                 htmlUrl="{{ url($language->code) }}" />
      @endforeach
    </outline>
  </body>
</opml>
