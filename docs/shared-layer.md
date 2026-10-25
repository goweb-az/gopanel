# Ümumi Qat (Support / Services / Queries)

Bu sənəd Gopanel-in **layihədən asılı olmayan** təkrar istifadə qatını təsvir edir.
Buradakı siniflərin heç biri konkret domenə (davamiyyət, satış, elan) bağlı
deyil - yeni layihədə olduğu kimi işləyir.

> Yeni sinif əlavə edərkən qərar ağacı: [rules/application-layering-support-query-traits-helpers-enums.md](rules/application-layering-support-query-traits-helpers-enums.md)

## Qovluq xəritəsi

```text
app/
├── Contracts/          interfeyslər (infrastruktur adapterləri)
│   ├── Export/         ExportHandler
│   └── Sms/            SmsProvider
├── DTOs/               typed data transfer obyektləri
│   └── Gopanel/        ContentPayload, FileField
├── Datatable/          server-side cədvəl sinifləri
│   └── Gopanel/Concerns/  RendersRichCells (hüceyrə qəlibləri)
├── Enums/              sabit dəyər dəstləri
│   └── Common/Status/                                                    [boş]
├── Exceptions/
│   └── Domain/         DomainException (iş qaydası istisnası → API zərfi)
├── Helpers/            kiçik köməkçilər + global helpers.php
│   ├── Common/         ActivityLogHelper, Timer, StringHelpers (maskalama)
│   └── Gopanel/        ServerMetricsHelper (CPU/RAM/disk/uptime)
├── Http/
│   ├── Controllers/Api/   ApiController (API bazası)
│   ├── Middleware/Gopanel/ GopanelHostMiddleware (gopanel.host)
│   ├── Requests/Gopanel/  GopanelFormRequest (bazа) + modul sorğuları
│   ├── Responses/Api/  SuccessResponse, ErrorResponse (API zərfi)
│   └── Resources/      API cavab formatı                                 [boş]
├── Jobs/               növbəyə düşən işlər
│   └── Backup/         BackupJob, CreateDatabaseBackup, CreateFilesBackup
├── Observers/          model event dinləyiciləri                         [boş]
├── Policies/           icazə qərarları
│   ├── BasePolicy      is_super qaydası
│   ├── CrudPolicy      index/add/edit/delete → icazə adları
│   └── Gopanel/        BackupPolicy
├── Queries/            BÜTÜN böyük SELECT-lər
│   ├── Api/                                                              [boş]
│   ├── Gopanel/        Common/SingleRecordQuery, Navigation, Site, Contact, Backup, System
│   └── Site/                                                             [boş]
├── Repositories/       YALNIZ insert / update / delete
│   ├── BaseRepository  fillable üzrə yazma, delete, forceDelete
│   └── Gopanel/        BackupRepository
├── Rules/              xüsusi validasiya qaydaları
│   └── TranslatedRequired
├── Services/           iş məntiqi, tranzaksiyalar, xarici API
│   ├── Activity/       LogService (fayl jurnalı)
│   ├── Bulk/           BulkActionService (abstract)
│   ├── Cache/          CacheService
│   ├── Export/         ExportContext, ExportResult
│   ├── Mail/           MailService + Templates/
│   ├── Queue/          QueueMonitorService
│   ├── Sms/            SmsService + Providers/
│   ├── Gopanel/        Content/, Backup/, System/, Navigation/, Settings/, Seo/
│   └── Site/           sayt tərəfi servisləri
├── Support/            saf, vəziyyətsiz primitivlər
│   ├── Activity/       ActivityChangeFormatter, ActivitySubject
│   ├── Api/            PaginationParams
│   ├── Cache/          CacheKey
│   ├── Contact/        PhoneNumber, EmailAddress
│   ├── Date/           DayRange, Duration, MonthNames, LocalizedDateFormatter, FilterDateRange
│   ├── Export/         ExportBranding
│   ├── Files/          ByteSize
│   ├── Geo/            Distance
│   ├── Gopanel/        StatCard, PeriodRange, PanelIconMap, GuideText
│   ├── Http/           PreviewBotDetector
│   ├── Security/                                                         [boş]
│   ├── Translation/    LoadedTranslation
│   └── Url/            CdnUrl, GopanelUrl
└── Traits/             model davranışı və təqdimat trait-ləri
    ├── Activity/       LogsAdminActivity
    ├── Content/        Translation, HasFiles, MetaData
    ├── Presentable/    <Domen>/Has<Model>Presentation                    [boş]
    ├── System/         AddUuid, Cacheable, HasArchive, HasRouteKey,
    │                   HasLabel, HasNotFoundMessage, HasSerialNumber
    └── Ui/             FormatsDate, UiElements
```

`[boş]` qovluqlar `.gitkeep` ilə repoda saxlanılır - struktur ilk gündən
görünsün, hər layihədə "haraya yazım?" sualı yaranmasın.

---

## 1. Keş - `CacheService` + `CacheKey`

İki ayrı məsuliyyət:

| Sinif | Məsuliyyət |
|---|---|
| `App\Services\Cache\CacheService` | **Saxlama** - oxu/yaz/sil, TTL, lock, tag |
| `App\Support\Cache\CacheKey` | **Açar** - açarın qurulması və qrup invalidasiyası |

### İstifadə

```php
use App\Services\Cache\CacheService;
use App\Support\Cache\CacheKey;

$menu = CacheService::remember(
    CacheKey::menu('web', 'header'),
    fn () => Menu::header()->get(),
);

// Qrupu təmizlə (versiyanı artırır)
CacheKey::flushGroup(CacheKey::GROUP_MENU);
```

### Niyə versiya, niyə tag deyil

`Cache::tags()` YALNIZ redis/memcached-də işləyir - file və database driver-ində
exception atır. Bir çox layihə file driver-i ilə başlayır.

