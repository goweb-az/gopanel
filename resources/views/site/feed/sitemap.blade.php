<?= '<?xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
{{-- Sitemap indeksi: hər dilin öz `<urlset>` faylına keçid. XML prologu faylın
     ƏN ƏVVƏLİNDƏ olmalıdır - ondan əvvəl bir boşluq belə "XML declaration not at
     start of document" xətası verir. --}}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($sitemap_languages as $language)
    <sitemap>
        <loc>{{ route("site.{$language->code}.sitemap.single") }}</loc>
        <lastmod>{{ date('Y-m-d') }}</lastmod>
    </sitemap>
@endforeach
</sitemapindex>
