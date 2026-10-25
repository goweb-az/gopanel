# Gopanel-də Status və Tip CRUD-ları (lookup)

> **Əvvəlcə oxu:** dəyər dəsti **kod** tərəfindən idarə olunursa (developer
> deploy etmədən dəyişmirsə) o, CRUD deyil, **Enum** olmalıdır —
> `app/Enums/`. CRUD yalnız admin özü əlavə/sıralama/söndürmə edə bildikdə
> yazılır. Qərar qaydası:
> [application-layering...md §25](application-layering-support-query-traits-helpers-enums.md).

Kiçik sorğu cədvəlləri — sifariş statusları, ödəniş statusları, məhsul tipləri,
şikayət səbəbləri — adətən 5-10 sətirdir. Starter-də onlar üçün **hazır ortaq
qat** var: siyahı + bölmədaxili sidebar, modal forma, sürüklə-burax sıralama,
silmə qaydaları, icazə, menyu. Layihə yalnız **tipi əlavə edir**.

Starter-in özündə heç bir lookup tipi **yoxdur** (layihəyə xas iş məntiqi
starter-ə yazılmır) — reyestr boşdursa bölmə menyuda görünmür.

Vizual dizayn aquastores layihəsindəki `/gopanel/lookups/listing-statuses`
səhifəsindən götürülüb; oradakı qüsurlar (bax § 12) burada düzəldilib.

---

## 1. Yeni tip — 3 addım

```bash
# 1) Skelet: model, miqrasiya, controller, request, 2 blade, reyestr sətri
php artisan gopanel:make-lookup OrderStatus --title="Sifariş statusları" \
    --fields=color,class_name --booleans=is_default,is_public,is_active

# 2) Cədvəl
php artisan migrate

# 3) İcazələr (reyestrdən avtomatik) → sonra vəzifələrə ver
php artisan db:seed --class=PermissionSeeder
```

Route, icazə sətirləri, əsas menyu və bölmədaxili sidebar **ayrıca yazılmır** —
hamısı `config/gopanel/lookups.php` reyestrindəki bir sətirdən qurulur.

Generator seçimləri:

| Seçim | Standart | Mənası |
|---|---|---|
| `--title` | `Str::headline` | Paneldə görünən ad (Azərbaycan dilində ver) |
| `--fields` | — | `color`, `class_name`, `icon` (ortaq forma/siyahı bunları tanıyır) |
| `--booleans` | `is_active` | `is_` ilə başlayan bayraqlar; `is_default` xüsusi qaydalı |
| `--translated` | `name,description` | `field_translations`-a gedən sahələr |
| `--icon` | `bx bx-list-ul` | Bölmədaxili sidebar ikonu |
| `--force` / `--dry-run` | — | Üzərinə yaz / heç nə yazmadan göstər |

Komanda idempotentdir: mövcud fayl üzərinə yazılmır, reyestr sətri təkrar
əlavə olunmur, eyni cədvəlin ikinci miqrasiyası yaranmır.

Əl ilə də əlavə etmək olar: model + miqrasiya + `BaseLookupController` və
`BaseLookupSaveRequest` törəmələri + reyestr sətri. Blade faylı **məcburi
deyil** — tipin öz qovluğunda blade yoxdursa ortaq səhifə işlədilir.

İstifadə olunan tip üçün controller-də `$usedBy` doldur (bax § 7) — əks
halda istifadədə olan status silinə bilər.

---

## 2. Fayl xəritəsi

