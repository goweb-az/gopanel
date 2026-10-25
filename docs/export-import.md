# Export (Excel / PDF / mail) və import köməkçiləri

Panel bölməsinin siyahısını **brendli Excel/PDF** kimi endirmək və ya **mail
qoşması** kimi göndərmək üçün hazır qat. İxrac **bir dəfə** tərif sinfi kimi
yazılır - endirmə, PDF, mail və queue-lu fayl eyni tərifdən qurulur, ona görə
formatlar arasında sütun fərqi yaranmır.

Mənbə: texnotech layihəsinin `Support/Export` qatı (ümumiləşdirilib, iş məntiqi
çıxarılıb) + gopanel-in mövcud `ExportContext` / `ExportResult` / `ExportHandler`
/ `ExportBranding` skeleti.

---

## 1. Qısa xəritə

| Sinif / fayl | Rolu |
|---|---|
| `App\Exports\Gopanel\ExportDefinition` | **Tərif** (abstract): `title()`, `headings()`, `rows()`, `summary()`, `appliedFilters()`, `numericColumns()`, `sections()` |
| `App\Support\Export\ExportSection` | Sənəddəki bir cədvəl (hesabatda bir neçə) |
| `App\Support\Export\ExcelExporter` | Tərif → markalı `.xlsx` (`download` / `raw` / `store`) - phpspreadsheet |
| `App\Support\Export\PdfExporter` | Tərif → PDF (`download` / `raw` / `store`) - **istəyə bağlı** Dompdf |
| `App\Support\Export\ExportRenderer` | Formatı seçir: `render()` (yaddaşda `ExportFile`), `download()`, `store()`, `availableFormats()` |
| `App\Support\Export\ExportFile` | Hazır fayl: ad, MIME, baytlar, `rowCount` |
| `App\Support\Export\ExportFilterLabels` | Xam süzgəclər → «Status: Aktiv · Başlanğıc: 01.10.2026» |
| `App\Support\Export\ExportRegistry` | Açar → tərif (`config/custom/export.php` → `definitions`) + icazə |
| `App\Support\Export\MailableExport` | Bir sətrə bağlı sənəd (qaimə) `uid`-dən özünü qurur |
| `App\Support\Export\ExportLinks` | Düymə linkləri / formatlar (blade-də `@php` olmasın deyə) |
| `App\Support\Export\ExportBranding` | Brend (başlıq, telefon, sayt, loqo) + **rənglər** (`color()`, `cssColor()`, `logoPath()`) |
| `App\Support\Export\PdfUnavailableException` | Dompdf yoxdursa aydın Azərbaycan dilində xəta |
| `App\Services\Export\ExportMailService` | Mailə göndərmə: icazə + növbəyə qoyma |
| `App\Services\Export\Handlers\DefinitionExportHandler` | `ExportHandler` ↔ `ExportDefinition` körpüsü (queue-lu fayl) |
| `App\Services\Export\Handlers\RegistryExportHandler` | Reyestr açarından hazır handler |
| `App\Jobs\Export\SendExportMailJob` | Faylı qurur, `MailService` ilə qoşma kimi göndərir |
| `App\Services\Mail\Templates\ExportMail` + `emails/export.blade.php` | Mail şablonu |
| `App\Http\Controllers\Gopanel\Export\ExportController` | Ortaq endpoint-lər: `gopanel.exports.download`, `gopanel.exports.mail` |
| `gopanel/exports/buttons.blade.php` | Excel / PDF / «Mailə göndər» düymə qrupu |
| `gopanel/exports/pdf.blade.php` | Ortaq PDF şablonu |
| `public/assets/gopanel/js/modules/export.js` + `css/export.css` | Süzgəci linkə qoşma + mail pəncərəsi (partial özü qoşur) |
| `App\Support\Import\ImportReport` | Import sayğacları + `report.json` forması |
| `App\Support\Import\FailureBucket` | Uğursuz sətirlər (səbəb + mərhələ), JSON round-trip, `--retry-failed` |

---

## 2. Yeni ixrac - 4 addım

### 2.1 Tərif sinfi

`app/Exports/Gopanel/<Domen>/<Ad>Export.php`. Data **Query** sinfindən gəlir
(datatable ilə eyni süzgəc açarları):

```php
namespace App\Exports\Gopanel\Site;

use App\Exports\Gopanel\ExportDefinition;
use App\Queries\Gopanel\Site\BlogQuery;

final class BlogExport extends ExportDefinition
{
    public function __construct(private readonly array $filters = []) {}

    public function title(): string { return 'Bloqlar'; }

    public function headings(): array { return ['ID', 'Başlıq', 'Status', 'Tarix']; }

    public function numericColumns(): array { return [0]; }

    public function rows(): iterable
    {
        // generator + lazy() - 20 min sətirdə də yaddaş sabit qalır
        foreach ((new BlogQuery($this->filters))->lazy() as $blog) {
            yield [$blog->id, $blog->title, $blog->is_active ? 'Aktiv' : 'Passiv', $blog->created_at?->format('d.m.Y')];
        }
    }

    public function appliedFilters(): array
    {
        return $this->describeFilters($this->filters, [], [
            'is_active' => ['1' => 'Aktiv', '0' => 'Passiv'],
        ]);
    }
}
```

