# Gopanel

Laravel 10 / PHP 8.1 admin panel **starter**-i. Bu layihə tək bir məhsul deyil -
yeni layihələrin üzərində qurulduğu skeletdir (Proweb). Buraya yazılan hər şey
BÜTÜN gələcək layihələrdə görünəcək, ona görə layihəyə xas iş məntiqi (məsələn
davamiyyət, satış, elan) bura ƏLAVƏ EDİLMİR - yalnız ümumi, təkrar istifadə
oluna bilən qat saxlanılır.

## Kod yazmazdan ƏVVƏL oxu

`.claude/rules/` bağlayıcı iş qaydalarını saxlayır:

| Fayl | Nə üçün |
|---|---|
| [rules/01-umumi.md](rules/01-umumi.md) | Layerlər, qovluq yerləşdirməsi, blade-də məntiq yoxdur, permission adlandırması, test, geriyə uyğunluq |
| [rules/02-gopanel.md](rules/02-gopanel.md) | GoPanel (`gopanel.*`) - CRUD modulu yazma ardıcıllığı, datatable, sidebar, icazə |
| [rules/03-site.md](rules/03-site.md) | Sayt tərəfi (`web.*`) - dinamik route, SEO, tərcümə, menyu |
| [rules/04-api.md](rules/04-api.md) | API - cavab formatı, resource, autentifikasiya |

**Ziddiyyət olarsa sıralama:** istifadəçinin cari mesajı → `.claude/rules/` → bu fayl → ümumi vərdişlər.

## Layerlər

| Layer | Route | Controller | View |
|---|---|---|---|
| GoPanel (admin) | `routes/gopanel.php` (`gopanel.*`) | `app/Http/Controllers/Gopanel` | `resources/views/gopanel` |
| Sayt | `routes/web.php` | `app/Http/Controllers/Site` | `resources/views/site` |
| API | `routes/api.php` | `app/Http/Controllers/Api` | JSON Resource |

## Hazır ümumi qat (yenidən yazma - VAR)

Yeni layihədə bunlar hazırdır; təkrar yazmaq əvəzinə istifadə et.
Tam siyahı və nümunələr: [docs/shared-layer.md](../docs/shared-layer.md)