| Nə | Harada |
|---|---|
| Reyestr (tiplər, menyu adı) | `config/gopanel/lookups.php` |
| Reyestr oxuyucusu (route/icazə/menyu formatı) | `App\Services\Gopanel\Lookup\LookupRegistry` |
| Tip təsviri (sahələr, bayraqlar, etiketlər) | `App\Services\Gopanel\Lookup\LookupDefinition` |
| Yazma qaydaları (default, silmə, sıralama, keş) | `App\Services\Gopanel\Lookup\LookupService` |
| Tək sorğulu sıralama | `App\Services\Gopanel\SortOrderService` |
| Ortaq controller | `App\Http\Controllers\Gopanel\Lookups\BaseLookupController` |
| Bölmənin giriş nöqtəsi (ilk icazəli tipə yönləndirir) | `App\Http\Controllers\Gopanel\Lookups\LookupSectionController` |
| Forma validasiyası + add/edit icazəsi | `App\Http\Requests\Gopanel\Lookups\BaseLookupSaveRequest` |
| Sıralama / keçid sorğuları | `LookupSortRequest`, `LookupToggleRequest` (eyni qovluq) |
| Server tərəfi icazə middleware-i | `App\Http\Middleware\Gopanel\GopanelPermissionMiddleware` (`gopanel.can`) |
| Route-lar | `routes/gopanel/lookups.php` (`routes/gopanel.php` qoşur) |
| Blade | `resources/views/gopanel/pages/lookups/{index,form,sidebar}.blade.php`, `partials/{index,form}`, `inc/modal` |
| JS | `public/assets/gopanel/js/modules/lookups/lookups.js` |
| CSS | `public/assets/gopanel/css/lookups.css` |
| Generator | `App\Console\Commands\MakeLookupCommand` (`gopanel:make-lookup`) |
| Test | `tests/Feature/Gopanel/Lookups/LookupModuleTest.php` |

Generator-in yaratdığı (layihədə):

```text
app/Models/Lookups/OrderStatus.php
app/Http/Controllers/Gopanel/Lookups/OrderStatusController.php
app/Http/Requests/Gopanel/Lookups/OrderStatusSaveRequest.php
database/migrations/xxxx_create_order_statuses_table.php
resources/views/gopanel/pages/lookups/order-statuses/{index,form}.blade.php   (fərdiləşdirmə üçün; silinə bilər)
config/gopanel/lookups.php   ← 'order-statuses' sətri
```

---

## 3. Reyestr

```php
// config/gopanel/lookups.php
'types' => [
    'order-statuses' => [
        'title'      => 'Sifariş statusları',
        'controller' => \App\Http\Controllers\Gopanel\Lookups\OrderStatusController::class,
        'icon'       => 'bx bx-list-check',
    ],
    // gopanel:make-lookup:types   ← generator bu markerin üstünə yazır, silmə
],
```

Açar (`order-statuses`) kebab-case-dir və URL seqmenti, route və icazə adının
hissəsidir. Yanlış formatlı açar və ya controller-siz sətir sükutla ötürülür
(bir səhv sətir bütün paneli yıxmasın).

Reyestrdən qurulanlar:

- **route-lar** — § 5;
- **icazələr** — `permission_list.php`-ə hər tip üçün ayrıca qrup
  (`Status və Tiplər: Sifariş statusları`) qoşulur;
- **əsas sol menyu** — «Status və Tiplər» qrupu, hər tip öz `index` icazəsi
  ilə alt sətir; reyestr boşdursa qrup ümumiyyətlə yoxdur, admin heç bir
  tipi görə bilmirsə qrup gizlənir;
- **bölmədaxili sidebar** — yalnız adminin `index` icazəsi olan tiplər.

---

## 4. Database strukturu

