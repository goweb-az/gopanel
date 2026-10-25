# Qeyd müəllifləri, domen jurnalı və tipli tənzimləmələr

Üç ümumi qat (texnotech və qrgate layihələrindən köçürülüb, starter üçün
ümumiləşdirilib):

| Qat | Nə həll edir |
|---|---|
| [1. Qeyd müəllifləri](#1-qeyd-müəllifləri-hasauthors) | «Bunu kim əlavə etdi?», «Bu müraciətlə hazırda kim məşğuldur?», «Əvvəlcə üzərinizə götürün» |
| [2. Domen jurnalı](#2-domen-jurnalı-activitylogger) | Bir çağırışla həm «Fəaliyyət jurnalı»na (`activity_log`), həm fayl jurnalına yazmaq |
| [3. Tipli tənzimləmələr](#3-tipli-key-value-tənzimləmələr) | Tənzimləməni config-də elan et → cast, validasiya, saxlama, forma hazırdır |
| [4. Kiçik köməkçilər](#4-kiçik-köməkçilər) | `StaticMap`, `FrontendUrl`, `MobilePlatform`, `PlatformEnum`, `LogChannelEnum` |

---

## 1. Qeyd müəllifləri (`HasAuthors`)

**Opt-in-dir**: yalnız `use HasAuthors;` yazılan model iştirak edir. `BaseModel`-ə
əlavə edilmir - hər lookup/tərcümə sətri üçün əlavə INSERT lazımsız yükdür.

### Hissələr

| Sinif | Rolu |
|---|---|
| `App\Traits\System\HasAuthors` | `creator()`, `assignee()`, `authors()`, `assignmentHistory()` əlaqələri; `created`-də müəllifi yazır |
| `App\Models\Author\RecordAuthor` | `record_authors` sətri; `display_name`, `isStale()` |
| `App\Services\Author\RecordAuthorService` | `setCreator()`, `claim()`, `release()`, `takeover()`, `current()`, `history()`, `using()` |
| `App\Queries\Gopanel\Author\RecordAuthorQuery` | hazırkı qeyd / tarixçə oxunuşu |
| `App\Enums\System\Author{Role,Source,EndReason}Enum` | `creator/assignee`; `panel/customer/integration/import/system`; `released/reassigned/expired` |
| `App\Exceptions\Domain\System\RecordAlreadyAssigned` (409) / `RecordAssignmentDenied` (403) | `DomainException`-dan törəyir |
| `App\Http\Controllers\Gopanel\Concerns\HandlesAssignment` | götür/burax endpoint-ləri + jurnal |
| `App\Support\Gopanel\AssignmentGuard` | istəyə bağlı «əvvəlcə üzərinizə götürün» qapısı (409 `NOT_ASSIGNED`) |
| `App\Helpers\Gopanel\AuthorCell` | siyahı nişanları, «üzərimə götür» düyməsi, zolaq datası, tarixçə sətirləri |
| `gopanel.component.assignment-banner` / `assignment-history` / `assignment-assets` | blade komponentləri |
| `public/assets/gopanel/js/modules/assignment/gp-assignment(-gate).js`, `css/assignment.css` | UI |
| `config/gopanel/assignment.php` | `ttl_hours` (`GOPANEL_ASSIGNMENT_TTL_HOURS`, default 8) |

### Niyə ayrıca cədvəl, `created_by` sütunu yox

- hər cədvələ sütun əlavə etmək eyni işi onlarla dəfə təkrarlamaqdır;
- `activity_log` təmizlənir, müəllif isə sətrin daimi atributudur (siyahıda süzülür, Excel-ə düşür);
- cədvəl **tarixçə** saxlayır: məsul dəyişəndə köhnə qeyd bağlanır (`is_current = NULL`, `ended_at`, `end_reason`), silinmir.

`is_current` hazırkı qeyddə `1`, keçmişdə `NULL`-dur - **heç vaxt `0` yazılmır**.
Unikal indeks (`authorable_type, authorable_id, role, is_current`) NULL-ları saymır,
ona görə «bir sətrin bir aktiv məsulu» qaydası DB səviyyəsində qorunur: iki admin
eyni anda «götür» basanda ikincisi `RecordAlreadyAssigned` alır.

### Bölməyə qoşmaq (4 addım)

```php
// 1. Model
class Order extends BaseModel
{
    use HasAuthors;
}

// 2. Controller
class OrderController extends GoPanelController
{
    use HandlesAssignment;

    public function assign(Request $request, Order $item): JsonResponse
    {
        return $this->applyAssignment($request, $item);
    }

    public function release(Order $item): JsonResponse
    {
        return $this->applyRelease($item);
    }

    protected function assignmentRoutePrefix(): string
    {
        return 'gopanel.sales.orders';
    }

    // istəyə bağlı: əl dəyişəndə köhnə məsula bildiriş
    protected function afterAssignment(Model $item, RecordAuthor $author, ?RecordAuthor $previous): void {}
}
```

```php
// 3. Route (routes/gopanel.php)
Route::post('/{item}/assign', [OrderController::class, 'assign'])
    ->middleware('can:gopanel.sales.orders.assign')->name('assign');
Route::post('/{item}/release', [OrderController::class, 'release'])
    ->middleware('can:gopanel.sales.orders.assign')->name('release');
```

```php
// 4. İcazələr (config/gopanel/permission_list.php) + PermissionSeeder
['name' => 'gopanel.sales.orders.assign',          'title' => 'Sifarişi üzərinə götürmək'],
['name' => 'gopanel.sales.orders.assign.takeover', 'title' => 'Sifarişi başqasının üzərindən götürmək'],
```

`takeover` ayrıca icazədir (bir əməliyyat = bir icazə). İstisna: təyinat
`ttl_hours`-dan köhnədirsə icazəsiz də götürülür - admin işi buraxmağı unudanda
qeyd əbədi «onun» qalmasın.

### Siyahı və detal səhifəsi

```php
// Query / datatable - N+1 olmasın
$query->with(['creator.admin', 'assignee.admin']);

// Datatable hüceyrəsi
AuthorCell::creatorLine($row) . AuthorCell::assigneeBadge($row);
AuthorCell::claimButton($row, 'gopanel.sales.orders', 'sifariş');   // icazəyə görə boş ola bilər
```

Siyahı səhifəsi düymə işlədirsə `@include('gopanel.component.assignment-assets')`
yazır (CSS + JS `@once` ilə bir dəfə qoşulur).

```php
// Detal controller-i - blade-də @php yoxdur, data hazır ötürülür
return view('...', [
    'banner'         => AuthorCell::banner($item, 'gopanel.sales.orders', 'sifariş'),
    'assignmentRows' => AuthorCell::historyRows(app(RecordAuthorService::class)->history($item)),
]);
```

```blade
@include('gopanel.component.assignment-banner', ['banner' => $banner])
@include('gopanel.component.assignment-history', ['assignmentRows' => $assignmentRows])
```

### «Əvvəlcə üzərinizə götürün» qapısı (istəyə bağlı)

Təyinat **qıfıl deyil**. Bölmə dəyişdirən əməliyyatı bağlamaq istəyirsə:

```php
if (! AssignmentGuard::allows($item, auth('gopanel')->user())) {
    return response()->json(AssignmentGuard::payload($item), AssignmentGuard::STATUS);
}
```

Super admin və `HasAuthors` işlətməyən model həmişə keçir. Cavab 409 + `code: NOT_ASSIGNED`;
`gp-assignment-gate.js` qlobal `ajaxError`-dan tutub «üzərimə götürürəm» sualını
verir və təsdiqdən sonra **dayandırılmış sorğunu təkrar göndərir**.

### Konsol / idxal

```php
RecordAuthorService::using(AuthorSourceEnum::IMPORT, fn () => $importer->run());
```

Callback müddətində yaranan sətirlərin müəllifi «İdxal» olur («Sistem» əvəzinə).
İnteqrasiya middleware-i `request()->attributes->set('integration_client', ...)`
qoyursa mənbə avtomatik `integration` olur.

### Jurnal mətnləri

`HandlesAssignment` `assigned` / `reassigned` / `released` hadisələrini
`ActivityLogger` ilə yazır. Model blokuna qəlib əlavə etmək kifayətdir:

```php
'Order' => [
    'title'      => 'Sifariş',
    'assigned'   => ':causer :order_number sifarişini öz üzərinə götürdü',
    'reassigned' => ':causer :order_number sifarişini :previous_assignee üzərindən götürdü',
    'released'   => ':causer :order_number sifarişini buraxdı',
],
```

Qəlib yoxdursa universal cümlə yazılır.

---

## 2. Domen jurnalı (`ActivityLogger`)

`LogsAdminActivity` model sahəsinin dəyişməsini tutur. «Status dəyişdi», «şəkil
silindi», «qeyd üzərinə götürüldü» kimi **domen hadisələri** isə servis öz axınında
yazır:

```php
use App\Enums\System\LogChannelEnum;
use App\Services\Activity\ActivityLogger;

ActivityLogger::on(LogChannelEnum::GOPANEL)      // və ya 'gopanel' - string də olar
    ->subject($order)
    ->event('status_changed')
    ->with(['from' => 'Yeni', 'to' => 'Təsdiqləndi'])
    ->write();

ActivityLogger::record('transactions', $payment, 'refunded', ['amount' => 25]);   // qısa yol
```

Bir çağırış iki yerə yazır və mesaj **eynidir**:

1. `activity_log` - log_name = model adı (və ya `group()`), subject, causer, `properties.context`;
2. `LogService` kanalı - `file_logs` + gündəlik fayl, kontekstdə `causer_name`, `subject_*`, `LogService::actorContext()`.

Mesaj `config/custom/activity_messages.php` → `{Model}.{event}` qəlibindən
`App\Support\Activity\ActivityMessage` ilə qurulur. Placeholder sırası: `:causer`,
`:changes`, sonra `with()` konteksti, sonra modelin atributu; həssas adlar `***`.

| Metod | Nə üçün |
|---|---|
| `changes($new, $old)` / `changesFromModel($model, $before)` | `:changes` - «başlıq: Köhnə → Yeni» |
| `group('Integration')` | qəlibi model adından yox, başqa qrupdan oxu |
| `level('warning')` | `config/custom/logging.php` → `levels` |
| `message('...')` | qəlibi keç (nadir) |
| `withDetails()` | adi səviyyədə də backtrace (xəta səviyyələrində onsuz da yazılır) |
| `withoutActivityLog()` | yalnız fayl jurnalı (texniki addım) |
| `ActivityLogger::forSubject($model)` | bu qeydin activity sətirləri (Builder) |

**`changesFromModel()` diqqəti:** `save()` köhnə dəyərləri silir (`syncOriginal`).
Köhnə dəyər `save()`-dan ƏVVƏL götürülür:

```php
$before = $order->getOriginal();
$order->update($data);
ActivityLogger::on('gopanel')->subject($order)->event('updated')->changesFromModel($order, $before)->write();
```

Zəmanətlər:

- jurnal xətası (yanlış səviyyə, DB yoxdur) çağıranı **dayandırmır** - `report()` olunur;
- trait-dən fərqli olaraq config bloku məcburi deyil - açıq çağırış açıq niyyətdir;
- subject `LogsAdminActivity` işlədirsə spatie onun `tapActivity()`-ni çağırır və
  təsviri model qəlibinə qaytarır - `ActivityLogger` öz mesajını və kontekstini geri yazır.

---

## 3. Tipli key-value tənzimləmələr

### Niyə

`site_settings` sütunlu cədvəldir: hər yeni tənzimləmə miqrasiya, model `fillable`,
forma və validasiya dəyişikliyi deməkdir. Key-value qatında tənzimləmə **config-də
bir blokdur** - forma, FormRequest qaydaları, cast və saxlama həmin blokdan qurulur.
Sətir yoxdursa `default` qüvvədədir: yeni açar üçün miqrasiya da, backfill də lazım deyil.

`SiteSetting` + `SiteSettingsController` **toxunulmayıb** - o, loqo/fayl/meta
saxlayır və mövcud layihələr ondan asılıdır. Key-value qatı yeni tənzimləmələr üçündür.

### Hissələr

| Sinif / fayl | Rolu |
|---|---|
| `App\Support\Settings\SettingDefinitions` (abstract) | tərif: `cast`, `toStorage`, `fromForm`, `validationRules`, `attributeLabels`, `formRows`, `groupedFormRows` |
| `App\Support\Settings\AdminSettingDefinitions` | `config/gopanel/admin_settings.php` (scope `admin`) |
| `App\Services\Settings\SettingsService` | oxu/yazı/keş - istənilən tərif dəsti, istəyə bağlı owner |
| `App\Services\Settings\AdminSettingsService` | admin üçün qısa yol (`all/get/saveMany/seedDefaults($admin)`) |
| `App\Models\Settings\SettingValue` | `setting_values` sətri (scope, owner_type, owner_id, key, value) |
| `App\Http\Requests\Gopanel\Admin\AdminSettingsRequest` | qaydalar tərifdən |
| `ProfileSettingsController` + `gopanel.pages.profile.settings` | Profil → «Şəxsi tənzimləmələr» |
| `gopanel.component.setting-fields` | istənilən tərif dəsti üçün forma sahələri |

### Tərif formatı

```php
'min_order' => [
    'type'    => 'int',            // bool | int | float | string | text | select | json
    'default' => 10,
    'label'   => 'Minimum sifariş',
    'hint'    => 'AZN ilə',
    'group'   => 'Satış',          // formada başlıq; default «Ümumi»
    'min'     => 1,                // int/float dəyər, string uzunluq sərhədi
    'max'     => 500,
    // 'rules'   => ['nullable', 'integer', 'between:1,500'],   // verilsə tipdən qurulanı ƏVƏZ edir
    // 'options' => ['AZN' => 'Manat'],                        // select üçün
],
```

| Tip | Cast | Default qayda | Input |
|---|---|---|---|
| `bool` | `true/false` | `nullable, boolean` | switch (hidden 0 + checkbox 1) |
| `int` / `float` | `int` / `float` | `nullable, integer\|numeric, min, max` | number |
| `string` | `string` | `nullable, string, max:255` | text |
| `text` | `string` | `nullable, string, max:5000` | textarea |
| `select` | `string` | `nullable, string, in:<options>` | select |
| `json` | massiv | `nullable, json` | textarea (JSON mətni) |

### İstifadə

```php
use App\Services\Settings\SettingsService;

// Adminin şəxsi tənzimləməsi
$on = app(AdminSettingsService::class)->get($adminId, 'notify_new_order');

// Öz tərif dəstin (qlobal - owner-siz)
final class ShopSettingDefinitions extends SettingDefinitions
{
    protected static function configKey(): string { return 'custom.shop_settings'; }
}

$shop = SettingsService::for(ShopSettingDefinitions::class);
$shop->get('min_order');                     // 10 (sətir yoxdursa default)
$shop->saveMany(['min_order' => 25]);        // tərifdə olmayan açar yazılmır
$shop->reset('min_order');                   // sətir silinir → default

// İstənilən model üçün (istifadəçi, filial...)
SettingsService::for(UserSettingDefinitions::class, $user)->all();
SettingsService::forOwner(UserSettingDefinitions::class, User::class, $userId);   // job yalnız id daşıyır
```

Öz panel ekranın üçün: FormRequest-də `ShopSettingDefinitions::validationRules('settings')`,
controller-də `groupedFormRows($service->all())`, blade-də
`@include('gopanel.component.setting-fields', ['groups' => $groups])` və saxlamada
`fromForm($validated['settings'])`. Nümunə: `ProfileSettingsController`.

### Keş

`(scope, owner)` üzrə bütün xam dəyərlər bir açarda (`CacheKey::settings(...)`).
Yazandan sonra `settings` qrupunun versiyası artırılır - `Cache::flush()` çağırılmır.
Request daxilində əlavə yaddaş keşi var; uzun işləyən command-də `SettingsService::flushMemo()`.

`SettingValue`-ni servisdən kənar birbaşa yazmaq keşi təmizləmir - yazı həmişə servisdən gedir.

### Panel

Profil → «Şəxsi tənzimləmələr» (`gopanel.profile.settings.index`). Hər admin yalnız
öz dəyərini dəyişir (route-da `{item}` yoxdur), ona görə ayrıca icazə yoxdur.
`config/gopanel/admin_settings.php` starter-də **boşdur** - boşdursa profil
səhifəsində link görünmür.

---

## 4. Kiçik köməkçilər

| Sinif | Nə edir | Config |
|---|---|---|
| `App\Enums\System\LogChannelEnum` | `config/logging.php` kanallarının güzgüsü; `LogChannelEnum::SMS->log()->info(...)`. String kanallar (`new LogService('sms')`) olduğu kimi işləyir | - |
| `App\Support\Geo\StaticMap` | email/bildiriş üçün statik xəritə şəkli + «xəritədə aç» linki + `payload()`; `0,0` və sərhəddən kənar koordinat «yer yoxdur» | `config/custom/map.php` (`STATIC_MAP_PROVIDER=none\|google\|geoapify`, `STATIC_MAP_KEY`) |
| `App\Support\Url\FrontendUrl` | ayrıca (headless) sayt ünvanı; `FRONTEND_URL` boşdursa `APP_URL`-in `api.` prefiksi atılır | `config/app.php` → `frontend_url` |
| `App\Support\Http\MobilePlatform` | UA-dan `ios` / `android` / `huawei` (mağaza düyməsi üçün) | - |
| `App\Enums\Common\PlatformEnum` | məzmun platforması `website` / `mobile` / `both`; `visibleOn()` süzgəci | - |

`StaticMap` default `none`-dır: açarsız ictimai statik xəritə servisləri bağlanıb -
açarsız provayder seçilsə email-də sınıq şəkil görünərdi.

---

## Köçürülməyənlər (və niyə)

| Mənbə | Səbəb |
|---|---|
| qrgate `SettingsService` (CommonService + `curreunt_page()`) | tənzimləmə qatı deyil, səhifə seqmenti oxuyur |
| Company/Employee setting variantları | layihəyə xas |
| texnotech `RendersHistoryModal` | `RecordHistoryQuery` + layihənin tarixçə modalından asılıdır; `assignment-history` komponenti ayrıca köçürülüb |
| texnotech bildiriş çağırışı (`notifyPrevious`) | starter-də bildiriş modulu yoxdur → `afterAssignment()` hook-u |
| `record_authors` miqrasiyasındakı məhsul backfill-i | layihəyə xas (`products` cədvəli) |
| qrgate `CustomLogInterface` | heç yerdə implement edilmir; `LogService` `__call` ilə işləyir |
| qrgate `RepeaterFieldHelper` | `Başlıq\|İzah` sətir formatı və qrgate-in köhnə tərcümə axınına bağlıdır; starter-də repeater UI yoxdur |
| qrgate `HasAnalyticsCells` | model accessor-unda HTML; starter-də hüceyrə qatı `RendersRichCells`-dir, CSS sinifləri (`gpd-badge-*`) burada yoxdur |
| qrgate `MobilePlatformHelper::isCrawler()` | `App\Support\Http\PreviewBotDetector` artıq var |
| aquastores `PlatformEnum` (web/mobile sorğu mənbəyi) | route prefiksinə (`api/mobile/*`) bağlıdır; texnotech-in məzmun variantı götürülüb |