Versiya üsulu hər driver-də işləyir: hər qrupun bir nömrəsi var və açar onu
daxil edir (`menu.v3.web.header.az`). Qrupu təmizləmək = nömrəni artırmaq;
köhnə açarlar avtomatik yararsız olur və TTL bitəndə özləri silinir. Beləliklə
dinamik açarlar (filtr, səhifə, dil) bir anda təmizlənir.

### Konfiqurasiya

`config/custom/cache.php`:

- `groups` - bütün qruplar (`flushAll()` bunları gəzir);
- `model_groups` - model → qrup xəritəsi.

### Avtomatik invalidasiya - `CacheInvalidationServiceProvider`

Model dəyişəndə keşi **heç kim əl ilə təmizləmir**:
`App\Providers\CacheInvalidationServiceProvider` (`config/app.php`-də qeydiyyatdadır)
`model_groups`-dakı hər modelin `saved` / `deleted` / `restored` event-inə bağlanır.

`FieldTranslation` event-i də bağlıdır: menyu adını redaktə edəndə `menus`
sətri `saved` OLMUR, yalnız `field_translations` yazılır - bu hissə olmasa
keş köhnə adı göstərərdi. Tərcümə sətri öz `model_type`-ı ilə sahibin qrupunu
təmizləyir.

Yeni modul keşlənirsə **iki addım**:

1. `config/custom/cache.php` → `model_groups`-a `\App\Models\X::class => 'qrup'`;
2. oxuyan yerdə açarı `CacheKey::<qrup>(...)` ilə qur:

```php
$items = CacheService::remember(
    CacheKey::page('services', $limit),        // page.v7.services.6.az
    fn () => $this->serviceQuery->active($limit),
);
```

Bir neçə qrupun datasını birləşdirən keş (məs. sitemap: menyu + bloq) açarına
hər iki qrupun versiyasını daxil edir - biri dəyişəndə açar dəyişsin:

```php
CacheKey::custom('seo', 'sitemap', ['menu' => CacheKey::menu('web', 'v'), 'page' => CacheKey::page('v')]);
```

### Açar qurucuları - tam siyahı

| Metod | Açar | Dil |
|---|---|---|
| `lookup($name)` | `lookup.v{n}.{name}.{az}` | ✓ |
| `menu($platform, $segment)` | `menu.v{n}.web.header.{az}` | ✓ |
| `category($platform, $filters)` | `category.v{n}.web.{hash}.{az}` | ✓ |
| `content($platform, $filters)` | `content.v{n}.web.{hash}.{az}` - bloq/xəbər siyahıları | ✓ |
| `translation($platform, $page)` | `translation.v{n}.web.home.{az}` | ✓ |
| `home($name)` / `page($name, $suffix)` | ana səhifə blokları / statik səhifələr | ✓ |
| `seo($name, $filters)` | meta, redirect, llms | ✓ |
| `settings($name)` | sayt ayarları | ✗ |
| `languages()` | `language.v{n}.active` - aktiv dillər | ✗ |
| `languageSwitch($scope, $token, $locale)` | dil dəyişdiricisinin slug tərcüməsi | hədəf dil |
| `api($name, $locale)` | xarici API datası | açıq verilir |
| `custom($group, $name, $filters, $localized)` | heç biri uyğun gəlməyəndə | seçimli |

**Bir neçə qrupdan data gələndə - `dependsOn()`:**

```php
// Bloq kartında kateqoriya adı da var - kateqoriya dəyişəndə də yenilənsin
$key = CacheKey::dependsOn(CacheKey::content('web', $filters), CacheKey::GROUP_CATEGORY);
// content.v3.web.<hash>.az+category.v7
```

**Bütün dilləri gəzəndə - `forLocale()`** (sitemap, export, növbə işi):

```php
foreach ($locales as $locale) {
    $key = CacheKey::forLocale($locale, fn () => CacheKey::menu('web', 'header'));   // ...header.en
}
```

`app()->setLocale()` dəyişdirilmir - yalnız açar həmin dilə bağlanır.

### `rememberFound()` - «tapılmadı» keşlənmir

```php
$page = CacheService::rememberFound(CacheKey::page('slug', $slug), fn () => $query->bySlug($slug));
```

`remember()` `null`-u da saxlayır - qeyd sonradan yaradılanda TTL bitənə qədər
yoxmuş kimi görünür. Slug/açar axtarışında `rememberFound()` işlədilir.

### Köhnə `Cacheable` trait-i

`Model::getCachedAll()` (`cache.{table}.all`) dil və versiya bilmir. Mövcud kod
işləməyə davam edir, amma **yeni** keşlər `CacheService` + `CacheKey` ilə yazılır.

TTL: `config/cache.php` → `default_ttl` (`.env` → `CACHE_DEFAULT_TTL`).

---

## 2. Email - `MailService`

```php
use App\Services\Mail\MailService;

(new MailService('Xoş gəldiniz'))
    ->enableQueue()
    ->sendBasicEmail($user->email, "Salam {$user->name}, qeydiyyatınız tamamlandı.");
```

- Ortaq brend datası (loqo, altlıq, əlaqə) `config/custom/mail.php` → `branding`.
- Şablonlar: `emails.basic` (qəlibli mətn) və `emails.html` (hazır HTML).
- Queue aktiv olanda mail `config/custom/mail.php` → `queue` ilə göstərilən
  növbəyə düşür - driver-in default növbəsinə yox.
  Ayrı worker: `php artisan queue:work --queue=email_queue`

**Niyə ayrı növbə:** SMTP yavaşdır. Mail default növbədə qalsa, arxadakı bütün
işlər (bildiriş, hesabat) onun arxasında gözləyir.

Yeni şablon: `app/Services/Mail/Templates/<Ad>Mail.php` (nümunə `BasicMail`)
+ `resources/views/emails/<ad>.blade.php`.