```php
Schema::create('order_statuses', function (Blueprint $table) {
    $table->id();
    $table->uuid('uid')->unique();
    $table->string('key', 50)->unique();
    $table->string('color', 30)->nullable();        // --fields=color
    $table->string('class_name', 100)->nullable();  // --fields=class_name
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_default')->default(false);
    $table->boolean('is_public')->default(false);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

| Sahə | Məqsəd |
|---|---|
| `uid` | URL və DOM-dakı identifikator (`data-id`) — daxili `id` açılmır |
| `key` | Kod və API üçün dəyişməz texniki açar (`^[a-z][a-z0-9_]*$`, unikal) |
| `sort_order` | Göstərilmə sırası. Yeni sətir avtomatik **sona** düşür (`BaseModel`) |
| `color` / `class_name` | Badge önizləməsi (siyahıda görünür) |
| `icon` | İkon sinfi — formada ikon seçici (`IconPickerHelper`) |
| `is_default` | Yeni obyekt üçün başlanğıc dəyər — **yalnız bir** sətir |
| `is_public` | Son istifadəçiyə göstərilə bilər |
| `is_active` | Yeni seçimlərdə istifadə oluna bilər |
| `deleted_at` | Silmə = arxivləmə (soft-delete) |

`name` / `description` `field_translations`-dadır (`Translation` trait).

Model: `BaseModel` + `AddUuid` + `SoftDeletes` + `Translation`;
`scopeActive()`, `scopePublic()`, `scopeOrdered()` generator tərəfindən
yazılır. Admin seçimi ilə public API sorğusu eyni deyil:

```php
OrderStatus::active()->ordered()->get();             // admin daxili seçim
OrderStatus::active()->public()->ordered()->get();   // istifadəçi API-si
```

`BaseModel::boot()` — `sort_order` fillable-dadırsa və dəyər verilməyibsə
(`null`) yeni sətrə `max + 1` yazır. Açıq verilən dəyər (0 daxil) toxunulmur.

---

## 5. Route-lar və icazələr

Hər tip eyni 6 route-u alır (`routes/gopanel/lookups.php`):

| Metod | URL (`/gopanel/lookups/{tip}/...`) | Ad (`gopanel.lookups.{tip}.`) | Server icazəsi |
|---|---|---|---|
| GET | `/` | `index` | `index` |
| GET | `/get/form/{id?}` | `get.form` | `add` və ya `edit` |
| POST | `/save/{id?}` | `save` | `add` və ya `edit` |
| DELETE | `/delete/{id}` | `destroy` | `delete` |
| PATCH | `/sort` | `sort` | `sort` |
| POST | `/toggle` | `toggle` | `edit` |

Əlavə: `GET /gopanel/lookups` (`gopanel.lookups.index`) — adminin görə
bildiyi ilk tipə yönləndirir.

İcazə adları: `gopanel.lookups.{tip}.index|add|edit|delete|sort` — route adı
ilə **eynidir** (01-umumi.md § 5). Yoxlama üç səviyyədədir:

1. **Route** — `gopanel.can:a,b` middleware-i («hər hansı biri», Spatie,
   `gopanel` guard). Əsas müdafiə budur.
2. **Controller / FormRequest** — forma və saxlama route-u `add|edit` ilə
   açılır; konkret əməliyyat (yeni → `add`, mövcud → `edit`) burada
   yoxlanılır. Yalnız `add` icazəsi olan admin mövcud sətri redaktə edə bilmir.
3. **Blade** — `@can($definition->permission('add'))` düymələri gizlədir.

İcazə xətası 403 qaytarır (`AccessDeniedHttpException`).

---

## 6. Controller

```php
class OrderStatusController extends BaseLookupController
{
    protected string $model       = OrderStatus::class;
    protected string $request     = OrderStatusSaveRequest::class;
    protected array  $extraFields = ['color', 'class_name'];
    protected array  $booleans    = ['is_default', 'is_public', 'is_active'];
    protected array  $usedBy      = ['orders' => 'order_status_id'];
}
```

Tip açarı, başlıq, route və icazə adları **yazılmır** — cari route-dan və
reyestrdən çıxarılır. Genişləndirmə nöqtələri:

| Nə | Necə |
|---|---|
| Başlığı reyestrdən fərqli et | `protected ?string $title` |
| Öz blade qovluğu | `protected ?string $viewPath` (standart: `gopanel.pages.lookups.{tip}`; yoxdursa ortaq) |
| Mürəkkəb istifadə sayı (polimorf, JSON) | `protected function inUseCount(Model $item): int` override |
| Formaya əlavə dəyişən (select siyahısı) | `protected function formData(Model $item): array` override |

Cavab formatı panelin müqaviləsidir (`crud.js`): `{status, message, data}`;
yaratma `201`, qalanı `200`; biznes qaydası və validasiya `422`
(`errors` ilə); tapılmadı `404`; icazə `403`.

## 7. Request

```php
class OrderStatusSaveRequest extends BaseLookupSaveRequest
{
    protected string $model = OrderStatus::class;

