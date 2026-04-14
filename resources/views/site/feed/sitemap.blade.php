<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($languages as $language)
        <sitemap>
            <loc>{{route("site.{$language->code}.sitemap.single")}}</loc>
        </sitemap>
    @endforeach
</sitemapindex>