> Email blade-ləri **"blade-də stil yoxdur"** qaydasının istisnasıdır - email
> klientləri xarici CSS və flexbox dəstəkləmir, table layout + inline stil işlədilir.

---

## 3. SMS - `SmsService`

```php
use App\Services\Sms\SmsService;

app(SmsService::class)->send($phone, "Təsdiq kodu: {$code}", SmsService::TYPE_OTP);
```

- Provayder `config/custom/sms.php` → `provider` ilə seçilir.
- **Default `LogProvider`-dir**: heç nə göndərmir, yalnız `storage/logs/sms/`
  faylına yazır. Yeni layihə ilk gündən işləyir və heç kimə təsadüfi SMS getmir.
- Canlıda `.env` → `SMS_PROVIDER=App\Services\Sms\Providers\Lsim`.
- `SMS_ENABLED=false` → bütün göndərişlər `blocked` kimi jurnala düşür.
- Mətn provayderə getməzdən əvvəl latına çevrilir - əks halda operator mesajı
  Unicode sayır və 1 SMS 70 simvola düşür.

Bazaya da yazmaq istəyirsənsə (`sms_logs` cədvəli varsa):

```php
$sms->setLogger(fn (array $row) => SmsLog::create($row));
```

Yeni provayder: `App\Contracts\Sms\SmsProvider` implement edilir, uğursuzluqda
**exception atılır** (`false` qaytarmaq gizli xəta yaradır).

> Köhnə `App\Services\Sms\ProviderInterface` silinməyib - o, artıq
> `SmsProvider`-dən törəyir. Beləliklə starter üzərində qurulmuş layihələrdəki
> mövcud provayderlər olduğu kimi işləyir. Eyni yanaşma `ExportHandler` üçün də
> tətbiq olunub.

---

## 4. Toplu əməliyyatlar - `BulkActionService`

`app/Services/Bulk/BulkActionService.php` - abstract. Hər modul öz törəməsini yazır:
`actions()`, `abilityFor()`, `fetch()`, `apply()`, `label()`, `emptySelectionMessage()`.

Qaydalar (sinifin docblock-unda tam nümunə var):

- Toplu rejim **yeni məntiq yazmır** - tək sətir üçün işləyən eyni servisi çağırır.
  Əks halda iki rejim iki fərqli nəticə verir.
- Bir sətrin xətası qalanları dayandırmır; nəticə:
  `['done' => n, 'failed' => n, 'skipped' => n, 'errors' => [...]]`.
- Xəta siyahısı 5 fərqli sətirdən sonra kəsilir - mesaj oxunaqlı qalsın.

---

## 5. Export skeleti

| Sinif | Rolu |
|---|---|
| `App\Services\Export\ExportContext` | Dəyişməz kontekst: bölmə, filtrlər, disk, yol, format |
| `App\Services\Export\ExportResult` | Nəticə: başlıq, sətir sayı, xülasə |
| `App\Services\Export\Contracts\ExportHandler` | Bir export tipini hazırlayan strategiya |
| `App\Support\Export\ExportBranding` | PDF/Excel header-lərindəki brend məlumatı |

Yeni export tipi:

1. `ExportHandler` implement edən sinif → `app/Services/Export/Handlers/`;
2. `config/custom/export.php` → `handlers` xəritəsinə qeyd.

Export-u işlədən job/controller-ə toxunmaq lazım deyil.

`config/custom/export.php` → `sync_limit`: bu limitdən çox sətri olan export
sinxron yüklənmir, queue-ya düşür. **Niyə:** brauzer 30-60 saniyədən sonra
sorğunu kəsir; 50 min sətirlik Excel isə həmişə daha uzun çəkir.

### Hazır pipeline: Excel / PDF / mail

İxrac bir dəfə `App\Exports\Gopanel\ExportDefinition` törəməsi kimi yazılır
(`title`, `headings`, `rows` generator, `appliedFilters`, `summary`); Excel, PDF,
mail qoşması və queue-lu fayl eyni tərifdən qurulur - formatlar arasında sütun
fərqi yaranmır.

| Sinif | Rolu |
|---|---|
| `App\Support\Export\ExportRenderer` | `render()` / `download()` / `store()` / `availableFormats()` |
| `App\Support\Export\ExcelExporter` | Brendli xlsx (phpspreadsheet) |
| `App\Support\Export\PdfExporter` | PDF - **istəyə bağlı**: `composer require barryvdh/laravel-dompdf`; yoxdursa PDF düyməsi gizlənir |
| `App\Support\Export\ExportRegistry` | Açar → tərif + icazə (`config/custom/export.php` → `definitions`) |
| `App\Services\Export\ExportMailService` | Mailə göndərmə (job yalnız skalyar daşıyır) |
| `App\Services\Export\Handlers\DefinitionExportHandler` | Yuxarıdakı `ExportHandler` skeletini tərifə bağlayır |
| `App\Support\Export\ExportFilterLabels` | Süzgəclər → oxunaqlı sənəd başlığı |
| `App\Support\Import\{ImportReport, FailureBucket}` | Import sayğacları və uğursuz sətirlərin JSON round-trip-i (`--retry-failed`) |

Yeni ixrac: tərif sinfi → `definitions` bəndi → `.export` icazəsi →
`@include('gopanel.exports.buttons', ['key' => '...', 'filters' => '#filterPanel'])`.
Yeni route/controller/JS yazılmır. Nümunə: `ActivityLogExport`. Tam bələdçi:
[export-import.md](export-import.md).

---

## 6. Növbə monitorinqi - `QueueMonitorService`

```php
$snapshot = app(QueueMonitorService::class)->snapshot();
// ['queues' => [...], 'failed' => ['total' => n, 'by_queue' => [], 'recent' => []], 'time' => '14:32:05']
```