    protected function extraRules(): array
    {
        return ['price' => ['nullable', 'numeric', 'min:0']];
    }
}
```

Ortaq qaydalar: `key` formatı + unikallıq (redaktədə özü istisna), `name`
ən azı standart dildə (`TranslatedRequired`), tərcümə massivlərində yalnız
**aktiv dil kodları**, `color` `#RRGGBB`, `class_name`/`icon` yalnız
`[A-Za-z0-9 _-]`. Tərcümə sahələri modelin `$translatedAttributes`-dən gəlir —
iki siyahı saxlanmır.

---

## 8. Biznes qaydaları (`LookupService`)

### Yalnız bir default

- Sətir default seçiləndə qalanlarının `is_default`-u **eyni tranzaksiyada**
  sıfırlanır.
- Default sətir avtomatik aktivdir; siyahıdan da deaktiv edilə bilməz.
- Default-dan əl ilə çıxarmaq olmaz — yalnız başqası default seçilərək
  (ən azı bir default qalır). Hər iki hal `422`.

### Silmə

- Silmə = soft-delete (arxiv).
- Default sətir silinmir.
- İstifadə olunan sətir silinmir: `$usedBy` (`cədvəl => xarici açar`) üzrə
  sayılır; soft-delete olunmuş istinadlar da sayılır. Mesaj adminə «deaktiv
  edin» təklif edir.

### Sıralama

- Bütün sıra **bir HTTP sorğusu**, **bir `UPDATE ... CASE`** sorğusu, bir
  tranzaksiya ilə yazılır (`SortOrderService`).
- Tanınmayan / silinmiş / təkrarlanan `uid` varsa heç nə yazılmır (`422`).
- Sətir başına model event-i işləmir; keş sonda **bir dəfə** təmizlənir.

### Keş

Hər yazmadan (save / toggle / delete / sort) sonra `lookup` keş qrupu
`CacheKey::flushGroup(CacheKey::GROUP_LOOKUP)` ilə köhnəlir. Saytda lookup
oxuyan yer açarı `CacheKey::lookup('order-statuses')` ilə qurmalıdır.
`Cache::flush()` çağırılmır.

---

## 9. Ekran

```text
┌──────────────────────┬──────────────────────────────────────────────┐
│ Status və Tiplər     │ Sifariş statusları                [+ Əlavə]  │
│                      │                                              │
│ Sifariş statusları ◄ │  ↕  Açar      Ad        Görünüş  Default  Status  ✎ 🗑 │
│ Ödəniş statusları    │  ↕  pending   Gözləyir  [badge]  Bəli     [on]       │
│ Məhsul tipləri       │  ↕  approved  Təsdiq    [badge]  Xeyr     [on]       │
└──────────────────────┴──────────────────────────────────────────────┘
```

- Sütunlar `LookupDefinition`-dan gəlir: `color`/`class_name` varsa
  «Görünüş» (badge önizləməsi, rəng `--lookup-color` CSS dəyişəni ilə),
  `icon` varsa ikon, `is_active`-dən başqa bayraqlar «Bəli/Xeyr» badge,
  `is_active` keçid düyməsi (`edit` icazəsi yoxdursa sadə badge).
- Desktop: sidebar `col-xl-3`, cədvəl `col-xl-9`. Mobil: sidebar cədvəlin
  üstündə üfüqi sürüşən zolaq, cədvəl `table-responsive`.
