# GoPanel — AI inkişaf bələdçisi

> Bu sənəd layihənin strukturunu, konvensiyalarını və mövcud abstraksiyalarını izah edir. Yeni xüsusiyyət əlavə etməzdən əvvəl bu sənədi oxu — yenidən kəşf etməyə ehtiyac yoxdur.

## 1. Texnologiya stack

- **Laravel** (PHP 8.1)
- **MySQL** — UUID stunları üçün `DB::raw('UUID()')` default
- **Bootstrap 5** + **jQuery** + **jQuery UI** (sortable)
- **Spatie/laravel-activitylog** — admin əməliyyatlarının izlənməsi
- **Spatie/laravel-permission** — rol və icazələr
- **Magnific Popup** — şəkil lightbox-u (`public/assets/gopanel/libs/magnific-popup`)
- **CKEditor 4** Classic — HTML zəngin redaktor (`public/assets/gopanel/libs/ckeditor`)
- **DataTables** (server-side) — AJAX cədvəllər
- **Select2**, **Toastr**, **SweetAlert2**, **bootstrap-tagsinput**, **bootstrap-switch-button**

## 2. Qovluq strukturu

```
app/
  Datatable/Gopanel/        # Server-side datatable sinifləri (BlogDatatable, ProductDatatable, ...)
  Enums/                    # Enum siniflər (SocialIconTypeEnum, ...)
  Helpers/Common/           # Ümumi helper-lər (ActivityLogHelper, ...)
  Helpers/Gopanel/          # Admin panel helper-ləri (FileUploader, CrudHelper, GoPanelHelper, IconPickerHelper, TranslationHelper)
  Helpers/Gopanel/Site/     # Sayt-istiqamətli helper-lər (PageMetaDataHelper, GoPanelSiteHelper)
  Http/Controllers/Gopanel/ # Admin controller-ləri
  Models/                   # BaseModel + alt qovluqlar
    Site/                   # Blog, Service, Product, Slider, AboutUs
    Navigation/             # Category, Menu
    Translations/           # FieldTranslation, Language
    Seo/                    # PageMetaData, SiteRedirect
    Gopanel/                # Admin, CustomRole, CustomPermission
  Traits/
    System/                 # AddUuid, HasRouteKey, HasArchive, Cacheable
    Content/                # Translation, HasFiles, MetaData
    Activity/               # LogsAdminActivity
    Ui/                     # UiElements, FormatsDate

config/gopanel/             # Permission siyahısı, sidebar menyusu, FA ikon adları
database/migrations/        # Cədvəllərin yaradılması
database/seeders/           # DatabaseSeeder + alt seeder-lər
database/seeders/mock/      # Test data-sı üçün mock seeder-lər (DatabaseSeeder-də qeyd EDİLMƏYİB, manual çağrılır)

resources/views/gopanel/
  layouts/main.blade.php    # Əsas layout
  blocks/                   # head, sidebar, header, footer
  assets/scripts.blade.php  # JS girişi
  assets/styles.blade.php   # CSS girişi
  component/                # Yenidən istifadə edilən komponentlər (datatable, meta, icon-picker-modal)
  pages/<module>/           # Hər modulun səhifələri
    index.blade.php
    store.blade.php         (full-page form pattern üçün)
    inc/modal.blade.php     (modal pattern üçün)
    partials/form.blade.php

public/assets/gopanel/
  js/main.js                # Global init, lightbox, sortable, datatable UI
  js/functions.js           # pageLoader, elementLoader, basicAlert, showError
  js/crud.js                # Modal & static form submit, edit/create handlers
  js/initDatatable.js       # DataTables AJAX init
  js/modules/               # Modul-spesifik JS (services.js, categories.js, products.js)
  libs/                     # Vendor JS/CSS

routes/gopanel.php          # Admin marşrutları
tests/Unit/                 # Modul testləri
```

## 3. BaseModel və trait-lər

Bütün domen modeller `App\Models\BaseModel`-i extend edir. Trait-lər **dörd qovluğa** bölünüb:

```
app/Traits/
├── System/      → model identity və lifecycle (AddUuid, HasRouteKey, HasArchive, Cacheable)
├── Content/     → məzmun sahələri (Translation, HasFiles, MetaData)
├── Activity/    → audit/log (LogsAdminActivity)
└── Ui/          → presentation helper-ləri (UiElements, FormatsDate)
```

### BaseModel-də avtomatik (bütün modellərə miras keçir)

- **`App\Traits\System\HasRouteKey`** — `{item:uid?}` route binding-i; `getIdentifierIdAttribute` (uid varsa uid, yoxsa id), `resolveByKey($value)`
- **`App\Traits\Content\HasFiles`** — `protected $files = ['image']` təyin edən modellərdə avtomatik:
  - `$model->image_url` → `public/site/.../file.png` URL-i
  - `$model->image_view` → `<a class="image-lightbox"><img></a>` (lightbox-a bağlı) və ya `Fayla bax` linki
- **`App\Traits\Activity\LogsAdminActivity`** — Spatie ActivityLog inteqrasiyası. `$logEnabled = false` default; `true` + `config/custom/activity_messages.php` açarı olsa log yazılır
- **`App\Traits\System\Cacheable`** — model üçün cache helper-ləri (aşağıda)

### Modelə əlavə qoşula bilən trait-lər

