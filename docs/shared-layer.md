# Ümumi Qat (Support / Services / Queries)

Bu sənəd Gopanel-in **layihədən asılı olmayan** təkrar istifadə qatını təsvir edir.
Buradakı siniflərin heç biri konkret domenə (davamiyyət, satış, elan) bağlı
deyil - yeni layihədə olduğu kimi işləyir.

> Yeni sinif əlavə edərkən qərar ağacı: [rules/application-layering-support-query-traits-helpers-enums.md](rules/application-layering-support-query-traits-helpers-enums.md)

## Qovluq xəritəsi

```text
app/
├── Contracts/          interfeyslər (infrastruktur adapterləri)          [boş]
├── DTOs/               typed data transfer obyektləri                    [boş]
├── Datatable/          server-side cədvəl sinifləri
├── Enums/              sabit dəyər dəstləri
│   └── Common/Status/                                                    [boş]
├── Helpers/            kiçik köməkçilər + global helpers.php
├── Http/
│   └── Resources/      API cavab formatı                                 [boş]
├── Jobs/               növbəyə düşən işlər                               [boş]
├── Observers/          model event dinləyiciləri                         [boş]
├── Policies/           icazə qərarları                                   [boş]
├── Queries/            BÜTÜN böyük SELECT-lər
│   ├── Api/                                                              [boş]
│   ├── Gopanel/                                                          [boş]
│   └── Site/                                                             [boş]
├── Repositories/       YALNIZ insert / update / delete
├── Rules/              xüsusi validasiya qaydaları                       [boş]
├── Services/           iş məntiqi, tranzaksiyalar, xarici API
│   ├── Activity/       LogService (fayl jurnalı)
│   ├── Bulk/           BulkActionService (abstract)
│   ├── Cache/          CacheService
│   ├── Export/         ExportContext, ExportResult, Contracts/ExportHandler
│   ├── Mail/           MailService + Templates/
│   ├── Queue/          QueueMonitorService
│   ├── Sms/            SmsService + ProviderInterface + Providers/
│   ├── Gopanel/        panelə xas servislər
│   └── Site/           sayt tərəfi servisləri
├── Support/            saf, vəziyyətsiz primitivlər
│   ├── Cache/          CacheKey
│   ├── Date/           DayRange, Duration, MonthNames
│   ├── Export/         ExportBranding
│   ├── Files/                                                            [boş]
│   ├── Gopanel/        StatCard, PeriodRange, PanelIconMap
│   ├── Security/                                                         [boş]
│   └── Url/            CdnUrl
└── Traits/             model davranışı və təqdimat trait-ləri
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

Model dəyişəndə keşin özü təmizlənsin deyə `AppServiceProvider::boot()`-da:

```php
foreach (CacheKey::trackedModels() as $model) {
    $model::saved(fn ($m) => CacheKey::flushForModelType($m::class));
    $model::deleted(fn ($m) => CacheKey::flushForModelType($m::class));
}
```

Çoxdilli layihədə tərcümə cədvəlini də bağlamaq lazımdır - mətn dəyişəndə
modelin özü `saved` olmur.

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

Yeni provayder: `ProviderInterface` implement edilir, uğursuzluqda **exception atılır**
(`false` qaytarmaq gizli xəta yaradır).

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
`mail`, `sms`, `export`, `transactions`. `manual => true` olan kanallar panelin
log-viewer siyahısında görünür.

Həssas açarlar (`password`, `token`, `card_number`...) `config/custom/logging.php`
→ `sensitiveKeys` siyahısına görə maskalanır.

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

Placeholder: `:causer` (əməliyyatı edən) + modelin **istənilən atributu**.
Yalnız HƏMİŞƏ dolu olan sütunlar seçilir - nullable sahə mesajda boş yer buraxır.

Modeldə `protected $logEnabled = false;` varsa, config-də adı olsa da jurnallanmır -
model bayrağı config-dən üstündür.

---

## 11. Query qatı

`app/Queries/{Gopanel,Site,Api}/` boşdur - hər layihə öz sorğularını yazır.
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

## Config faylları

| Fayl | Nə üçün |
|---|---|
| `config/custom/activity_messages.php` | Fəaliyyət jurnalı mətnləri **və** hansı modelin jurnallanacağı |
| `config/custom/cache.php` | Keş qrupları + model → qrup xəritəsi |
| `config/custom/export.php` | Export limiti, disk, brend, handler xəritəsi |
| `config/custom/logging.php` | Jurnal səviyyələri + maskalanan həssas açarlar |
| `config/custom/mail.php` | Mail brendi + növbə |
| `config/custom/queue_monitor.php` | İzlənən növbələr |
| `config/custom/security.php` | IP məhdudiyyəti, giriş limiti, yükləmə qaydaları |
| `config/custom/sms.php` | SMS provayderi və açarları |

**Qayda:** `env()` yalnız config faylının içində çağırılır. Servis/controller
içində `config()` işlədilir - əks halda `php artisan config:cache` sonrası
dəyər boş qalır.