Hesabat kimi bir neçə cədvəlli sənəd üçün `sections()` üstələnir:

```php
public function sections(): array
{
    return [
        new ExportSection('Günlük', ['Tarix', 'Say'], $this->daily(), [1]),
        new ExportSection('Status üzrə', ['Status', 'Say'], $this->byStatus(), [1]),
    ];
}
```

### 2.2 Reyestr bəndi

`config/custom/export.php` → `definitions`:

```php
'site.blogs' => [
    'label'      => 'Bloqlar',
    'permission' => 'gopanel.blogs.export',
    'export'     => \App\Exports\Gopanel\Site\BlogExport::class,
    // 'mail' => false,   // mailə göndərmək qadağandırsa
],
```

### 2.3 İcazə

`config/gopanel/permission_list.php`-ə `gopanel.blogs.export` sətri + `php artisan db:seed --class=PermissionSeeder`.
Endirmə də, mail də **eyni** icazəni yoxlayır (FormRequest / `ExportMailService`).

### 2.4 Düymə

Siyahı səhifəsinin blade-ində:

```blade
@can('gopanel.blogs.export')
    @include('gopanel.exports.buttons', ['key' => 'site.blogs', 'filters' => '#filterPanel'])
@endcan
```

`filters` - süzgəc konteynerinin seçicisi. Klik anında oradakı `name` və ya
`data-export-param="status"` atributu olan sahələr linkə qoşulur - fayl ekranda
görünəni verir. Tək sənəd üçün `['params' => ['uid' => $item->uid]]` ötürülür.

Yeni route, controller və JS **yazılmır** - partial ortaq endpoint-ə gedir və
JS/CSS-i özü `@push` edir.

---

## 3. İşlək nümunə - fəaliyyət jurnalı

| Hissə | Fayl |
|---|---|
| Tərif | `app/Exports/Gopanel/Activity/ActivityLogExport.php` |
| Query | `app/Queries/Gopanel/Activity/ActivityLogQuery.php` (açarlar `ActivityLogDatatable` ilə eyni) |
| Reyestr | `activity.activity-logs` |
| İcazə | `gopanel.activity.activity-logs.export` |

Birbaşa link:

```php
route('gopanel.exports.download', ['key' => 'activity.activity-logs', 'format' => 'excel', 'event' => 'deleted']);
```

Siyahı səhifəsinə düymə qoymaq üçün `activity_logs/index.blade.php`-də:
`@include('gopanel.exports.buttons', ['key' => 'activity.activity-logs', 'filters' => '#filterPanel'])`
və filtr sahələrinə `data-export-param="log_name"`, `"event"`, `"causer_id"`,
`"date_from"`, `"date_to"` atributları əlavə edilir (sahələrin `name`-i yoxdur).

---

## 4. PDF - istəyə bağlıdır

Starter Dompdf-u **tələb etmir**. Paket yoxdursa:

- `ExportRenderer::availableFormats()` → `['excel']`, düymələrdə PDF bəndi görünmür;
- PDF linki birbaşa açılsa `PdfUnavailableException` - controller əvvəlki səhifəyə
  Azərbaycan dilində mesajla qayıdır (AJAX-a `422 {status:'error'}`);
- mail pəncərəsində PDF kartı yoxdur, servis PDF sorğusunu növbəyə qoymadan rədd edir.

Aktivləşdirmək:

```bash
composer require barryvdh/laravel-dompdf
```

Başqa heç nə dəyişmir. Qeydlər:

- şrift DejaVu Sans-dır; orada `₼` yoxdur - `PdfExporter` onu avtomatik `AZN`-ə çevirir;
- loqo URL yox, **fayl yolu** ilə verilir (`ExportBranding::logoPath()`), şəbəkə sorğusu olmasın;
- 7-dən çox sütun → albom səhifə (`pdf.landscape_after_columns`);
- xüsusi tərtibatlı sənəd üçün tərifdə `pdfView()` + `pdfData()` üstələnir.

---

## 5. Mail ilə göndərmə

Axın: `buttons` → `export.js` pəncərəsi → `POST gopanel/exports/mail` →
`ExportMailRequest` → `ExportMailService::queue()` (icazə burada, sorğu anında)
→ `SendExportMailJob` (yalnız skalyarlar) → `ExportRenderer::render()` →
`MailService::send()` + `ExportMail` (fayl **qoşma** kimi).

- Növbə: `EXPORT_MAIL_QUEUE_CONNECTION` / `EXPORT_MAIL_QUEUE`.
- Nəticə və xəta `export` jurnal kanalına yazılır (panel log-viewer-də görünür).
- Admin bildirişi starter-də yoxdur; bildiriş bölməsi qurulubsa
  ([gopanel-notifications-module.md](rules/gopanel-notifications-module.md))
  job-un `handle()` / `failed()` sonuna `dispatch()` əlavə edilir.