- İzlənən növbələr `config/custom/queue_monitor.php`-dədir.
- Redis və database driver-ləri dəstəklənir; hər növbə üçün
  **gözləyən / gecikmiş / işlənən** ayrıca göstərilir.
- `Queue::size()` bunların CƏMİni verir - burada ayrı-ayrı oxunur, çünki
  «5 iş gözləyir» ilə «5 iş ilişib qalıb» tamam fərqli hallardır.
- `clearFailedJobs()` və `retryAllFailedJobs()` panel düymələri üçündür;
  birincisi **geri qaytarılmır**.

---

## 7. Tarix köməkçiləri

### `App\Support\Date\DayRange`

```php
[$start, $end] = DayRange::of($date);
$query->whereBetween('created_at', [$start, $end]);
```

**Niyə `whereDate()` yox:** `whereDate('created_at', $gun)` sütunun üstünə
`DATE()` funksiyası qoyur və MySQL kompozit indeksdən istifadə **edə bilmir** -
bütün tarixçəni oxuyur. Aralıq forması indeksi işlədir. Real ölçmədə fərq
26 000 sətir skanı ilə 151 sətir skanı arasındadır.

### `App\Support\Date\Duration`

Dəqiqə ilə saxlanılan müddətin oxunaqlı görünüşü. Baza və hesablamalar
**həmişə tam dəqiqədə** qalır - bu yalnız göstəriş qatıdır.

```php
Duration::human(198);   // "3.3 saat"
Duration::precise(95);  // "1 saat 35 dəq"
Duration::clock(198);   // "03:18"
Duration::hours(198);   // 3.3   (Excel/qrafik üçün rəqəm)
```

### `App\Support\Date\MonthNames`

Azərbaycanca ay və həftə günü adlarının vahid mənbəyi - siyahı bir neçə
bölmədə (hesabat, təqvim, export) lazım olur, ona görə TƏK yerdə saxlanılır.

```php
MonthNames::labelForPeriod('2026-07');  // "İyul 2026"
MonthNames::weekDayShort(1);            // "B.e"
```

---

## 8. Fayl / CDN URL - `CdnUrl`

```php
use App\Support\Url\CdnUrl;

CdnUrl::url($model->image);              // növü özü ayırd edir
CdnUrl::asset('assets/images/logo.png'); // public/ altındakı statik fayl
CdnUrl::storage($path);                  // yüklənmiş fayl
```

Siyasət:

```text
boş dəyər               → null
http/https ilə başlayır → toxunulmur
assets/...              → CDN (və ya app.url) + yol
storage/...             → CDN (və ya app.url) + yol
digər nisbi yol         → Storage::disk('public')->url()
```

`.env` → `CDN_URL`. **Diqqət:** CDN qoşulanda `config/filesystems.php` →
`disks.public.url` da eyni ünvanla uzlaşdırılmalıdır, əks halda yüklənmiş
fayllar köhnə domendə qalır.

Bu sinif yalnız **public** fayllar üçündür. İmzalı/gizli fayl üçün
`Storage::temporaryUrl()` işlədilir - fayl adına baxıb təxmin edilmir.

---

## 9. Panel köməkçiləri

### `App\Support\Gopanel\StatCard`

Dashboard/siyahı başındakı info kartı. Faiz, ox istiqaməti və rəng **blade-də
hesablanmır** - burada hazırlanır.

```php
$cards = StatCard::collection([[
    'label' => 'Yeni istifadəçilər',
    'value' => $current, 'current' => $current, 'previous' => $previous,
    'series' => $daily, 'icon' => 'fas fa-users',
]]);
```

### `App\Support\Gopanel\PeriodRange`

«Bu dövr / əvvəlki dövr» müqayisəsi. Filtrdə tarix seçilibsə həmin aralıq,
seçilməyibsə son N gün. `previous()` **eyni uzunluqda** dərhal əvvəlki aralığı
qaytarır - müqayisə həmişə bərabər uzunluqlu iki dövr arasında olur.

```php
$period = PeriodRange::fromFilters($request->from, $request->to);
$prev   = $period->previous();
$period->label();    // "son 30 gün" / "01.07.2026 - 30.07.2026"
$period->dayKeys();  // qrafik seriyasında boş günlər də olsun deyə
```

### `App\Support\Gopanel\PanelIconMap`

Kənar ikon dəstini (Phosphor `ph-*`, Tabler `ti ti-*`) Font Awesome 5-ə çevirir.
FA5 sinifləri olduğu kimi ötürülür - ikiqat çevirmə olmur.

---

## 10. Jurnal

### Fayl jurnalı - `App\Services\Activity\LogService`

```php
$log = new LogService('sms');
$log->info('Mesaj', ['key' => 'value']);
```

Kanallar `config/logging.php`-də: `system-errors`, `gopanel`, `gopanel-auth`,
`mail`, `sms`, `export`, `transactions`, `cron`. `manual => true` olan kanallar
panelin «Fayl jurnalı» filtrində (`name` adı ilə) görünür.

Həssas açarlar (`password`, `token`, `card_number`...) `config/custom/logging.php`
→ `sensitiveKeys` siyahısına görə maskalanır.

Davranış zəmanətləri:

- `info`/`warning` üçün backtrace (`log_details`) YAZILMIR - hər log üçün
  yazmaq səs-küy idi. `error`/`critical`/`alert`/`emergency` üçün HƏMİŞƏ yazılır.
  Adi səviyyədə də lazımdırsa: `new LogService('kanal', true, true)`.
- Yanlış səviyyə adı (`$log->inof()`) `BadMethodCallException` atır - əvvəl
  səssizcə udulurdu və log itirdi. Jurnal yazma xətası isə (DB yoxdur) əsas
  əməliyyatı dayandırmır.