- **`App\Traits\Content\Translation`** — multi-lang sahələr `field_translations` cədvəlində saxlanır. `public $translatedAttributes = ['title', 'description', 'slug']`. Avtomatik:
  - `$model->title` → cari locale tərcüməsi
  - `$model->getTranslation('title', 'az', true)` → fallback ilə
  - Global scope `with('translations')` avtomatik eager-load (yalnız translatedAttributes təyin edilibsə)
  - `delete` zamanı cascade
- **`App\Traits\Content\MetaData`** — `page_meta_data` ilə morphOne əlaqəsi. SEO meta üçün
- **`App\Traits\System\AddUuid`** — `creating` event-də uid avtomatik dolur. `findByUid($uid)` static metodu
- **`App\Traits\System\HasArchive`** — `archived_at` ilə soft archive (silmədən fərqli)
- **`App\Traits\Ui\UiElements`** — admin UI render helper-ləri (`is_active_btn`, `toggle_btn`, `editBtn`, `deleteBtn`, badge accessor-ları)
- **`App\Traits\Ui\FormatsDate`** — `created_at_formatted`, `updated_at_formatted` və s. accessor-ları (Carbon ilə az locale)

### Cacheable trait API

`App\Traits\System\Cacheable` BaseModel-ə avtomatik qoşulduğundan **bütün modellərdə** istifadə oluna bilər:

```php
// Filterli + sıralanmış bütün rekordlar (default 5 gün cache)
$products = Product::getCachedAll();

// Custom TTL
$products = Product::getCachedAll(now()->addHour());

// Forever cache (yalnız flushCache çağrılana qədər)
$slides = Slider::getCachedForever();

// Single record by primary key
$product = Product::getCachedFirst(12);

// Single record by any column
$blog = Blog::getCachedBy('slug', 'my-post');

// Generic remember wrapper (custom suffix + callback)
$top = Product::remember('top-rated', fn () => Product::orderBy('rating', 'desc')->take(5)->get());

// Manual cache flush (saved/deleted hook avtomatik flush edir)
Product::flushCache();
```

**Filtrlər** (`getCachedAll` / `getCachedForever`):
- `is_active = true` (fillable-da varsa)
- `status = true` (fillable-da varsa)
- `slug` translatedAttributes-da varsa, slug dolu rekordlar
- Sıralama: `order` fillable-da varsa ASC, yoxsa `id` DESC

**Cache açarı format**: `cache.{table}.{suffix}` (məs. `cache.products.all`, `cache.products.first.12`). Override etmək üçün modeldə `cacheKeyPrefix()` və `cacheTtl()`-i yenidən təyin et.

**Auto-invalidation**: `bootCacheable()` `saved` və `deleted` event-lərində avtomatik `flushCache()` çağırır. `getCachedFirst($id)` və `getCachedBy($col, $val)` per-key cache-lənir; bunlar TTL bitənə qədər qalır (cache driver tag dəstəkləməzsə manual flush mümkün deyil).

**Override** — özünəxas filter/sort istəyirsənsə model-də metodu yenidən təyin et (Language nümunəsi):
```php
public static function getCachedAll(DateTimeInterface|DateInterval|int|null $ttl = null): Collection {
    return Cache::remember("site_languages", $ttl ?? now()->addDays(5),
        fn () => self::where('is_active', true)->orderByDesc('default')->orderBy('sort_order')->get()
    );
}
```

### Slug konvensiyası

`Translation` istifadə edən modeldə:
```php
public $translatedAttributes = ['title', 'description', 'slug'];
public $slug_key = 'title';   // hansı sahədən slug üretilməlidir
```
`TranslationHelper::create()` slug-ı boş olduqda `$slug_key` sahəsindən avtomatik üretir.

### Modelə nümunə

```php
class Product extends BaseModel
{
    use HasFactory, SoftDeletes, Translation, MetaData, AddUuid;

    protected $table = 'products';
    protected $logEnabled = false;
    protected $fillable = ['uid','price','discount','image','is_active'];
    protected $casts = ['price'=>'decimal:2','is_active'=>'boolean'];
    protected $files = ['image'];                                     // _url + _view accessor-ları
    public $translatedAttributes = ['title','short_description','description','slug'];
    public $slug_key = 'title';

    // Datatable üçün custom view accessor-ları
    public function getImageViewAttribute(): string { ... }
    public function getIsActiveBtnAttribute(): string {
        return app('gopanel')->toggle_btn($this, 'is_active', $this->is_active == 1);
    }
}
```

## 4. Controller naxışları

İki konvensiya mövcuddur — modulun ölçüsünə görə seçilir:

### 4.1 Modal naxışı (kiçik formalar — Service, Category, Slider)

```php
public function index(Request $request)
{
    $items = Service::orderBy('sort_order')->get();
    $modelKey = Service::class;
    return view('gopanel.pages.services.index', compact('items','modelKey'));
}

public function getForm(Service $item, Request $request)
{
    $route = route('gopanel.services.save', $item);
    $this->response['html'] = View::make('gopanel.pages.services.partials.form', compact('item','route'))->render();
    $this->success_response([], 'Form yaradıldı');
    return $this->response_json();
}

public function save(Service $item, Request $request)
{
    $data = $request->except(['_token','meta']);
    if ($request->hasFile('image')) {
        $fileName = FileUploader::nameGenerate($data, 'service');
        $data['image'] = FileUploader::toPublic($request->file('image'), $item->getTable(), $fileName);
    }
    $item = $this->crudHelper->saveInstance($item, $data);
    if (isset($item->id)) {
        TranslationHelper::create($item, $request);
        PageMetaDataHelper::save($item, $request->input('meta', []), $request->file('meta', []));
    }
    $this->success_response($item, 'Saxlanıldı');
    return $this->response_json();
}
```
- Form `id="data-form"` → `crud.js` modal axını (`#open-create-modal` + `.edit` triggerlər)
- Save modal-ı bağlayır + datatable reload edir (redirect yoxdursa)