- Job-da icazə **yoxlanılmır** - queue-da `$admin->can()` işləmir.

---

## 6. Queue-lu böyük export (`ExportHandler` ilə)

`sync_limit`-dən böyük həcm brauzerdə gözlənilmir. Mövcud `ExportHandler`
müqaviləsi ilə birləşdirilib - handler ayrıca Excel qurmur:

```php
// config/custom/export.php
'handlers' => [
    'site.blogs' => \App\Services\Export\Handlers\RegistryExportHandler::class,
],

// job içində
$context = new ExportContext('site.blogs', $filters, config('custom.export.disk'), "exports/{$uid}.xlsx", 'xlsx');
$result  = app(config("custom.export.handlers.{$context->section}"))->handle($context);
// $result->title, $result->rowCount, $result->summary
```

Öz handler-i lazımdırsa `DefinitionExportHandler`-dən törəyib yalnız
`definition(ExportContext)` yazılır.

---

## 7. Import köməkçiləri

Import / backfill command-ləri (`app/Console/Commands/Migrate/`, idempotent,
`--dry-run`) üçün:

```php
$report = new ImportReport();
$failures = new FailureBucket();

foreach ($rows as $line => $row) {
    try {
        // ... yaz
        $report->increment('created');
    } catch (Throwable $e) {
        $failures->add($row['sku'] ?? $line, 'save', $e->getMessage(), ['line' => $line]);
        $report->increment('failed');
    }
}

Storage::put('imports/report.json', json_encode($report->toArray(), JSON_PRETTY_PRINT));
Storage::put('imports/failures.json', $failures->toJson());

// --retry-failed:
$ids = FailureBucket::fromJson(Storage::get('imports/failures.json'))->externalIds();
```

- Bir sətrin xətası importu dayandırmır, amma **səssizcə itmir**.
- `add()`-da kontekst əsas açarları (`external_id`, `stage`, `reason`, `failed_at`) üstələyə bilməz.
- `fromJson()` pozuq faylda istisna atır - boş bucket qaytarmaq uğursuzları itirmək olardı.

---

## 8. Konfiqurasiya (`config/custom/export.php`)

| Açar | Mənası |
|---|---|
| `branding.*` | Başlıq, telefon, sayt, loqo (nisbi `public/` yolu) |
| `colors.*` | `accent`, `header`, `header_text`, `summary`, `border`, `muted` (HEX, `#`-siz) |
| `max_rows` | Hər bölmə üçün sətir limiti (default 20000) |
| `pdf.paper`, `pdf.landscape_after_columns` | PDF səhifəsi |
| `filter_labels` | Layihəyə xas süzgəc adları (`warehouse_id` => 'Anbar') |
| `definitions` | Açar → tərif reyestri |
| `handlers` | Bölmə → `ExportHandler` (queue-lu) |
| `mail.queue_connection`, `mail.queue` | Mail job-unun növbəsi |

Brend məlumatı bazadan (məs. `contact_info`) gəlməlidirsə `ExportBranding`
dəyişdirilmir - service provider-də `config(['custom.export.branding.phone' => ...])`
ilə üstələnir.

---

## 9. Nə köçürülmədi və niyə

| Mənbə | Qərar |
|---|---|
| texnotech `ExportRegistry`-nin `report` qolu (`ReportSection`) | Çıxarıldı - hesabat modulu layihəyə xasdır. Lazımdırsa `MailableExport` adapter-i yazılır. |
| texnotech `ExportBranding`-in `ContactInfo` / `SiteSetting` sorğuları | Çıxarıldı - starter-də brend config-dəndir; bazadan üstələmə §8-də. |
| texnotech admin bildirişi (`AdminNotificationService`) | Starter-də yoxdur - jurnal kanalı işlədilir (§5). |
| texnotech `ExportsData` controller trait-i | Ortaq `ExportController` + reyestr onu əvəz edir; modul controller-i öz route-unu istəyirsə `app(ExportRenderer::class)->download($export, $format)` bəsdir. |
| aquastores `XlsxExporter` (zip ilə əl ilə xlsx) | Əlavə edilmədi - phpspreadsheet starter-in birbaşa asılılığıdır; o sinifdə brend, stil, rəqəm tipi yoxdur (hər şey sətir), ikinci qurulma yolu formatlar arası fərq yaradardı. |

---

## 10. Testlər

`tests/Feature/Gopanel/ExportImportLayerTest.php` - bazasız: xlsx diskə yazılması
və məzmunu, sətir limiti, boş nəticə, süzgəc etiketləri, PDF-in zərif imtinası,
PDF şablonunun render-i, reyestr, handler körpüsü, mail servisi (icazə + növbə),
mail job-u (qoşma), düymə partial-ı, `ImportReport`, `FailureBucket` round-trip.