- `LogService::actorContext()` / `actorLabel()` - xəta loguna «kim» məlumatı
  (sayt istifadəçisi + admin) eyni formada; console/queue-da boş qalır.

### Cron jurnalı - `App\Helpers\Common\Timer`

```php
$timer = Timer::cronStart('logs:cleanup');   // cron kanalına «başladı»
// ...
$timer->cronEnd("{$deleted} sətir silindi"); // «tamamlandı» + elapsed_ms
```

Scheduler-də işləyən hər daimi command bu iki sətri yazır - panelin fayl
jurnalında `cron` filtri ilə «nə vaxt işlədi, nə qədər çəkdi» görünür.

### Fəaliyyət jurnalı - kim nə etdi

1. Modelə `App\Traits\Activity\LogsAdminActivity` trait-i əlavə olunur;
2. `config/custom/activity_messages.php`-də model adı (namespace-siz) ilə blok yazılır.

**İkinci addım olmadan heç nə jurnallanmır** - trait config-də adı olmayan modeli
sükutla ötürür. Yəni bu fayl həm mətn mənbəyi, həm də aç/söndür düyməsidir.

```php
'Blog' => [
    'title'   => 'Bloq',
    'created' => ':causer yeni bloq yazısı yaratdı — :title',
    'updated' => ':causer bloq yazısını yenilədi — :title',
    'deleted' => ':causer bloq yazısını sildi — :title',
],
```

Placeholder-lar:

| Placeholder | Dəyər |
|---|---|
| `:causer` | əməliyyatı edən (admin/istifadəçi; cron/queue-da «Sistem») |
| `:changes` | nə dəyişdi: «ad: Köhnə → Yeni, aktivlik: xeyr → bəli» (`updated` üçün) |
| `:istənilən_atribut` | modelin atributu (accessor daxil); boşdursa «-», həssasdırsa «***» |

```php
'updated' => ':causer bloq yazısını yenilədi — :title (:changes)',
```

Yalnız HƏMİŞƏ dolu olan sütunlar seçilir.

Modeldə `protected $logEnabled = false;` varsa, config-də adı olsa da jurnallanmır -
model bayrağı config-dən üstündür.

**Modeldə əlavə idarəetmə:**

```php
protected $logExcept = ['views', 'last_seen_at'];   // səs-küy sahələri - loga düşmür

public function activityContext(): array            // detal pəncərəsində «kontekst» bloku
{
    return ['category_name' => $this->category?->name];
}
```

Həssas sahələr (`password`, `token`, `otp`, `pin`...) `ActivityLogHelper::SENSITIVE_ATTRIBUTES`
siyahısına görə **heç vaxt** `activity_log`-a yazılmır; modelin `$hidden` sahələri də.

**Oxunaqlı fərq - `App\Support\Activity\ActivityChangeFormatter`.** Həm `:changes`
mətni, həm paneldəki «Nə dəyişdi» cədvəli buradan gəlir - iki nüsxə olsaydı log
sətri ilə detal pəncərəsi fərqli mətn göstərərdi. Sahə adları və dəyər
xəritələri `activity_messages.php`-dədir:

| Açar | Nə üçün |
|---|---|
| `attribute_labels` (kökdə) | bütün modellər üçün ortaq sahə adları (`is_active` → «aktivlik») |
| `<Model>.attribute_labels` | modelə xas ad (ortağın üstündədir) |
| `<Model>.attribute_values` | rəqəmin mənası (`status: 1` → «dərc edilib») |
| `date_only_attributes` | saatı göstərilməyən tarix sahələri (`_date` sonluğu avtomatikdir) |
| `technical_attributes` | «Nə dəyişdi» cədvəlində gizlədilən sahələr (`id`, `uid`...) |
| `context_labels` | `activityContext()` açarlarının adları |

Kök səviyyədəki açarlar kiçik hərflə, model blokları böyük hərflə yazılır -
`ActivityLogHelper::modelBlocks()` filtr siyahısını belə ayırır.

---

## 11. Yazma qatı - `BaseRepository`

`app/Repositories/BaseRepository.php` - **yalnız** insert / update / delete.

```php
use App\Repositories\BaseRepository;

$item = app(BaseRepository::class)->save($item, ['title' => 'Yeni ad']);
```

- yalnız modelin `fillable`-ında olan **skalyar** açarlar mənimsədilir; massivlər
  (`title[az]`, `meta[...]`) ayrıca layerdə emal olunur;
- `delete()` modeldə `SoftDeletes` varsa arxivləmədir, `forceDelete()` isə
  sətri tamamilə silir (toplu rejimdə çağırılmır);
- burada SELECT **yoxdur**.

Köhnə `CrudHelper::saveInstance()` imzası saxlanılıb - iş bu sinifə ötürülür,
ona görə starter üzərində qurulmuş layihələr sınmır.

---

## 12. Məzmun formalarının saxlanması - `ContentSaveService`

Bloq, kateqoriya, xidmət, məhsul, slayder, «Haqqımızda»... hamısında eyni
ardıcıllıq var: **fayl yüklə → sütunları yaz → tərcümələri yaz → SEO meta yaz**.
Bu ardıcıllıq tək yerdədir.

```php
// FormRequest formanın şəklini bilir
$item = $this->content->save($item, $request->payload(), $request->fileFields());
```

| Sinif | Rolu |
|---|---|
| `App\DTOs\Gopanel\ContentPayload` | `attributes` / `translations` / `meta` / `files` - dörd axın ayrı |
| `App\DTOs\Gopanel\FileField` | hansı input hansı sütuna düşür (`icon_image` → `icon`) |
| `App\Http\Requests\Gopanel\GopanelFormRequest` | icazə + validasiya + `payload()` |
| `App\Services\Gopanel\Content\ContentSaveService` | ardıcıllığın özü |

