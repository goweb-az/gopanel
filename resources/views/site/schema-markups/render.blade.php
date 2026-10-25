{{--
    JSON-LD qovşaqlarının çapı.

    Məzmun `App\Services\Seo\SchemaService`-də massiv kimi qurulur və burada
    `json_encode` ilə çap olunur - başlıqdakı dırnaq və ya sətir keçidi markup-ı
    sındıra bilmir. `JSON_HEX_TAG` mətndəki `</script>`-i `<` kimi yazır,
    yəni redaktor məzmunu skript blokunu bağlaya bilmir.

    Bu `<script>` JS deyil, data blokudur (application/ld+json) - blade-də
    `<script>` qadağasının istisnasıdır.
--}}
@if (!empty($schema_graph))
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => array_values($schema_graph)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endif