### 4.2 Full-page naxışı (geniş formalar — Blog, Product, Menu)

```php
public function index(Request $request)
{
    return view('gopanel.pages.products.index');
}

public function store(Product $item, Request $request)
{
    $item = is_null($item->id) ? new Product() : $item;
    $route = route('gopanel.products.save', $item);
    return view('gopanel.pages.products.store', compact('item','route'));
}

public function save(Product $item, Request $request)
{
    // ... eyni save məntiqi ...
    $this->response['redirect'] = isset($item->id) ? route('gopanel.products.index') : false;
    $this->success_response($item, 'Saxlanıldı');
    return $this->response_json();
}
```
- Form `id="static-form"` → `crud.js` static-form axını (səhifə submit, response-da redirect varsa pageLoader göstərir və yönləndirir)
- Edit səhifəsinə `.edit` linki ilə deyil, datatable-dən birbaşa URL ilə keçilir

### 4.3 GoPanelController bazası

```php
class GoPanelController extends Controller
{
    public SiteService $siteService;
    public GoPanelHelper $gopanelHelper;
    public CrudHelper $crudHelper;

    public function __construct() {
        parent::__construct();
        $this->response['redirect'] = false;
    }
}
```
Hər controller `parent::__construct()` çağırmalıdır. `$this->response`, `$this->success_response()`, `$this->response_json()` Controller-də mövcuddur.

### 4.4 UID-ə əsasən route binding

URL-də integer ID açıqlamaq istəmirsənsə, save route-da:
```php
Route::post('/save/{item:uid?}', [ProductController::class, 'save']);
```
Form action-ı avtomatik `$model->uid` ilə yaradılır.

## 5. View naxışları

### 5.1 Index — Datatable (AJAX)

```blade
@extends('gopanel.layouts.main')
@section('content')
    @include('gopanel.component.datatable',[
        '__datatableName' => 'gopanel.product',  // → App\Datatable\Gopanel\ProductDatatable
        '__datatableId'   => 'products'
    ])
@endsection
```

### 5.2 Index — Sortable cədvəl (kiçik siyahılar üçün, drag-drop sıralama)

```blade
<tbody class="sortable"
       data-key="{{ $modelKey }}"
       data-row="sort_order"
       data-url="{{ route('gopanel.general.sortable') }}">
    @foreach($items as $item)
    <tr id="item_{{ $item->id }}">
        <td style="cursor:grab"><i class="fas fa-grip-vertical"></i></td>
        ...
    </tr>
    @endforeach
</tbody>
```
`main.js`-dəki `sortupdate` handler avtomatik POST edir → `gopanel.general.sortable` endpoint-i sıralayır.

### 5.3 Form — multilang tab-lar

```blade
<form id="static-form" action="{{$route}}" enctype="multipart/form-data">
    <ul class="nav nav-tabs">
        @foreach ($languages as $lang)
            <li><a data-bs-toggle="tab" href="#lang_key_{{$lang->code}}">{{$lang->upper_code}}</a></li>
        @endforeach
    </ul>
    <div class="tab-content">
        @foreach ($languages as $lang)
        <div class="tab-pane" id="lang_key_{{$lang->code}}">
            <input name="title[{{$lang->code}}]" value="{{$item->getTranslation('title', $lang->code, true)}}">
            <textarea class="ckeditor" name="description[{{$lang->code}}]">{{$item->getTranslation('description', $lang->code, true)}}</textarea>
            @include("gopanel.component.meta", ['lang' => $lang->code, 'open' => true])
        </div>
        @endforeach
    </div>
    ...
</form>
```
`$languages` view composer vasitəsilə (ViewServiceProvider) bütün view-lara avtomatik ötürülür.

## 6. Hazır komponentlər (`resources/views/gopanel/component/`)

| Komponent | Param | İzah |
|---|---|---|
| `gopanel.component.datatable` | `__datatableName`, `__datatableId` | AJAX server-side datatable wrapper |
| `gopanel.component.meta` | `lang`, `open` (default true) | SEO meta (title, description, keywords, image) per locale |
| `gopanel.component.icon-picker-modal` | — | Global ikon picker modalı; `data-icon-picker-target="#input"` ilə tetiklənir |

## 7. Helper-lər (`App\Helpers\Gopanel\`)

### FileUploader

```php
// 1. Fayl adı yarat
$fileName = FileUploader::nameGenerate($request->all(), 'product'); // → product-meqlerdo-65...

// 2. public/site/{table}/ qovluğuna yüklə
$path = FileUploader::toPublic($request->file('image'), $item->getTable(), $fileName);
// $path = "site/products/product-meqlerdo-65....png"
```
`nameGenerate()` `title`, `name`, `slug`, `heading`, `label` açarlarını (multi-dil və ya scalar) yoxlayır.

### TranslationHelper

```php
TranslationHelper::create($item, $request);    // bütün $translatedAttributes-i request-dən yazır
TranslationHelper::basic($item, ['az'=>'..','en'=>'..'], 'title');  // bir sahə üçün manual yazma (seeder-də)
```
`slug` sahəsi varsa və boş gəlsə, `$model->slug_key` sahəsindən avtomatik üretir.