**Nə həll edir:** əvvəllər bu blok yeddi controller-də kopyalanmışdı və hər
nüsxədə bir addım fərqli idi - kimisi meta saxlamırdı, kimisi şəkil
seçilməyəndə köhnə yolu boş dəyərlə əvəz edirdi.

Yeni modul üçün:

```php
class NewsSaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.news';
    protected array $translatedFields = ['title', 'description', 'slug'];
    protected array $fileInputs = ['image'];

    public function rules(): array
    {
        return ['title' => ['required', 'array', new TranslatedRequired()]];
    }

    public function fileFields(): array
    {
        return [new FileField(input: 'image', column: 'image', prefix: 'news')];
    }
}
```

`ability()` override edilməsə `.add` / `.edit` avtomatik seçilir. Tək sətirli
səhifələrdə (Haqqımızda, Əlaqə, Sayt tənzimləmələri) həmişə `.edit` işlədilir.

---

## 13. İcazə qatı - `CrudPolicy`

```php
class NewsPolicy extends CrudPolicy
{
    protected string $module = 'gopanel.news';   // hamısı bu qədər
}
```

`viewAny/view → .index`, `create → .add`, `update → .edit`, `delete → .delete`.
`is_super` admin `BasePolicy::before()` ilə keçir - `Gate::before` işləməyən
yerlərdə də (job içində `Gate::forUser(...)`) davranış eyni olsun deyə.

Policy `app/Providers/AuthServiceProvider.php` → `$policies` massivində
qeydiyyatdan keçir (avtomatik kəşf `App\Policies\<Model>Policy` gözlədiyi üçün
alt qovluqdakı policy əl ilə yazılır).

---

## 14. Validasiya qaydası - `TranslatedRequired`

```php
'title' => ['required', 'array', new TranslatedRequired()],
```

Çoxdilli sahədə **yalnız standart dil** məcburidir. `required` massivin özünü
yoxlayır (bütün dillər boş olsa da keçir), `required_array_keys` isə bütün
dilləri məcbur edir. Standart dil `languages` cədvəlindən oxunur.

---

## 15. Bayt formatı - `ByteSize`

```php
ByteSize::human(5_872_025_600);   // "5.5 GB"
ByteSize::humanOrDash(0);         // "—"
```

Format TƏK yerdədir - əks halda eyni ölçü bir səhifədə «5.4 GB», digərində
«5487 MB» görünür.

---

## 16. Cədvəl hüceyrələri - `RendersRichCells`

`app/Datatable/Gopanel/Concerns/RendersRichCells.php` - datatable sinifləri üçün
hazır hüceyrə qəlibləri: `titleCell()`, `mutedCell()`, `textCell()`, `badge()`,
`dateCell()`, `actionsCell()`, `linkBtn()`, `deleteRowBtn()`.

Qarşılığı olan CSS: `public/assets/gopanel/css/custom.css` → «Cədvəl hüceyrələri»
bloku (`gp-cell-*`, `gp-badge-*`). Sinif adları dəyişəndə **hər ikisi** dəyişir.

`deleteRowBtn()`-a öz route-unu ötürmək olar: ümumi `gopanel.general.delete`
ünvanı modul icazəsini yoxlamır, həssas modullar (backup) öz endpoint-ini verir.

---

## 17. Query qatı

Stil qaydaları:

```php
declare(strict_types=1);

namespace App\Queries\Gopanel\Site;

final class BlogQuery
{
    public function __construct(
        private readonly ?string $from = null,
        private readonly ?string $to = null,
    ) {}

    public function monthlyCounts(): Collection { /* bir metod = bir sorğu */ }
}
```

- `declare(strict_types=1)`, konstruktorda readonly promotion;
- metodlar **instance**-dır, `static` deyil (test edilə bilsin);
- Query içində `request()` **oxunmur** - filtr konstruktorla ötürülür;
- Query **yazmır**: `save()`, event, fayl yükləmə burada olmur;
- səhifələnməmiş qeyri-məhdud nəticə qaytarılmır.

---

## 18. API cavab qatı

`.claude/rules/04-api.md`-dəki zərf müqaviləsinin hazır icrası.

| Sinif | Rolu |
|---|---|
| `App\Http\Controllers\Api\ApiController` | Baza: `success()`, `created()`, `updated()`, `deleted()`, `error()`, `notFound()`, `forbidden()`... |
| `App\Http\Responses\Api\SuccessResponse` | `{success:true, message, data, meta}`; paginator → `meta.pagination` |
| `App\Http\Responses\Api\ErrorResponse` | `{success:false, message, code, errors}`; `validation()` xətaları bir cümləyə yığır |
| `App\Exceptions\Domain\DomainException` | İş qaydası istisnası: status + kod daşıyır, controller-də try/catch yazılmır |
| `App\Support\Api\PaginationParams` | `per_page`/`page` - default və maksimum `config/api.php`-dən |

```php
final class BlogController extends ApiController
{
    public function index(Request $request, BlogQuery $query): JsonResponse
    {
        $p = PaginationParams::fromRequest($request);

        return $this->success(BlogResource::collection($query->paginate($p->perPage, $p->page)));
    }
}

final class BalanceNotEnough extends DomainException
{
    protected string $errorCode = 'BALANCE_NOT_ENOUGH';
}
```

`app/Exceptions/Handler.php` `api/*` sorğularında **istənilən** istisnanı bu
zərfə çevirir (422/401/403/404/429/500). Daxili detal cavaba yazılmır, jurnala
gedir. Panel və saytın JSON cavabları toxunulmaz qalıb - `crud.js` `status`
açarına bağlıdır.

`data` HƏMİŞƏ obyekt/massivdir - `null` `{}`-ə çevrilir.

---

## 19. Əlaqə məlumatı - `PhoneNumber`, `EmailAddress`, `StringHelpers`