- Yeni sətirdə yalnız `is_active` açıq gəlir; `is_default`/`is_public` bağlı.
- Blade-də hesablama, `<style>`, `<script>` yoxdur — dəyərlər `data-*` ilə.

## 10. JavaScript

Yaratma/redaktə **mövcud `crud.js` axını** ilədir (`#open-create-modal`,
`.edit`, `#save-form-btn`, modal `#cerate-modal`). Modal id-sinin yazılışı
crud.js-in müqaviləsidir — düzəltmək bütün panelin modallarını sındırar.

`modules/lookups/lookups.js` yalnız:

1. sürüklə-burax (jQuery UI sortable, yalnız `.lookup-drag-handle` ilə;
   sorğu gedərkən bloklanır; xətada sətirlər əvvəlki yerinə qayıdır);
2. silmə təsdiqi (SweetAlert2) və `DELETE` — xətada server mesajı göstərilir;
3. formadakı rəng seçicisinin mətn sahəsi ilə sinxronu;
4. keçid düyməsini server rədd edəndə səhifəni yeniləyir (real vəziyyət görünsün).

Tipə xas JS lazımdırsa `modules/lookups/<tip>.js` yaradılır və tipin öz
`index.blade.php`-sində `@push('scripts')` ilə qoşulur — ortaq fayla yazılmır.

---

## 11. Test

`tests/Feature/Gopanel/Lookups/LookupModuleTest.php` atılan `test_statuses`
lookup-u üzərində (yaddaşda SQLite) yoxlayır: index render, tərcüməli
saxlama, validasiya (açar, standart dil, dil whitelist-i, rəng), tək default
qaydası, bir sorğulu sıralama + rollback, icazəsiz admin üçün bütün
endpoint-lərdə 403, silmə qadağaları, reyestr → icazə/menyu, generator.

Layihədə yeni tip əlavə edəndə adətən əlavə test lazım deyil; tipə xas qayda
(`inUseCount()`, `extraRules()`) yazılıbsa onun üçün test yazılır.

## 12. aquastores-dan fərqlər (düzəldilmiş qüsurlar)

| aquastores | Starter |
|---|---|
| `@can('gopanel.lookups.add')` — mövcud olmayan icazə | Tip başına `gopanel.lookups.{tip}.*`, route adı = icazə adı |
| Server tərəfi icazə yox idi | `gopanel.can` hər route-da + FormRequest-də add/edit |
| Formada dillər `code != 'az'` ilə sabit, `Currency`/`PortfolioCategory` controller-də | Aktiv dillər paylaşılan siyahıdan; biznes modeli yoxdur, `formData()` hook-u |
| `is_default` tək saxlanmırdı | Tranzaksiyada tək default, deaktiv/silinmə qadağası |
| Silmə istifadəni yoxlamırdı | `$usedBy` / `inUseCount()` hook-u |
| Sıralama: N `find()` + N `update()`, tranzaksiyasız, N keş təmizlənməsi | 1 `UPDATE ... CASE`, tranzaksiya, 1 keş təmizlənməsi |
| Menyu controller-də sabit massiv | `config/gopanel/lookups.php` reyestri |
| `@push('styles')` içində inline CSS | `css/lookups.css` |
| Hər yeni sətirdə bütün checkbox-lar açıq | Yalnız `is_active` açıq |

## 13. Qəbul meyarları

- Reyestr boşdursa bölmə menyuda görünmür; tip əlavə olunanda «Status və
  Tiplər» qrupu və bölmədaxili sidebar avtomatik görünür.
- Create/edit AJAX modal ilə, validasiya xətaları göstərilir.
- Sürüklə-burax qalıcıdır və xətada geri qayıdır.
- Default/silmə qaydaları serverdə tətbiq olunur.
- İcazəsiz admin heç bir endpoint-i çağıra bilmir (403).
- Yeni tip 3 addımda əlavə olunur (§ 1).