| Ehtiyac | Sinif |
|---|---|
| Modelə yazmaq (insert/update/delete) | `App\Repositories\BaseRepository` |
| Panel formasının saxlanması (fayl + tərcümə + meta) | `App\Services\Gopanel\Content\ContentSaveService` |
| Panel forması (icazə + validasiya + DTO) | `App\Http\Requests\Gopanel\GopanelFormRequest` |
| Form məlumatının typed daşıyıcısı | `App\DTOs\Gopanel\ContentPayload` + `FileField` |
| «Bu istifadəçi bunu edə bilər?» | `App\Policies\CrudPolicy` (`$module` elan et, bəsdir) |
| Çoxdilli sahə məcburidirsə | `App\Rules\TranslatedRequired` |
| Tək sətirli səhifə (Haqqımızda, Əlaqə...) | `App\Queries\Gopanel\Common\SingleRecordQuery` |
| Status/tip lookup cədvəli (CRUD + sürüklə-burax + tək default) | `App\Http\Controllers\Gopanel\Lookups\BaseLookupController` + `config/gopanel/lookups.php` (`php artisan gopanel:make-lookup`) |
| Route-da panel icazəsi (`gopanel.can:a,b`) | `App\Http\Middleware\Gopanel\GopanelPermissionMiddleware` |
| Sürüklə-burax sıra yazılışı (bir sorğu) | `App\Services\Gopanel\SortOrderService` |
| Excel/PDF ixrac (bir tərif, hər format, mailə göndər) | `App\Exports\Gopanel\ExportDefinition` + `App\Support\Export\ExportRenderer` + `gopanel.exports.buttons` ([bələdçi](../docs/export-import.md)) |
| Import sayğacları / uğursuz sətirlər (retry) | `App\Support\Import\{ImportReport,FailureBucket}` |
| Fayl URL-i (disk avtomatik / fallback) | `FileUploader::url($p, 'auto')`, `storageUrl()`, `*OrFallback()`, `file_url()` |
| Həssas əməliyyatın PIN təsdiqi | `App\Services\Gopanel\Admins\AdminPinService` + route-a `gopanel.pin` |
| Giriş limiti / e-poçt OTP | `App\Services\Auth\{LoginThrottle,OtpService}` |
| OTP / şifrə bərpası məktubu | `MailService::sendOtpEmail()` / `sendForgotPasswordEmail()` |
| IP allowlist / sayt bağlı (503) / dev domen | `ip.restrict`, `site.status` (`SiteStatusService`), `RestrictDevDomain` - config ilə, default sönük ([sənəd](../docs/security-and-auth.md)) |
| Qeydin müəllifi / məsulu («üzərinə götür») | `App\Traits\System\HasAuthors` (opt-in) + `RecordAuthorService` + `HandlesAssignment` |
| Domen hadisəsi → activity_log + fayl jurnalı | `App\Services\Activity\ActivityLogger` (+ `App\Enums\System\LogChannelEnum`) |
| Tipli key-value tənzimləmələr (config-də elan) | `App\Support\Settings\SettingDefinitions` + `App\Services\Settings\SettingsService` ([sənəd](../docs/authors-settings.md)) |
| Saytın UI sözlərini bazaya yazmaq (prod-da da, yalnız çatışmayanlar) | `php artisan translations:seed-site` ← `database/data/translations/website.json` ([qayda](../docs/translations.md)) |
| Tərcümələri mühitlər arası köçürmək (Excel, eyni format) | `App\Services\Gopanel\Translations\TranslationExcelService` (panel: Excel-ə çıxar / Excel-dən yüklə) |
| UI tərcümələrinin toplu yazılışı | `App\Services\Gopanel\Translations\TranslationWriteService` |
| Tərcümə dəyişikliyi jurnala | `TranslationHelper::fromInputWithChanges()` + `App\Support\Activity\TranslationChanges` |
| Statik xəritə şəkli / headless sayt ünvanı | `App\Support\Geo\StaticMap`, `App\Support\Url\FrontendUrl` |
| Cədvəl hüceyrəsi / sətir düymələri | `App\Datatable\Gopanel\Concerns\RendersRichCells` |
| Bayt → oxunaqlı ölçü | `App\Support\Files\ByteSize` |
| Serverin CPU/RAM/disk göstəriciləri | `App\Helpers\Gopanel\ServerMetricsHelper` |
| Keş (oxu/yaz/flush, `rememberFound`) | `App\Services\Cache\CacheService` |
| Keş açarı (dil + versiya) + qrup invalidasiyası | `App\Support\Cache\CacheKey` (+ `config/custom/cache.php`) |
| Model dəyişəndə keşin avtomatik təmizlənməsi | `App\Providers\CacheInvalidationServiceProvider` (`model_groups`-a bir sətir) |
| Email göndərişi | `App\Services\Mail\MailService` |
| SMS göndərişi | `App\Services\Sms\SmsService` (+ `config/custom/sms.php`) |
| Toplu (bulk) əməliyyatlar | `App\Services\Bulk\BulkActionService` (abstract) |
| Export (Excel/PDF) skeleti | `App\Services\Export\*` (+ `config/custom/export.php`) |
| Növbə monitorinqi | `App\Services\Queue\QueueMonitorService` |
| Fayl/CDN URL-i | `App\Support\Url\CdnUrl` |
| Tarix aralığı / müddət / ay adları | `App\Support\Date\{DayRange,Duration,MonthNames}` |
| Panel info kartı + trend | `App\Support\Gopanel\StatCard` |
| Dövr müqayisəsi (bu dövr / əvvəlki) | `App\Support\Gopanel\PeriodRange` |
| Kənar ikonu FA5-ə çevir | `App\Support\Gopanel\PanelIconMap` |
| Fayl jurnalı | `App\Services\Activity\LogService` |
| Cron command-in başlanğıc/son jurnalı | `App\Helpers\Common\Timer` (`cron` kanalı) |
| Fəaliyyət jurnalı | `App\Traits\Activity\LogsAdminActivity` + `config/custom/activity_messages.php` |
| Jurnalda «nə dəyişdi» mətni (`:changes`) | `App\Support\Activity\ActivityChangeFormatter` |
| API cavab zərfi `{success, message, data}` | `App\Http\Controllers\Api\ApiController` + `App\Http\Responses\Api\{Success,Error}Response` |
| İş qaydası istisnası (status + kod) | `App\Exceptions\Domain\DomainException` |
| API səhifələmə (`per_page` limiti) | `App\Support\Api\PaginationParams` (+ `config/api.php`) |
| Telefon / e-poçt normallaşdırma, maskalama | `App\Support\Contact\{PhoneNumber,EmailAddress}`, `App\Helpers\Common\StringHelpers` |
| Dilə görə tarix mətni | `App\Support\Date\LocalizedDateFormatter` |
| Siyahı filtrində tarix aralığı | `App\Support\Date\FilterDateRange` |
| Koordinatlar arası məsafə | `App\Support\Geo\Distance` |
| Link önizləmə robotu / bot tanıma | `App\Support\Http\PreviewBotDetector` |
| Panel linki (növbədən/emaildən) | `App\Support\Url\GopanelUrl` (+ `GOPANEL_URL`, `gopanel.host` middleware) |
| İnsan üçün artan nömrə (sifariş №) | `App\Traits\System\HasSerialNumber` |
| Model adı / «X tapılmadı» | `App\Traits\System\{HasLabel,HasNotFoundMessage}` |
| Modelin göstəriş accessor-ları | `App\Traits\Presentable\<Domen>\Has<Model>Presentation` ([qayda](../docs/rules/presentable-traits.md)) |
| Publik səhifələr (sitemap / RSS / `.md` ortaq mənbə) | `App\Services\Seo\PublicContentService` (+ `config/seo/content.php`) |
| Səhifənin `.md` variantı, `/markdown-index.md`, `/llms-full.txt` | `App\Services\Seo\Markdown\MarkdownPageService` + `markdown_url()` |
| HTML → Markdown | `App\Services\Seo\Markdown\HtmlToMarkdown` |
| Sitemap / RSS | `App\Services\Seo\SitemapService` / `RssService` |
| JSON-LD (schema.org) | `App\Services\Seo\SchemaService` (+ `config/seo/schema.php`, `setSchemaGraph()`) |
| robots/ai/ads/llms.txt | `App\Services\Seo\MachineFilesService` (panel: SEO → SEO və analitika) |
| Meta (title/description/og), robots | `App\Services\Site\Seo\MetaService` (+ `config/seo/meta.php`, `setPageMeta()` / `setPageRobots()`) |
| URL-in başqa dildəki variantı / yanlış dil slug-u → 301 | `App\Services\Site\Seo\{LanguageSwitcherService,SlugAliasResolver}` |