```php
PhoneNumber::normalize('(055) 272-51-95');        // "994552725195"
PhoneNumber::normalizeIn($data, ['phone', 'contacts.*.phone']);
EmailAddress::normalize('  Test@Mail.AZ ');        // "test@mail.az" (səhvdirsə null)
EmailAddress::mask('test@mail.az');               // "t***@mail.az"
StringHelpers::obfuscatePhone('994552725195');    // "99455*****95"
```

Normallaşdırma FormRequest-in `prepareForValidation()`-ında, **validasiyadan
əvvəl** edilir. **Niyə:** ön tərəf maskası tək müdafiə deyil (Excel idxalı, API) -
normallaşdırılmasa eyni nömrə ilə iki hesab yaranır və SMS tutmur.

---

## 20. Əlavə tarix köməkçiləri

### `App\Support\Date\LocalizedDateFormatter`

```php
LocalizedDateFormatter::dateTime($post->published_at);   // "27 sentyabr 2026, 01:03" / "27 September 2026, 01:03"
LocalizedDateFormatter::date($value, 'ru');
```

İstifadəçiyə görünən dilə bağlı tarix **TƏK** buradan formatlanır. Carbon-un
qlobal locale-i dəyişdirilmir (`Carbon::setLocale()` növbə worker-ində bir işin
dilini növbəti işə «sızdırır»).

### `App\Support\Date\FilterDateRange`

Siyahı filtrindəki flatpickr aralığı (`2026-09-01+-+2026-09-30`):

```php
[$from, $to] = FilterDateRange::parse($filters['date']);
FilterDateRange::applyOverlap($query, $from, $to, 'start_date', 'end_date');  // kəsişmə
FilterDateRange::label($from, $to);                                           // export başlığı
```

Default aralıq **yoxdur** - seçilməyibsə şərt qoyulmur (gələcək tarixli qeyd
gizlənməsin). Dashboard müqayisəsi üçün `PeriodRange` işlədilir.

---

## 21. Digər saf köməkçilər

| Sinif | Nə edir | Niyə ortaqdır |
|---|---|---|
| `App\Support\Geo\Distance` | Haversine məsafə (m), `label()` → «320 m» | düstur iki yerdə saxlananda nəticə fərqlənir |
| `App\Support\Http\PreviewBotDetector` | WhatsApp/Telegram önizləmə robotu, axtarış botu, curl | «link açıldı» sayğacı robotları saymasın (tək müdafiə deyil - JS təsdiqi əsasdır) |
| `App\Support\Translation\LoadedTranslation` | tərcüməni yüklənmiş `translations` əlaqəsindən oxuyur | siyahıda N+1 olmasın |
| `App\Support\Gopanel\GuideText` | `**qalın**`, `` `kod` `` → HTML, qalanı escape | təlimat mətnləri JSON-da xam HTML saxlamasın |
| `App\Support\Url\GopanelUrl` | panel linki `GOPANEL_URL` kökündən | növbədən gedən email/bildiriş linki sayt domeninə getməsin |

`Translation` trait-i özü də artıq yüklənmiş əlaqədən oxuyur (əvvəl hər atribut
üçün ayrıca sorğu gedirdi). `TranslationHelper` tərcüməni yazandan sonra
yaddaşdakı əlaqəni atır - eyni sorğuda köhnə dəyər görünməsin.

---

## 22. Model trait-ləri (yeni)

| Trait | Nə edir |
|---|---|
| `App\Traits\System\HasLabel` | `protected static string $label = 'Status';` → `Model::label()` |
| `App\Traits\System\HasNotFoundMessage` | `Model::notFoundMessage()` → «Status tapılmadı.» (HasLabel varsa onun adı) |
| `App\Traits\System\HasSerialNumber` | insan üçün artan nömrə (sifariş №); paralel yazıda unikal toqquşmanı təkrar cəhdlə həll edir |
| `App\Traits\Presentable\<Domen>\Has<Model>Presentation` | göstəriş accessor-ları - qayda: [rules/presentable-traits.md](rules/presentable-traits.md) |

`HasSerialNumber` iki abstract metod tələb edir: `serialNumberColumn()` və
`serialNumberStart()`; sütunda **unikal indeks** olmalıdır.

---

## 23. Panel host məhdudiyyəti - `GopanelHostMiddleware`

`GOPANEL_URL` doludursa panel yalnız həmin host-dan açılır, qalanında 404.
Boşdursa (lokal) heç nə yoxlanmır. `RouteServiceProvider`-də panel route
qrupuna bağlıdır (alias `gopanel.host`). Deployment: [rules/domains-cdn-gopanel.md](rules/domains-cdn-gopanel.md).

---

## 24. Status/tip lookup cədvəlləri

5-10 sətirlik panel lüğətləri (sifariş statusu, elan tipi) üçün hazır qat:
`BaseLookupController` + `LookupService` + `config/gopanel/lookups.php` reyestri.
Route, icazə qrupu və sidebar sətri reyestrdən avtomatik qurulur; sıra
sürüklə-burax ilə bir sorğuda yazılır, standart (default) sətir yalnız bir olur,
istifadədə olan sətir silinmir.

```bash
php artisan gopanel:make-lookup OrderStatus --title="Sifariş statusları" --fields=color,class_name --booleans=is_default,is_active
php artisan migrate && php artisan db:seed --class=PermissionSeeder
```

Əvvəlcə yoxla: dəyər dəsti kodda sabitdirsə **Enum** kifayətdir - lookup yalnız
admin redaktə edəndə lazımdır. Tam qayda: [rules/gopanel-status-and-type-cruds.md](rules/gopanel-status-and-type-cruds.md).

---

## 25. Global helper-lər (yeni)