### PageMetaDataHelper

```php
PageMetaDataHelper::save($item, $request->input('meta', []), $request->file('meta', []));
```
`meta[title][az]`, `meta[description][az]`, `meta[keywords][az]`, `meta[image][az]` formatında inputları gözləyir.

### GoPanelHelper (`app('gopanel')`)

```php
{!! app('gopanel')->toggle_btn($model, 'is_active', $model->is_active == 1) !!}
{!! app('gopanel')->is_active_btn($model, 'is_active', $checked) !!}
```
HTML toggle düyməsi qaytarır; `gopanel.general.status.change` endpoint-inə bağlı.

### CrudHelper

```php
$item = $this->crudHelper->saveInstance($item, $data);
```
Mass assignment + save. Yeni və ya mövcud instance-i idarə edir.

### IconPickerHelper

```php
IconPickerHelper::all();         // ['fa'=>[...], 'bx'=>[...], 'mdi'=>[...], 'drp'=>[...]]
IconPickerHelper::flushCache();  // 7 günlük cache-i silmək üçün
```
FA brand/regular klassifikasiyası `config/gopanel/font_awesome_icons.php`-dədir.

## 8. JS axınları

### 8.1 crud.js — form submit handler-ləri

| Element | Hadisə | İş |
|---|---|---|
| `#open-create-modal` (button) | click | `data-route` → AJAX form yüklə → modal aç |
| `.edit` (link) | click | `href` → AJAX form yüklə → modal aç |
| `#save-form-btn` (button) | click | `#data-form`-u submit et → response.redirect varsa yönləndir, yoxsa modal bağla + datatable reload |
| `#static-form` | submit | Səhifə form submit → response.redirect varsa pageLoader + yönləndir |

### 8.2 main.js — Global UI

- **`initFormUiElements(scope)`** — select2, tagsInput, meta collapse, datatable UI, lightbox-u initialize edir. Document.ready-də işə düşür və `#form-wrap` daxilində MutationObserver vasitəsilə dinamik enjekte olunan formalarda təkrar çağrılır.
- **`initDatatableUiElements()`** — DataTables-in hər drawCallback / initComplete event-ində çağrılır. Bootstrap switch-ləri, tooltips, lightbox-u init edir.
- **`initImageLightbox(scope)`** — `<a class="image-lightbox">` selektorunu magnific-popup-a bağlayır.
- **`pageLoader(1, text)`** — tam ekran spinner overlay göstərir
- **`elementLoader(selector, true|false)`** — element üzərində kiçik loader
- **`toastr.success(msg)`**, **`basicAlert(msg, status)`**, **`showError(xhr)`** — bildirişlər

### 8.3 Sortable

`<tbody class="sortable" data-key=".." data-row=".." data-url="..">` tbody-də element sürüşdürüldükdə `sortupdate` event-i `gopanel.general.sortable`-ə POST göndərir. Avtomatik işləyir.

### 8.4 Modul JS-ləri

Modul-spesifik script `public/assets/gopanel/js/modules/<module>.js`-də saxlanır və `@push('scripts')` vasitəsilə daxil edilir:

```blade
@push('scripts')
    <script src="{{ asset('/assets/gopanel/libs/ckeditor/ckeditor.js') }}"></script>
    <script src="{{ asset('/assets/gopanel/js/modules/products.js?v=' . time()) }}"></script>
@endpush
```

## 9. Datatable yaratmaq

```php
// app/Datatable/Gopanel/ProductDatatable.php
class ProductDatatable extends GopanelDatatable
{
    public function __construct() {
        parent::__construct(Product::class, [
            'image_view' => 'Şəkil',
            'title'      => 'Başlıq',
            'is_active_btn' => 'Status',
        ], [
            'actions' => [
                'title' => 'Əməliyyatlar',
                'type'  => 'callable',
                'view'  => fn ($item) => $this->itemActions($item),
            ],
        ]);
    }
    protected function query(): Builder { return $this->baseQueryScope(); }
}
```
- Sütun açarları model-də mövcud sahə və ya accessor olmalıdır
- `_view` / `_btn` suffiksli sütunlar HTML render edir (escape edilmir DataTables tərəfdən)
- Görünüş aliase: `gopanel.product` (route param) → `App\Datatable\Gopanel\ProductDatatable` (`Product` + `Datatable`)

## 10. Permission və sidebar

Hər modul üçün:

```php
// config/gopanel/permission_list.php
'Məhsullar' => [
    ['name' => 'gopanel.products.index', 'title' => 'Məhsullar siyahısı'],
    ['name' => 'gopanel.products.add',   'title' => 'Məhsul əlavə etmə'],
    ['name' => 'gopanel.products.edit',  'title' => 'Məhsul redaktə'],
    ['name' => 'gopanel.products.delete','title' => 'Məhsul silmə'],
],
```

```php
// config/gopanel/sidebar_menu_list.php
[
    'icon'  => '<i class="bx bx-package"></i>',
    'title' => 'Məhsullar',
    'route' => 'gopanel.products.index',
    'can'   => 'gopanel.products.index',
],
```

Sonra `php artisan db:seed --class="Database\Seeders\PermissionSeeder"` yenidən qaçır → Super Admin rolu yenilənir, blade `@can` direktivləri işə düşür.

## 11. Mock seeder yazma

Mövcud seeder-lər `database/seeders/mock/`-də. **DatabaseSeeder-də qeyd EDİLMƏYİB** — manual çağrılır:

```bash
php artisan db:seed --class="Database\Seeders\mock\ProductSeeder"
```

Şablon: `CreatesPlaceholderImages` trait + `TranslationHelper::basic()` + `PageMetaData::updateOrCreate()`. Mövcud rekordu titulundan tapıb update edir, yoxsa yeni yaradır (idempotent).

```php
class ProductSeeder extends Seeder {
    use CreatesPlaceholderImages;

    public function run(): void {
        $items = [['title'=>['az'=>'...'],'image'=>$this->placeholderImage('products','Label',800,800)], ...];
        foreach ($items as $data) {
            $product = $this->findProductByTitle($data['title']['az']) ?? Product::create($payload);
            TranslationHelper::basic($product, $data['title'], 'title');
            // ...
        }
    }
}
```

## 12. Marşrut konvensiyaları

`routes/gopanel.php` daxilində bütün admin route-ları `gopanel` middleware altındadır:

```php
Route::group(['middleware' => 'gopanel'], function () {
    Route::prefix('products')->name("products.")->group(function () {
        Route::get('/',                  [ProductController::class, 'index'])->name('index');
        Route::get('/store/{item?}',     [ProductController::class, 'store'])->name('store');     // full-page edit
        Route::get('/get/form/{item?}',  [ProductController::class, 'getForm'])->name('get.form'); // modal pattern
        Route::post('/save/{item:uid?}', [ProductController::class, 'save'])->name('save');
    });
});
```

### Mövcud ümumi endpoint-lər (`gopanel.general.*`)

- `POST /general/sortable` — sortable cədvəl üçün
- `POST /general/delete/{id?}` — silmə (data-key model class göndərilməlidir)
- `POST /general/status/change` — toggle düyməsi üçün
- `POST /general/editable/{id?}` — inline editable hücrələr (`.editable` + double-click)
- `GET /general/icon-picker/list` — ikon picker datasını qaytarır

## 13. Translation cədvəli

`field_translations` polimorfik:
```
id | model_type | model_id | locale | key | value
```
- Unique: (model_type, model_id, locale, key)
- `getTranslation($attr, $locale, $fallback=true)` ilə oxunur
- `Translation` trait `model->translations` morphMany ilə avtomatik load edir
- `delete` zamanı cascade silinir

## 14. Activity log

`config/custom/activity_messages.php`-də modulun açarı varsa və `$logEnabled = true`-dirsə, hər `created/updated/deleted` event-i `activity_log` cədvəlinə yazılır. Causer: gopanel guard, fallback web guard.

```php
// config/custom/activity_messages.php
'Product' => [
    'title'   => 'Məhsul',
    'created' => ':causer yeni :title əlavə etdi',
    'updated' => ':causer :title məlumatını dəyişdirdi',
    'deleted' => ':causer :title sildi',
],
```

## 15. Test yazma

`tests/Unit/<Module>Test.php`. Test pattern olaraq fayl məzmunu və konfiqurasiya yoxlamaları edilir (DB-yə minimal toxunulur):

```php
class ProductModuleTest extends TestCase
{
    public function test_product_model_configuration_is_ready(): void {
        $product = new Product();
        $this->assertSame('products', $product->getTable());
        $this->assertContains('uid', $product->getFillable());
    }

    public function test_product_permissions_and_sidebar_are_registered(): void {
        $names = collect(config('gopanel.permission_list.gopanel.Məhsullar'))->pluck('name')->all();
        $this->assertContains('gopanel.products.index', $names);
    }
}
```
Run: `php vendor/phpunit/phpunit/phpunit`

## 16. Yeni CRUD modul əlavə etmək — addım-addım siyahı

1. **Migration** — `database/migrations/YYYY_MM_DD_HHMMSS_create_<plural>_table.php`
   - id, uid (uuid+default UUID()), atributlar, is_active, timestamps, softDeletes
2. **Model** — `app/Models/Site/<Singular>.php`
   - Translation, MetaData, AddUuid trait-ləri (lazımdırsa)
   - `$fillable`, `$casts`, `$files`, `$translatedAttributes`, `$slug_key`
   - Datatable accessor-ları: `getXxxViewAttribute()`, `getIsActiveBtnAttribute()`
3. **Controller** — `app/Http/Controllers/Gopanel/<Singular>Controller.php`
   - Modal və ya full-page naxışı seç
   - index / (getForm və ya store) / save
4. **Datatable class** (datatable üçün) — `app/Datatable/Gopanel/<Singular>Datatable.php`
   - `gopanel.<singular>` adı `<Singular>Datatable` sinifə uyğun olmalıdır
5. **Routes** — `routes/gopanel.php`-də qrup əlavə et
6. **View-lar** — `resources/views/gopanel/pages/<plural>/`
   - `index.blade.php` (datatable və ya sortable)
   - `store.blade.php` (full-page) və ya `inc/modal.blade.php` (modal)
   - `partials/form.blade.php` (multi-tab + meta)
7. **JS modulu** (lazımdırsa) — `public/assets/gopanel/js/modules/<plural>.js`
8. **Permissions** — `config/gopanel/permission_list.php`-ə yeni qrup
9. **Sidebar** — `config/gopanel/sidebar_menu_list.php`-ə yeni element
10. **Mock seeder** — `database/seeders/mock/<Singular>Seeder.php`
11. **Test** — `tests/Unit/<Singular>ModuleTest.php`
12. **İcra**:
    ```bash
    php artisan migrate
    php artisan db:seed --class="Database\Seeders\PermissionSeeder"
    php artisan db:seed --class="Database\Seeders\mock\<Singular>Seeder"
    php artisan route:clear && php artisan view:clear
    php vendor/phpunit/phpunit/phpunit
    ```