## Qovluq xəritəsi

```text
app/Contracts     interfeyslər (infrastruktur adapterləri)
app/DTOs          typed data transfer obyektləri
app/Datatable     server-side cədvəl sinifləri
app/Enums         sabit dəyər dəstləri (kod idarə edir)
app/Helpers       kiçik köməkçilər + global helpers.php
app/Http          Controller / Request / Middleware / Resource
app/Jobs          növbəyə düşən işlər
app/Models        Eloquent modelləri
app/Observers     model event dinləyiciləri
app/Policies      "bu istifadəçi bunu edə bilər?"
app/Queries       BÜTÜN böyük SELECT-lər (Gopanel / Site / Api)
app/Repositories  YALNIZ insert / update / delete
app/Rules         xüsusi validasiya qaydaları
app/Services      iş məntiqi, tranzaksiyalar, xarici API
app/Support       saf, vəziyyətsiz köməkçi primitivlər
app/Traits        model davranışı və təqdimat trait-ləri
config/custom     layihəyə xas config (activity_messages, cache, mail, sms, export...)
config/gopanel    panel konfiqurasiyası (sidebar, menyu, icazə siyahısı)
docs              modul sənədləri
```

## Vacib vərdişlər

- İstifadəçiyə görünən BÜTÜN mətn **Azərbaycan dilində**.
- Açar/token/parol hardcode edilmir → `config` + `.env`.
- Yeni ümumi sinif yazanda **docblock-da "niyə" yazılır**, təkcə "nə" yox.
- Layihəyə xas modul bu repoya əlavə edilmir - o, törəmə layihədə qalır.
- Bu bir starter olduğu üçün **geriyə uyğunluq** kritikdir: mövcud sinifin
  metod imzası dəyişmir, yenisi əlavə olunur.

## Sənədlər

- [docs/README.md](../docs/README.md) — bütün sənədlərin indeksi (buradan başla)
- [docs/shared-layer.md](../docs/shared-layer.md) — ümumi qat (Support/Services/Queries) kataloqu
- [docs/ai-guide.md](../docs/ai-guide.md) — praktik naxışlar və kod nümunələri (uzun)
- [docs/database-structure.md](../docs/database-structure.md) — cədvəllər, əlaqələr, trait-lər
- [docs/backup.md](../docs/backup.md) — panel backup bölməsi (baza + artımlı fayl arxivi)
- [docs/system-status.md](../docs/system-status.md) — sistem vəziyyəti monitoru (CPU/RAM/disk, növbə, cron)
- [docs/seo.md](../docs/seo.md) — SEO: meta, JSON-LD, hreflang; [markdown-pages.md](../docs/markdown-pages.md) (`.md` səhifələr), [seo-machine-files.md](../docs/seo-machine-files.md) (robots/llms/ai/ads.txt)
- [docs/rules/presentable-traits.md](../docs/rules/presentable-traits.md) — modelin göstəriş trait-ləri
- [docs/rules/README.md](../docs/rules/README.md) — böyük modul qurma spesifikasiyaları:
  bildirişlər, dashboard, istifadəçi idarəetməsi, kateqoriya ağacı, API, deployment

**Sıra:** bu fayl → `.claude/rules/` → `docs/shared-layer.md` → `docs/ai-guide.md`.
Ziddiyyətdə `.claude/rules/` üstündür.
