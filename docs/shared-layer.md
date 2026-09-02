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
├── Helpers/            kiçik köməkçilər + global helpers.php
│   └── Gopanel/        ServerMetricsHelper (CPU/RAM/disk/uptime)
├── Http/
│   ├── Requests/Gopanel/  GopanelFormRequest (bazа) + modul sorğuları
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
│   ├── Cache/          CacheKey
│   ├── Date/           DayRange, Duration, MonthNames
│   ├── Export/         ExportBranding
│   ├── Files/          ByteSize
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
| `config/gopanel/backup.php` | Backup yolları, `mysqldump` seçimləri, disk həddi ([docs/backup.md](backup.md)) |
| `config/gopanel/system_status.php` | Monitor: yenilənmə aralığı, həddlər, heartbeat faylı ([docs/system-status.md](system-status.md)) |

**Qayda:** `env()` yalnız config faylının içində çağırılır. Servis/controller
içində `config()` işlədilir - əks halda `php artisan config:cache` sonrası
dəyər boş qalır.