## 17. Tez-tez yaranan problemlər

- **`config:cache` xətası** — config-də Closure var, normal davranışdır. `route:cache` istifadə et, `config:cache` yox.
- **DataTable adı `gopanel.products` (plural)** olsa, sinif `ProductsDatatable` axtarılar; sinifin adı `ProductDatatable` (singular)-dirsə, view-da `gopanel.product` yaz.
- **Modal `#cerate-modal`** — typo, dəyişdirmə (crud.js ona bağlıdır).
- **Lightbox işləmir** — şəkil `class="image-lightbox"` olmalıdır (HasFiles trait `_view` accessor-undan avtomatik gəlir). Magnific-popup CSS+JS scripts.blade.php-də daxil edilib.
- **Slug avtomatik üretilmir** — modeldə `public $slug_key = 'title'` təyin et; `Translation` trait-ində `slug` translatedAttributes-da olmalıdır.

## 18. Vacib komandalar

```bash
# Migration
php artisan migrate
php artisan migrate:rollback

# Seeder
php artisan db:seed                                                    # core (DatabaseSeeder)
php artisan db:seed --class="Database\Seeders\PermissionSeeder"        # permissions yenilə
php artisan db:seed --class="Database\Seeders\mock\<Name>Seeder"       # mock data

# Cache
php artisan cache:clear
php artisan route:clear && php artisan view:clear

# Test
php vendor/phpunit/phpunit/phpunit
php vendor/phpunit/phpunit/phpunit --filter ProductModuleTest

# Tinker (sürətli yoxlama)
php artisan tinker --execute="echo App\Models\Site\Product::count();"

# Route inspecting
php artisan route:list --name=products
```

## 19. Layiheə xüsusi qaydalar

### 19.1 Ümumi
- **Heç vaxt `--no-verify` ilə commit etmə** (default qadağa)
- **Heç vaxt `git config` dəyişdirmə**
- **Comment yazmaqdan çəkin** — sadəcə "niyə" qeyri-aşkardırsa
- **Yeni `.md` faylı yaratma** istifadəçi xahiş etmədikcə (bu fayl istisnadır — istifadəçi xahiş etmişdir)
- **Statik analiz xəbərdarlıqlarına** (P1003 unused, P1132 missing type, P1013 undefined method on `auth()->user()`) — ümumi naxışdır, runtime-da işləyir
- **Tərcümə açarları AZ-ENG mübadilə olunmalıdır** — interfeys mətnləri Azərbaycanca

### 19.2 Namespace istifadəsi (use vs FQN)

Heç vaxt **fully-qualified class name** ilə inline istifadə etmə. Sinif həmişə faylın yuxarısında `use` ilə import olunmalıdır.

```php
// ❌ YANLIŞ
$users = \App\Models\User\User::all();
$status = \App\Enums\Common\StatusEnum::Active;

// ✅ DOĞRU
use App\Models\User\User;
use App\Enums\Common\StatusEnum;

class FooController {
    public function index() {
        $users  = User::all();
        $status = StatusEnum::Active;
    }
}
```

### 19.3 Blade-də Enum və ağır məntiq

Blade fayllarında **heç vaxt** birbaşa enum cases, model query-ləri, və ya hesablamalar etmə. Onları controller-də variable kimi hazırla və view-a ötür.

```blade
{{-- ❌ YANLIŞ — blade-də enum və query --}}
@foreach (\App\Enums\Common\StatusEnum::cases() as $status) ...
@foreach (App\Models\Site\Product::where('is_active', 1)->get() as $product) ...

{{-- ✅ DOĞRU — controller-dən gələn variable --}}
@foreach ($statuses as $status) ...
@foreach ($products as $product) ...
```

Controller:
```php
public function index() {
    return view('...', [
        'statuses' => StatusEnum::cases(),
        'products' => Product::where('is_active', 1)->get(),
    ]);
}
```

Blade yalnız **render** üçün, biznes məntiqi üçün deyil.

### 19.4 Layer arxitekturası

Lazım olduqda controller-i şişirtmək yerinə uyğun layer-dən istifadə et:

| Layer | Yer | Vəzifəsi | Nə vaxt |
|---|---|---|---|
| **Service** | `app/Services/` | Biznes məntiqi orchestration | Controller bir neçə model/helper-i koordinasiya edirsə |
| **Repository** | `app/Repositories/` | DB write əməliyyatları (insert, update, delete, bulk) | Eyni write məntiqi 2+ yerdə təkrar olursa, və ya transaction lazımdırsa |
| **Query** | `app/Queries/` | Mürəkkəb SELECT-lər (filtrlər, joinlər, agreqasiyalar) | 5+ sətirlik query, 2+ join, dynamic where |
| **DTO** | `app/DTOs/` | Type-safe data konteynerləri | Service/Repository arasında strukturlaşdırılmış data ötürmə |
| **Action** | `app/Actions/` | Tək məsuliyyətli əməliyyat (single-method invokable) | Bir əməl bir neçə yerdən çağırılırsa (CreateProductAction, ResetUserPasswordAction) |
| **Enum** | `app/Enums/` | Sabit dəyər dəstləri | Status, tip, kateqoriya — string/int sabitlər əvəzinə |