| Funksiya | Nə üçün |
|---|---|
| `asset_v('assets/gopanel/js/x.js')` | `?v=filemtime` - deploy-dan sonra köhnə JS qalmasın, dəyişməyəndə keşdən gəlsin |
| `form_date($model->start_date)` | `<input type="date">` üçün `Y-m-d` (Carbon `2026-05-01 00:00:00` input-u boş göstərir) |
| `selected($a, $b)` | `selected` atributu |
| `markdown_url()` | cari səhifənin `.md` variantı (SEO - [markdown-pages.md](markdown-pages.md)) |
| `file_url($path, $fallback)` | yüklənmiş faylın tam (CDN) URL-i |

Fayl URL-ləri üçün `FileUploader`: `url($path, 'auto')` (əvvəl storage, sonra public),
`storageUrl()` / `publicUrl()` (diskə baxmadan), `*OrFallback($path, $placeholder)`.

---

## 26. Təhlükəsizlik və auth

Tam təsvir: [security-and-auth.md](security-and-auth.md). Hamısı config ilə idarə
olunur və default mövcud davranışı dəyişmir - **istisna: panel giriş limiti** (5 cəhd / 10 dəq).

| Hissə | Sinif / alias | Default |
|---|---|---|
| Həssas əməliyyatın ikinci təsdiqi (PIN) | `AdminPinService` + route-a `gopanel.pin` (backup endirmədə qoşulub) | `ADMIN_PIN_ENABLED=false` |
| Giriş limiti | `App\Services\Auth\LoginThrottle` | açıq; `LOGIN_MAX_ATTEMPTS=0` söndürür |
| E-poçt OTP | `App\Services\Auth\OtpService` (kod keşdə, jurnala düşmür) | opt-in |
| OTP / şifrə bərpası məktubu | `MailService::sendOtpEmail()` / `sendForgotPasswordEmail()` | opt-in |
| Panel IP allowlist (CIDR, fail-closed) | `ip.restrict` | `IP_RESTRICTION_ENABLED=false` |
| Sayt bağlı → 503 + Retry-After | `site.status` + `App\Services\Site\SiteStatusService` | `SITE_STATUS_ENFORCE=false` |
| Dev domen qoruması (noindex + allowlist) | `RestrictDevDomain` | `DEV_GUARD_ENABLED=false` |
| Server-Timing başlığı | `App\Http\Middleware\ServerTiming` | flag + `APP_DEBUG` |
| Spatie-siz yüngül icazə yoxlaması | `App\Traits\System\LeanPermissionChecks` | opt-in |

---

## 27. Qeyd müəllifləri, domen jurnalı, tipli tənzimləmələr

Tam sənəd: [authors-settings.md](authors-settings.md)

- **`HasAuthors` (opt-in)** - `record_authors` morph cədvəli: müəllif `created`-də
  avtomatik, məsul `claim/release/takeover` ilə. Tarixçə silinmir; unikal indeks iki
  adminin eyni anda götürməsini DB-də kəsir. Panel: `HandlesAssignment` trait-i,
  `AuthorCell`, `assignment-banner`, icazələr `<prefix>.assign` / `<prefix>.assign.takeover`.
  Qapı: `AssignmentGuard` (409 `NOT_ASSIGNED`).
- **`ActivityLogger`** - domen hadisəsini bir çağırışla `activity_log` + `LogService`
  kanalına eyni mətnlə yazır. Kanal: `App\Enums\System\LogChannelEnum` və ya string.
- **Tipli key-value tənzimləmələr** - tərif config-də (`type/default/label/group/rules`),
  cast/validasiya/forma `SettingDefinitions`-dən, saxlama `setting_values` +
  `SettingsService`. Yeni açar üçün miqrasiya lazım deyil. `SiteSetting` dəyişməyib.
  Adminin şəxsi ayarları: `AdminSettingsService` (+ `config/gopanel/admin_settings.php`).
- Kiçik: `App\Support\Geo\StaticMap` (statik xəritə şəkli), `App\Support\Url\FrontendUrl`
  (headless sayt ünvanı), `App\Support\Http\MobilePlatform`, `App\Enums\Common\PlatformEnum`.
- Jurnal köməkçiləri: `TranslationHelper::fromInputWithChanges()` +
  `App\Support\Activity\TranslationChanges` (tərcümə dəyişikliyi jurnala),
  `ActivityEventLabel` (hadisə adı/rəngi), `LogService::withDetails()`.

---

## Config faylları

| Fayl | Nə üçün |
|---|---|
| `config/custom/activity_messages.php` | Fəaliyyət jurnalı mətnləri **və** hansı modelin jurnallanacağı; ortaq sahə adları |
| `config/custom/cache.php` | Keş qrupları + model → qrup xəritəsi (`CacheInvalidationServiceProvider` oxuyur) |
| `config/api.php` | API səhifələmə: default və maksimum `per_page` |
| `config/seo/*.php` | SEO: meta defaultları, JSON-LD, publik məzmun mənbələri, `.md` ([seo.md](seo.md)) |
| `config/custom/export.php` | Export limiti, disk, brend, handler xəritəsi |
| `config/custom/logging.php` | Jurnal səviyyələri + maskalanan həssas açarlar |
| `config/custom/mail.php` | Mail brendi + növbə |
| `config/custom/queue_monitor.php` | İzlənən növbələr |
| `config/custom/security.php` | IP məhdudiyyəti, giriş limiti, yükləmə qaydaları |
| `config/custom/sms.php` | SMS provayderi və açarları |
| `config/gopanel/backup.php` | Backup yolları, `mysqldump` seçimləri, disk həddi ([docs/backup.md](backup.md)) |
| `config/gopanel/system_status.php` | Monitor: yenilənmə aralığı, həddlər, heartbeat faylı ([docs/system-status.md](system-status.md)) |

**Qayda:** `env()` yalnız config faylının içində çağırılır. Servis/controller
içində `config()` işlədilir - əks halda `php artisan config:cache` sonrası
dəyər boş qalır.
