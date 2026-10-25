# Maşın faylları

| Ünvan | Mənbə | Redaktə | Boş olanda |
|---|---|---|---|
| `/robots.txt` | `seo_analytics.robots_txt` | SEO → SEO və analitika | default mətn (`MachineFilesService::defaultRobots()`, sitemap ünvanı ilə) |
| `/ai.txt` | `seo_analytics.ai_txt` | eyni səhifə | boş cavab |
| `/ads.txt` | `seo_analytics.ads_txt` | eyni səhifə | **404** (fayl yoxdur sayılır) |
| `/llms.txt` | `llms_txts.content` | SEO → LLMs.txt | boş cavab |
| `/llms-full.txt` | Markdown sənədləri (avtomatik) | `config/seo/markdown.php` | `enabled=false` → 404 |
| `/markdown-index.md` | avtomatik | - | - |
| `/sitemap.xml`, `/{dil}/sitemap.xml` | avtomatik | `config/seo/content.php` | - |
| `/rss-index.opml`, `/{dil}/rss.xml` | avtomatik | `config/seo/content.php` | - |

Hamısı `Site\Seo\TxtController` / `MarkdownController` / `SitemapController` /
`RssController`; mətn faylları `text/plain; charset=UTF-8`. Route-lar
`site.redirects` və `track.analytics`-dən çıxarılıb.

`seo_analytics` sətri olmayanda (təzə quraşdırma) controller artıq 500 vermir.

## Panel bloku

SEO → SEO və analitika səhifəsinin yuxarısında «Maşın faylları» cədvəli bütün
faylların hazır keçidlərini göstərir. Siyahı
`App\Services\Seo\MachineFilesService::links()`-dəndir - yeni maşın faylı
əlavə olunanda yalnız orada bir sətir yazılır. Yalnız qeydiyyatda olan
route-lar göstərilir.

Yadda saxlama `App\Services\Gopanel\Seo\SeoAnalyticsService` ilə gedir - o,
`seo_analytics` keşini də silir (əvvəl `/robots.txt` köhnə mətni verirdi).

## ads.txt sütunu

Miqrasiya: `2026_10_25_000001_add_ads_txt_to_seo_analytics_table` (nullable,
yalnız əlavə edir).

## Seeder

`Database\Seeders\SeoMachineFilesSeeder` robots/ai/llms başlanğıc mətnlərini
yazır; mətnlər `/llms.txt`, `/llms-full.txt`, `/markdown-index.md` və sitemap
ünvanlarını göstərir. **İdempotentdir**:

```text
sahə boşdur                      → yazılır
sahə cari default ilə eynidir    → toxunulmur
sahə əvvəlki seed default-udur   → yenilənir
paneldən yazılıb                 → ATLANIR (xəbərdarlıq)
```

Mövcud bazada köhnə default-ları yeniləmək:

```bash
php artisan db:seed --class=SeoMachineFilesSeeder
```

`SeoAnalyticsSeeder` və `LlmsTxtSeeder` təzə bazada default mətni bu seeder-dən
alır; `DatabaseSeeder` onu sonda çağırır.