```php
// Yaxşı bölgü nümunəsi
class ProductController extends GoPanelController {
    public function __construct(
        public ProductService $service,
    ) { parent::__construct(); }

    public function index(ProductFilterRequest $request) {
        return view('...', [
            'products' => (new ProductQuery)
                ->withFilters($request->validated())
                ->withCategory()
                ->paginate(),
        ]);
    }

    public function save(Product $item, SaveProductRequest $request) {
        $dto = ProductDto::fromRequest($request);
        $product = (new SaveProductAction)->execute($item, $dto);
        $this->success_response($product, 'Saxlanıldı');
        return $this->response_json();
    }
}
```

**Lazımsız layer yaratma** — sadə CRUD üçün artıq abstraksiya çoxluq edir. Controller + Model + Helper kifayətdirsə, dayan.

### 19.5 Activity loglama (Spatie — model səviyyəsi)

CRUD-ların avtomatik audit izi üçün:

1. **Modeldə** `protected $logEnabled = true` (default `false`) — `LogsAdminActivity` trait-i bu flag-a baxır.
2. **`config/custom/activity_messages.php`-ə** modul açarını əlavə et:

```php
'Product' => [
    'title'   => 'Məhsul',
    'created' => ':causer yeni məhsul əlavə etdi — :title (qiymət: :price)',
    'updated' => ':causer məhsulu yenilədi — :title',
    'deleted' => ':causer məhsulu sildi — :title',
],
```

3. **Placeholder-lar**: `:causer` (admin adı) + modelin istənilən atributu (`:title`, `:price`, `:slug` və s.). Translation trait-li atributlar avtomatik cari locale-də gəlir.

4. `$logEnabled = false` saxla, əgər model kritik audit tələb etmirsə (mock seeder, internal cache modelləri). Trait `false` olduqda heç nə yazmır.

5. Bu yalnız Eloquent CRUD event-lərini (`activity_log` cədvəlində) izləyir — manual log-lar (login, file upload, custom action-lar) üçün **19.6**-ya bax.

### 19.6 Custom kanal logları (`LogService` + `config/logging.php`)

Manual loglar (auth, ödənişlər, mail, custom action-lar) `App\Services\Activity\LogService` vasitəsilə yazılır. Bu xidmət həm faylı yazır (Laravel `Log::channel()`), həm də `file_logs` cədvəlinə qeyd salır (admin paneldə filtr/baxış üçün), causer-i avtomatik bağlayır, prod-da sensitive key-ləri maskalayır.

**Addım 1 — Yeni kanal əlavə et** (`config/logging.php` → `channels`):

```php
'product' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/product/product-day.log'),
    'level'  => env('LOG_LEVEL', 'debug'),
    'days'   => 30,
    'manual' => true,
    'name'   => 'Məhsul əməliyyatları',  // UI filtrlərində göstərilən az ad
],
```

Mövcud kanallar: `system-errors`, `gopanel-auth`, `gopanel`, `transactions`, `mail`.

**Addım 2 — `LogService`-i constructor-da `$this->logging` property-sinə bağla.** Eyni metodun içində bir neçə log çağırışı olarsa, hər dəfə `LogService::channel(...)` yazmaq əvəzinə bir dəfə inicialize edib istifadə et.

#### Controller-də

```php
use App\Services\Activity\LogService;

class ProductController extends GoPanelController
{
    protected LogService $logging;

    public function __construct()
    {
        parent::__construct();
        $this->logging = LogService::channel('product');
    }

    public function save(Product $item, Request $request)
    {
        try {
            // ... save məntiqi ...
            $this->logging->info('Məhsul yaradıldı', $item);
        } catch (\Throwable $e) {
            $this->logging->error('Məhsul saxlanıla bilmədi', [
                'item_id' => $item->id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

#### Service-də (controllerdə servis varsa, log-u servisin daxilinə salt)

```php
class ProductService
{
    protected LogService $logging;

    public function __construct()
    {
        $this->logging = LogService::channel('product');
    }

