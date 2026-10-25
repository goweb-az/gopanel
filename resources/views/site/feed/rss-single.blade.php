<?= '<?xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
{{-- RSS 2.0. Siyahını `App\Services\Seo\RssService` qurur. Başlıq və təsvir
     `CDATA` içindədir: redaktordan gələn mətndə `&`, `<` və dırnaq ola bilər. --}}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title><![CDATA[{{ $channel['title'] }}]]></title>
        <link>{{ $channel['link'] }}</link>
        <description><![CDATA[{{ $channel['description'] }}]]></description>
        <language>{{ $channel['language'] }}</language>
        <lastBuildDate>{{ $channel['lastBuild'] }}</lastBuildDate>
        <atom:link href="{{ $channel['self'] }}" rel="self" type="application/rss+xml" />
@foreach ($items as $item)
        <item>
            <title><![CDATA[{{ $item['title'] }}]]></title>
            <link>{{ $item['link'] }}</link>
            <guid isPermaLink="true">{{ $item['guid'] }}</guid>
@if (!empty($item['pubDate']))
            <pubDate>{{ $item['pubDate'] }}</pubDate>
@endif
@if (!empty($item['description']))
            <description><![CDATA[{{ $item['description'] }}]]></description>
@endif
        </item>
@endforeach
    </channel>
</rss>
