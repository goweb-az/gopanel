<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
	<url>
	  <loc><?=url($currentLocale) ?></loc>
	  <lastmod>{{date("Y-m-d")}}</lastmod>
	  <changefreq>daily</changefreq>
	  <priority>1.00</priority>
	</url>
    {{-- Menus --}}
    @if ($sitemap_menus->count())
    @foreach ($sitemap_menus as $sitemap_menu)
        @if ($sitemap_menu->route_name != 'home')
            <url>
                <loc>{{$sitemap_menu->route}}</loc>
                <lastmod>{{date("Y-m-d")}}</lastmod>
                <changefreq>daily</changefreq>
                <priority>1.00</priority>
            </url>
        @endif
    @endforeach
    @endif

    {{-- blogs  --}}
    @if ($blogs->count())
    @foreach ($blogs as $blog)
        @if (!is_null($blog->single_url))
            <url>
                <loc>{{$blog->single_url}}</loc>
                <lastmod>{{$blog->updated_at?->format('Y-m-d') ?? date("Y-m-d")}}</lastmod>
                <changefreq>weekly</changefreq>
                <priority>0.80</priority>
            </url>
        @endif
    @endforeach
    @endif
</urlset>