    public function create(ProductDto $dto): Product
    {
        $product = Product::create($dto->toArray());
        $this->logging->info('Məhsul yaradıldı', $product);
        return $product;
    }
}
```

Beləliklə controller "nazik" qalır, biznes-spesifik loglar servisin içindədir.

#### Tek bir yerdə istifadə (constructor lazım deyilsə)

```php
LogService::channel('gopanel-auth')->info('Admin daxil oldu', ['email' => $admin->email]);
```

Bu format **yalnız bir-iki sətirlik istifadədə** uyğundur. Eyni metodda 2+ log çağırışı varsa, mütləq constructor pattern-ə keç.

API:
- `LogService::channel(string $name, bool $saveDb = true, bool $logDetail = false)` — fluent başlanğıc
- `->info($msg, $context)`, `->warning(...)`, `->error(...)`, `->critical(...)`, `->debug(...)`, `->notice(...)`, `->alert(...)`, `->emergency(...)` (level-lər `config/custom/logging.php`-də)
- `$context` — `Model | JsonResource | Collection | array | string` qəbul edir, daxili olaraq array-a normalize olunur
- Production-da `sensitiveKeys` (`config/custom/logging.php`) avtomatik `[password]`-vari maskalanır
- `error/critical/alert/emergency` səviyyəsində `getLogDetails()` ilə stack/trace context əlavə olunur
- Kanal `config/logging.php`-də yoxdursa default-a fallback olur (sistem çökmür)

**Addım 3 — `Log::info()` / `Log::channel(...)` BİRBAŞA istifadə ETMƏ.** Onun yerinə həmişə `LogService::channel(...)` ki:
- `file_logs` cədvəlinə də yazılsın (admin panelin "Loglar" bölməsində görünsün)
- Causer (gopanel/web admin) avtomatik bağlansın
- Prod-da sensitive data (`password`, `token`, `api_key` və s.) avtomatik maskalansın
- Yanlış kanal adında sistem çökməsin (graceful fallback)

**Hansı əməliyyatda hansı kanal:**

| Əməliyyat | Kanal |
|---|---|
| Login / logout / qeydiyyat | `gopanel-auth` |
| Sistem xətaları (try/catch içində) | `system-errors` |
| Admin panel manual əməliyyatları | `gopanel` |
| Ödəniş / tranzaksiya | `transactions` |
| Email göndərmə | `mail` |
| Modulun spesifik audit-i (məs. inventar dəyişiklikləri) | yeni kanal — `product`, `order`, və s. |

### 19.7 try / catch və loglamanın əhatə dairəsi

**Qayda 1 — Hər vacib metodu `try / catch` içinə al.** İstisnaların proddakı işi dayandırmaması üçün xarici giriş nöqtələri (controller action-ları, queue job-ları, scheduled command-lar, file upload-lar, üçüncü tərəf API çağırışları) həmişə qorunmalıdır. Pure utility funksiyaları və getter-lər istisnadır.

**Qayda 2 — `catch` blokunda hər dəfə detallı log yaz** (`error` və ya `critical` səviyyəsi). Sadəcə `$e->getMessage()` kifayət deyil — context da əlavə olunmalıdır:

```php
try {
    $product = $this->productService->create($dto);
    $this->success_response($product, 'Saxlanıldı');
} catch (Exception $e) {
    $this->logging->error("Məhsul yaradıla bilmədi xeta {$e->getMessage()}", [
        'admin_id'      => auth('gopanel')?->id() ?? null,
        'item_id'      => $item->id ?? null,
        'file'         => $e->getFile(),
        'line'         => $e->getLine(),
        // trace LogService tərəfindən error/critical səviyyəsində avtomatik əlavə olunur
    ]);
    $this->response['message'] .= $e->getMessage();
    return $this->response_json();
}
```

`LogService` `error/critical/alert/emergency` səviyyələrində `getLogDetails()`-dən stack trace, request URL, IP, user agent kimi məlumatları avtomatik əlavə edir — onları əl ilə yazmağa ehtiyac yoxdur.

**Qayda 3 — Hər yerdə loglamağa ehtiyac yoxdur.** Log yalnız aşağıdakı yerlərdə yazılmalıdır:

- ✅ **Mürəkkəb biznes axınları** — ödəniş, sifariş emalı, multi-step orchestration
- ✅ **Xarici inteqrasiyalar** — API çağırışları, webhook-lar, mail göndərmə, SMS
- ✅ **Auth həssas əməliyyatları** — login, logout, parol dəyişikliyi, 2FA
- ✅ **Manual admin əməliyyatları** — bulk delete, məlumat ixrac, sistem tənzimləmələri
- ✅ **Bütün `catch` blokları** — istisnasız (Qayda 2)
- ❌ **Sadə CRUD** — `$model->save()` çağırışlarına manual log lazım deyil; `LogsAdminActivity` (Spatie) onu avtomatik tutur
- ❌ **Read-only sorğular** — `index()`, `show()` metodları
- ❌ **Pure helper funksiyaları** — formatter-lər, accessor-lar, view render-ləri
- ❌ **Loop daxilində** — hər iteration üçün ayrı log yazma; əvəzinə döngü əvvəlində/sonunda summary yaz

**Qayda 4 — Context-i mümkün qədər zəngin et.** Future debugging üçün loga baxan adamın "nə baş verib" sualına tək-tənha log entry cavab verə bilməlidir. Aşağıdakıları əlavə et:

- Cəlb olunan model ID-ləri (`'product_id' => 12`, `'order_id' => 'ord_xxx'`)
- Vacib request datası (`$request->except(['_token', 'password', 'card_number'])`)
- Auth context (`'user_id' => auth()->id()`, `'guard' => 'gopanel'`)
- Üçüncü tərəf cavabları (`'api_response' => $response->json()`)
- Vəziyyət-dəyişiklikləri (`'before' => $oldStatus, 'after' => $newStatus`)

`LogService` `Model`, `JsonResource`, `Collection` instance-larını avtomatik array-a çevirir, ona görə `$this->logging->info('...', $product)` birbaşa işləyir.

## 20. Sınanmış axın nümunələri (referans olaraq oxu)

- **Modal pattern** kommunikasiyası: `services` modulu — `index.blade.php`, `getForm`, modal `#cerate-modal`, `#data-form`, `#save-form-btn`
- **Full-page pattern**: `blog` modulu və `products` modulu — `static-form` + redirect
- **Sortable cədvəl**: `services`, `categories`, `slider` (yeni) modulları
- **Datatable**: `blog`, `products`, `admins`, `roles` modulları
- **Translatable + MetaData + AddUuid**: `products` modulu — bütün xüsusiyyətləri özündə birləşdirir
- **Drag-drop hierarchy**: `categories` modulu — parent-child ilişkili sortable

---

**Bu sənəd "yeni AI sessiyaları üçün konteks azaltma" məqsədi daşıyır.** Əgər layihədə əhəmiyyətli dəyişiklik baş verərsə (yeni trait, yeni helper, yeni komponent), bu faylı da yenilə.
