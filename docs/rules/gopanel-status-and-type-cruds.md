# Gopanel-də Status və Tip CRUD-larının Qurulması

Bu sənəd başqa layihədə işləyən developer və ya süni intellekt üçün implementasiya spesifikasiyasıdır. Buradakı məqsəd konkret `ListingStatus` modeli yaratmaq deyil. Məqsəd Gopanel daxilində müxtəlif status və tip lüğətlərinin vahid arxitektura ilə necə qurulacağını izah etməkdir.

Hədəf menyu adı nümunə olaraq **Status və Tiplər** ola bilər. Bu bölmənin daxilində layihənin ehtiyacına uyğun aşağıdakı CRUD-lardan hər hansı biri saxlanıla bilər:

- elan statusları;
- sifariş statusları;
- ödəniş statusları;
- tender statusları;
- müraciət statusları;
- elan tipləri;
- istifadəçi tipləri;
- məhsul tipləri;
- tender tipləri;
- şikayət səbəbləri;
- layihəyə məxsus digər kiçik lookup/lüğət cədvəlləri.

Başqa layihədə `listing_statuses` və ya elan modulu ümumiyyətlə olmaya bilər. Bu halda sənəddəki `OrderStatus`, `ProductType`, `RequestStatus` kimi nümunələr həmin layihənin domeninə uyğun dəyişdirilməlidir.

## 1. Əsas prinsip

Status və tip CRUD-ları bir-birinə çox oxşar olur. Hər modul üçün controller, Blade və JavaScript-i sıfırdan təkrarlamaq əvəzinə iki qatlı struktur qurulmalıdır:

1. **Ortaq lookup infrastrukturu** — siyahı, modal, form submit, delete, sortable, permission və response formatı.
2. **Modula məxsus konfiqurasiya və biznes qaydaları** — model, başlıq, route adı, əlavə sahələr, boolean-lar, default status qaydası və s.

Sadə CRUD davranışı ortaq qatda saxlanmalıdır. Domen qaydaları generic controller-ə doldurulmamalıdır.

## 2. Gopanel menyusu

Əsas sol Gopanel menyusunda bir parent menyu yaradılmalıdır:

```text
Status və Tiplər
├── Sifariş statusları
├── Ödəniş statusları
├── Müraciət statusları
├── Məhsul tipləri
└── İstifadəçi tipləri
```

Parent menyunun adı layihədən asılı olaraq bunlardan biri ola bilər:

- `Status və Tiplər`;
- `Statuslar və Tiplər`;
- `Sistem lüğətləri`;
- `Lookup-lar`.

Tövsiyə edilən ad **Status və Tiplər**-dir, çünki texniki olmayan admin üçün daha aydındır.

Menyu nümunəsi:

```php
[
    'title' => 'Status və Tiplər',
    'icon' => 'bx bx-category-alt',
    'permission' => 'gopanel.lookups.view',
    'children' => [
        [
            'title' => 'Sifariş statusları',
            'route' => 'gopanel.lookups.order-statuses.index',
            'permission' => 'gopanel.lookups.view',
        ],
        [
            'title' => 'Məhsul tipləri',
            'route' => 'gopanel.lookups.product-types.index',
            'permission' => 'gopanel.lookups.view',
        ],
    ],
]
```

Qaydalar:

- parent menyu yalnız istifadəçinin görə bildiyi ən az bir child olduqda göstərilməlidir;
- aktiv child route olduqda parent menyu açıq və aktiv görünməlidir;
- route adları hardcoded URL deyil, named route olmalıdır;
- bütün child-lar eyni permission istifadə edə bilər, yaxud layihə tələb edirsə ayrıca permission ala bilər;
- çox sayda lookup olduqda menyu həddən artıq uzadılmamalı, daxili sidebar istifadə edilməlidir.

## 3. Bölmədaxili sidebar

İstifadəçi “Status və Tiplər” bölməsinə daxil olduqda səhifə iki sütunlu ola bilər:

```text
┌──────────────────────┬──────────────────────────────────────────┐
│ Status və Tiplər     │ Sifariş statusları            [+ Əlavə] │
│                      │                                          │
│ Sifariş statusları   │  ↕  Key       Ad       Aktiv  Əməliyyat │
│ Ödəniş statusları    │  ↕  pending   Gözləyir   ✓      ✎  🗑   │
│ Müraciət statusları  │  ↕  approved  Təsdiq     ✓      ✎  🗑   │
│ Məhsul tipləri       │                                          │
│ İstifadəçi tipləri   │                                          │
└──────────────────────┴──────────────────────────────────────────┘
```

Desktop görünüşü:

- sidebar: `col-xl-3`;
- əsas kontent: `col-xl-9`;
- sidebar ayrıca card/list-group kimi göstərilir;
- cari route aktiv class alır;
- hər elementin uyğun ikonu ola bilər.

Mobil görünüş:

- sidebar əsas cədvəlin üstünə keçir;
- çox element olduqda collapse və ya select formasına çevrilə bilər;
- cədvəl `table-responsive` daxilində olmalıdır.

Sidebar Blade nümunəsi:

```blade
<div class="card lookup-sidebar-card">
    <div class="card-header">
        <h5 class="mb-0">Status və Tiplər</h5>
    </div>

    <div class="list-group list-group-flush lookup-sidebar">
        @foreach ($lookupMenu as $menu)
            @can($menu['permission'] ?? 'gopanel.lookups.view')
                <a href="{{ route($menu['route']) }}"
                   class="list-group-item list-group-item-action
                          {{ $activeLookupRoute === $menu['route'] ? 'active' : '' }}">
                    <i class="{{ $menu['icon'] }}"></i>
                    <span>{{ $menu['title'] }}</span>
                </a>
            @endcan
        @endforeach
    </div>
</div>
```

Sidebar elementləri view daxilində əl ilə təkrarlanmamalıdır. Siyahı config, menu service və ya `BaseLookupController::lookupMenu()` kimi ortaq mənbədən gəlməlidir.

## 4. Tövsiyə olunan qovluq strukturu

```text
app/
├── Http/Controllers/Gopanel/Lookups/
│   ├── BaseLookupController.php
│   ├── OrderStatusController.php
│   └── ProductTypeController.php
├── Http/Requests/Gopanel/Lookups/
│   ├── BaseLookupSaveRequest.php
│   ├── LookupSortRequest.php
│   ├── OrderStatusSaveRequest.php
│   └── ProductTypeSaveRequest.php
├── Models/Lookups/
│   ├── OrderStatus.php
│   └── ProductType.php
└── Services/Lookups/
    └── OrderStatusService.php       # yalnız biznes qaydası çoxdursa

resources/views/gopanel/pages/lookups/
├── partials/
│   ├── index.blade.php
│   └── form.blade.php
├── inc/
│   └── modal.blade.php
├── sidebar.blade.php
├── order-statuses/
│   ├── index.blade.php
│   └── form.blade.php
└── product-types/
    ├── index.blade.php
    └── form.blade.php

public/assets/gopanel/js/
├── crud.js
└── sortable-lookups.js

routes/
└── gopanel.php

tests/Feature/Gopanel/Lookups/
├── OrderStatusControllerTest.php
└── ProductTypeControllerTest.php
```

Model namespace layihənin mövcud konvensiyasına uyğun dəyişə bilər. Əsas tələb bütün lookup modullarının proqnozlaşdırılan, eyni struktura sahib olmasıdır.

## 5. Database strukturu

Status və tip cədvəlləri üçün baza schema:

```php
Schema::create('order_statuses', function (Blueprint $table) {
    $table->id();
    $table->uuid('uid')->unique();
    $table->string('key', 50)->unique();
    $table->string('color', 30)->nullable();
    $table->string('class_name', 100)->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_default')->default(false);
    $table->boolean('is_public')->default(false);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

Tip cədvəli daha sadə ola bilər:

```php
Schema::create('product_types', function (Blueprint $table) {
    $table->id();
    $table->uuid('uid')->unique();
    $table->string('key', 50)->unique();
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

Sahələrin mənası:

| Sahə | Məqsəd |
|---|---|
| `id` | Daxili primary key və foreign key əlaqələri |
| `uid` | URL və frontend-də təhlükəsiz identifikator |
| `key` | Kod və API üçün dəyişməz texniki açar |
| `sort_order` | Admin və API-də göstərilmə sırası |
| `color` | Status badge rəngi |
| `class_name` | Hazır CSS class istifadə edilirsə onun adı |
| `is_default` | Yeni obyekt üçün başlanğıc status |
| `is_public` | Son istifadəçiyə göstərilə bilən status |
| `is_active` | Yeni seçimlərdə istifadə edilə bilməsi |
| `deleted_at` | Tarixi məlumatı qoruyan soft-delete |

`name` və `description` çoxdilli layihədə ayrıca translation cədvəlində saxlanmalıdır. Layihə çoxdilli deyilsə birbaşa əsas cədvəldə `name` və `description` sütunları istifadə edilə bilər.

## 6. Model strukturu

Çoxdilli status modeli nümunəsi:

```php
class OrderStatus extends BaseModel
{
    use SoftDeletes, Translation, AddUuid;

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = [
        'uid',
        'key',
        'color',
        'class_name',
        'sort_order',
        'is_default',
        'is_public',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_default' => 'boolean',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
```

`key` texniki müqavilədir. Məsələn `pending`, `approved`, `cancelled`. Kod daxilində bu açara müraciət edilə biləcəyi üçün status istifadə olunmağa başladıqdan sonra `key` nəzarətsiz dəyişdirilməməlidir.

## 7. Route strukturu

Hər lookup eyni route müqaviləsini istifadə etməlidir:

```php
Route::prefix('lookups')->name('lookups.')->group(function () {
    Route::prefix('order-statuses')->name('order-statuses.')->group(function () {
        Route::get('/', [OrderStatusController::class, 'index'])
            ->name('index');

        Route::get('/get/form/{id?}', [OrderStatusController::class, 'getForm'])
            ->name('get.form');

        Route::post('/save/{id?}', [OrderStatusController::class, 'save'])
            ->name('save');

        Route::delete('/delete/{id}', [OrderStatusController::class, 'destroy'])
            ->name('destroy');

        Route::patch('/sort', [OrderStatusController::class, 'sort'])
            ->name('sort');
    });
});
```

Nəticə URL-lər:

```text
GET     /gopanel/lookups/order-statuses
GET     /gopanel/lookups/order-statuses/get/form/{uid?}
POST    /gopanel/lookups/order-statuses/save/{uid?}
DELETE  /gopanel/lookups/order-statuses/delete/{uid}
PATCH   /gopanel/lookups/order-statuses/sort
```

Bütün route-lar admin authentication və server-side permission middleware ilə qorunmalıdır. Blade `@can` yalnız UI elementini gizlədir, endpoint-i qorumur.

## 8. Base controller

Ortaq controller aşağıdakı məsuliyyətləri daşımalıdır:

- modeli `sort_order` ilə gətirmək;
- form Blade-ni AJAX üçün render etmək;
- validated məlumatı saxlamaq;
- translation-ları saxlamaq;
- delete etmək;
- sıralamanı yeniləmək;
- vahid JSON response qaytarmaq;
- sidebar menyusunu view-a ötürmək.

Konseptual struktur:

```php
abstract class BaseLookupController extends GoPanelController
{
    protected string $model;
    protected string $title;
    protected string $viewPath;
    protected string $routeName;
    protected array $extraFields = [];
    protected array $booleans = [];

    public function index(): View
    {
        $items = ($this->model)::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view($this->viewPath . '.index', [
            'items' => $items,
            'definition' => $this->definition(),
            'routeName' => $this->routeName,
            'lookupMenu' => $this->lookupMenu(),
            'activeLookupRoute' => $this->routeName . '.index',
        ]);
    }

    public function getForm(?string $id = null): JsonResponse
    {
        $item = $id
            ? ($this->model)::resolveByKey($id)
            : new $this->model();

        abort_unless($item, 404);

        $html = view($this->viewPath . '.form', [
            'item' => $item,
            'definition' => $this->definition(),
            'route' => route($this->routeName . '.save', $item->identifier_id),
            'languages' => $this->activeLanguages(),
        ])->render();

        return response()->json([
            'status' => 'success',
            'html' => $html,
        ]);
    }
}
```

## 9. Modul controller-i

Sadə tip controller-i yalnız konfiqurasiya verməlidir:

```php
class ProductTypeController extends BaseLookupController
{
    protected string $model = ProductType::class;
    protected string $title = 'Məhsul tipləri';
    protected string $viewPath = 'gopanel.pages.lookups.product-types';
    protected string $routeName = 'gopanel.lookups.product-types';
    protected array $booleans = ['is_active'];

    public function save(ProductTypeSaveRequest $request, ?string $id = null)
    {
        return $this->saveItem($request, $id);
    }
}
```

Status controller-i əlavə sahələr verə bilər:

```php
class OrderStatusController extends BaseLookupController
{
    protected string $model = OrderStatus::class;
    protected string $title = 'Sifariş statusları';
    protected string $viewPath = 'gopanel.pages.lookups.order-statuses';
    protected string $routeName = 'gopanel.lookups.order-statuses';
    protected array $extraFields = ['color', 'class_name'];
    protected array $booleans = ['is_default', 'is_public', 'is_active'];
}
```

Default status, qorunan key və istifadə olunan statusun silinməsi kimi qaydalar varsa onlar modul controller-i və ya service daxilində yazılmalıdır.

## 10. Request validasiyası

Generic request bütün mümkün sahələr üçün zəif qaydalar verməməlidir. Hər modul öz domeninə uyğun qaydaları sərtləşdirməlidir.

```php
public function rules(): array
{
    return [
        'key' => [
            'required',
            'string',
            'max:50',
            'regex:/^[a-z][a-z0-9_]*$/',
            Rule::unique('order_statuses', 'key')->ignore($this->modelId()),
        ],
        'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'class_name' => ['nullable', 'string', 'max:100'],
        'is_default' => ['sometimes', 'boolean'],
        'is_public' => ['sometimes', 'boolean'],
        'is_active' => ['sometimes', 'boolean'],
        'name' => ['required', 'array'],
        'name.*' => ['nullable', 'string', 'max:255'],
        'description' => ['nullable', 'array'],
        'description.*' => ['nullable', 'string', 'max:2000'],
    ];
}
```

Ən azı default dilin adı tələb olunmalıdır. Request-də gələn dil kodları aktiv dil siyahısı ilə whitelist edilməlidir.

## 11. Index Blade və HTML strukturu

Modulun `index.blade.php` faylı nazik wrapper olmalıdır:

```blade
@extends('gopanel.layouts.main')

@section('content')
    @include('gopanel.pages.lookups.partials.index')
@endsection
```

Ortaq index partial:

```blade
<div class="page-content">
    <div class="container-fluid">
        <div class="page-title-box d-flex justify-content-between">
            <h4>{{ $definition['title'] }}</h4>

            @can('gopanel.lookups.add')
                <button id="open-create-modal"
                        class="btn btn-success"
                        data-route="{{ route($routeName . '.get.form') }}">
                    <i class="fas fa-plus"></i> Əlavə et
                </button>
            @endcan
        </div>

        <div class="row g-3">
            <aside class="col-xl-3">
                @include('gopanel.pages.lookups.sidebar')
            </aside>

            <main class="col-xl-9">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class="lookup-sort-column"></th>
                                        <th>Key</th>
                                        <th>Ad</th>
                                        <th>Status</th>
                                        <th class="text-end">Əməliyyatlar</th>
                                    </tr>
                                </thead>

                                <tbody id="lookup-sortable"
                                       data-url="{{ route($routeName . '.sort') }}">
                                    @foreach ($items as $item)
                                        <tr data-id="{{ $item->identifier_id }}">
                                            <td>
                                                <button type="button"
                                                        class="lookup-drag-handle btn btn-link"
                                                        aria-label="Sıranı dəyiş">
                                                    <i class="fas fa-grip-vertical"></i>
                                                </button>
                                            </td>
                                            <td><code>{{ $item->key }}</code></td>
                                            <td>{{ $item->name ?? $item->key }}</td>
                                            <td><!-- active switch --></td>
                                            <td class="text-end">
                                                <!-- edit/delete buttons -->
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
```

Status CRUD-da əlavə sütunlar lazım ola bilər:

- rəng preview-i;
- default badge;
- public/private badge;
- həmin statusdan istifadə edən obyektlərin sayı.

Belə halda generic partial çoxlu `if` ilə mürəkkəbləşdirilməməlidir. Modul üçün ayrıca `partials/table.blade.php` yaradılmalıdır.

## 12. Form Blade strukturu

Form AJAX ilə modal daxilinə yüklənir:

```blade
<form action="{{ $route }}" id="data-form">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="lookup-key" class="form-label">Key</label>
            <input id="lookup-key"
                   name="key"
                   value="{{ old('key', $item->key) }}"
                   class="form-control"
                   placeholder="pending_review">
            <div class="form-text">
                Bu texniki açardır. Kodda istifadə olunduqdan sonra dəyişdirilməməlidir.
            </div>
        </div>

        <!-- Dil tabları: name[az], name[en], description[az] və s. -->
        <!-- Konfiqurasiyaya əsasən color/class_name kimi əlavə sahələr -->
        <!-- Konfiqurasiyaya əsasən boolean switch-lər -->
    </div>
</form>
```

Yeni status üçün boolean default-ları:

```text
is_active  = true
is_public  = false
is_default = false
```

Bütün checkbox-ları yeni qeyddə avtomatik aktiv etmək olmaz.

Modal strukturu:

```blade
<div id="lookup-form-modal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-right-side">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Status və ya tip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div id="form-wrap" class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    İmtina et
                </button>
                <button type="button" id="save-form-btn" class="btn btn-primary">
                    Yadda saxla
                </button>
            </div>
        </div>
    </div>
</div>
```

Create və edit vəziyyətində modal title uyğun dəyişməlidir.

## 13. JavaScript strukturu

JavaScript üç məsuliyyətə ayrılmalıdır:

```text
main.js
└── CSRF, alert, loader və ümumi helper-lər

crud.js
├── create modalını açmaq
├── edit formunu açmaq
├── AJAX form submit
└── validasiya xətalarını göstərmək

sortable-lookups.js
├── drag-and-drop
├── yeni sıranı serverə göndərmək
├── xəta zamanı əvvəlki sıraya qayıtmaq
└── delete confirm və DELETE request
```

Create/edit axını:

```text
Create/Edit klik
→ GET get/form/{uid?}
→ server {status, html} qaytarır
→ HTML #form-wrap daxilinə yazılır
→ modal açılır
→ #save-form-btn klik
→ #data-form FormData ilə POST edilir
→ uğurlu response-dan sonra redirect və ya siyahı refresh edilir
```

Statusa məxsus JavaScript generic `crud.js` daxilinə yazılmamalıdır. Xüsusi davranış varsa `modules/order-statuses.js` kimi ayrıca fayl yaradılmalıdır.

## 14. Sortable davranışı

DOM müqaviləsi:

```html
<tbody id="lookup-sortable" data-url="/gopanel/lookups/order-statuses/sort">
    <tr data-id="UUID-1">...</tr>
    <tr data-id="UUID-2">...</tr>
</tbody>
```

JavaScript:

```javascript
$(function () {
    var $list = $('#lookup-sortable');
    var previousOrder = [];
    var sorting = false;

    function readOrder() {
        return $list.find('tr[data-id]').map(function () {
            return $(this).data('id');
        }).get();
    }

    function restoreOrder(order) {
        order.forEach(function (id) {
            $list.append($list.find('tr[data-id="' + id + '"]'));
        });
    }

    $list.sortable({
        items: '> tr',
        axis: 'y',
        handle: '.lookup-drag-handle',
        start: function () {
            previousOrder = readOrder();
        },
        update: function () {
            if (sorting) return;
            sorting = true;
            $list.sortable('disable');

            $.ajax({
                url: $list.data('url'),
                type: 'PATCH',
                data: { orders: readOrder() }
            }).fail(function (xhr) {
                restoreOrder(previousOrder);
                showError(xhr);
            }).always(function () {
                sorting = false;
                $list.sortable('enable');
            });
        }
    });
});
```

Server request validasiyası:

```php
return [
    'orders' => ['required', 'array', 'min:1'],
    'orders.*' => [
        'required',
        'distinct',
        'uuid',
        Rule::exists($this->table, 'uid')->whereNull('deleted_at'),
    ],
];
```

Controller:

```php
DB::transaction(function () use ($request) {
    foreach ($request->validated('orders') as $index => $uid) {
        ($this->model)::where('uid', $uid)->update([
            'sort_order' => $index + 1,
        ]);
    }
});
```

Sortable qaydaları:

- drag yalnız handle üzərindən başlamalıdır;
- request gedərkən sortable müvəqqəti bloklanmalıdır;
- xəta olarsa DOM əvvəlki sıraya qaytarılmalıdır;
- UID-lər serverdə validasiya edilməlidir;
- təkrarlanan UID qəbul edilməməlidir;
- update transaction daxilində olmalıdır;
- permission server tərəfində yoxlanmalıdır;
- uğurlu sort-dan sonra cache təmizlənməlidir;
- böyük siyahıda loop əvəzinə bulk `upsert` istifadə edilə bilər.

## 15. Statuslara məxsus biznes qaydaları

Bu bölmə yalnız status CRUD-larına aiddir. Sadə tip CRUD-larında lazım olmaya bilər.

### Yalnız bir default status

Eyni domen üçün eyni anda yalnız bir `is_default=true` status olmalıdır.

```php
DB::transaction(function () use ($status, $data) {
    if ($data['is_default']) {
        OrderStatus::whereKeyNot($status->getKey())
            ->update(['is_default' => false]);
    }

    $status->fill($data)->save();
});
```

Default status:

- başqa default seçilmədən silinməməlidir;
- deaktiv edilməməlidir;
- bütün statuslar içində ən azı bir default qalmalıdır.

### İstifadə olunan statusun silinməsi

Status foreign key ilə obyektlərə bağlıdırsa istifadə olunan status silinməməlidir:

```php
if ($status->orders()->exists()) {
    throw ValidationException::withMessages([
        'status' => 'Bu status istifadə olunur. Silmək əvəzinə deaktiv edin.',
    ]);
}
```

Soft-delete foreign key problemini tam həll etmir. Əlaqəli model soft-delete olunmuş statusu adi `belongsTo` ilə gətirməyə bilər. Ona görə istifadə olunan status üçün əsas əməliyyat deaktiv etməkdir.

### Public və active fərqi

- `is_active`: statusun yeni seçimlərdə istifadə oluna bilməsi;
- `is_public`: statusun son istifadəçiyə göstərilə bilməsi.

Admin üçün aktiv status query-si və public API query-si eyni olmamalıdır:

```php
// Admin daxili seçim
OrderStatus::active()->ordered()->get();

// İstifadəçi API-si
OrderStatus::active()->public()->ordered()->get();
```

## 16. Delete davranışı

Delete klikində SweetAlert və ya uyğun confirm göstərilməlidir. Server aşağıdakıları yoxlamalıdır:

- istifadəçinin delete permission-u varmı;
- model mövcuddurmu;
- status default-durmu;
- status/type başqa cədvəldə istifadə olunurmu;
- qorunan sistem key-i varmı;
- silmə soft-delete, yoxsa hard-delete olmalıdır.

Server uğurlu olduqda sətir DOM-dan silinə və ya səhifə yenilənə bilər. Xəta olduqda backend mesajı göstərilməlidir.

## 17. Permission strukturu

Minimum permission-lar:

```text
gopanel.lookups.view
gopanel.lookups.add
gopanel.lookups.edit
gopanel.lookups.delete
```

Layihə daha granular icazə istəyirsə:

```text
gopanel.order-statuses.view
gopanel.order-statuses.add
gopanel.order-statuses.edit
gopanel.order-statuses.delete
```

Yoxlama üç yerdə düşünülməlidir:

1. Gopanel əsas menyusu;
2. Blade düymələri və sidebar elementləri;
3. route/controller səviyyəsi.

Əsas təhlükəsizlik üçüncü səviyyədir.

## 18. Response formatı

Form HTML response:

```json
{
  "status": "success",
  "message": "Form yaradıldı",
  "html": "<form id=\"data-form\">...</form>"
}
```

Save response:

```json
{
  "status": "success",
  "message": "Məlumat yadda saxlanıldı",
  "redirect": "/gopanel/lookups/order-statuses"
}
```

Sort/delete response:

```json
{
  "status": "success",
  "message": "Əməliyyat uğurla tamamlandı"
}
```

HTTP statusları düzgün istifadə edilməlidir:

- `200` — uğurlu read/update;
- `201` — yaradılma;
- `403` — permission yoxdur;
- `404` — model tapılmadı;
- `422` — validasiya və biznes qaydası xətası;
- `500` — gözlənilməyən server xətası.

## 19. Yeni status və ya tip CRUD əlavə etmə təlimatı

Başqa layihədə yeni modul yaradarkən bu ardıcıllıq izlənməlidir:

1. Domenə uyğun cədvəl adı müəyyən et: məsələn `request_statuses`.
2. Migration yarat və baza sahələrini əlavə et.
3. Model yarat; fillable, casts, translation və relation-ları yaz.
4. Seeder yarat; texniki `key`-ləri və ilkin sıralamanı əlavə et.
5. Save request yarat və modulun real qaydalarını yaz.
6. Status/type controller-i `BaseLookupController`-dən extend et.
7. Beş standart route-u əlavə et: index, form, save, delete, sort.
8. Modul üçün nazik `index.blade.php` və `form.blade.php` wrapper-ları yarat.
9. Modulu “Status və Tiplər” sidebar siyahısına əlavə et.
10. Modulu Gopanel əsas menyusuna ayrıca child kimi yalnız həqiqətən lazımdırsa əlavə et.
11. Permission-ları seed et və route-lara tətbiq et.
12. Cache istifadə olunursa save/delete/sort zamanı invalidate et.
13. Feature test və frontend smoke test yaz.

AI yeni modul yaradanda əvvəlcə layihənin mövcud model, route, response, translation, permission və menu konvensiyalarını yoxlamalıdır. Bu sənəddəki namespace və class adlarını kor-koranə kopyalamamalıdır.

## 20. Test checklist

Backend:

- admin olmayan şəxs bölməyə daxil ola bilmir;
- permission olmayan admin mutasiya edə bilmir;
- siyahı `sort_order`, sonra `id` ilə sıralanır;
- create və edit işləyir;
- eyni `key` qəbul edilmir;
- `key` yalnız düzgün formatda qəbul edilir;
- translation-lar düzgün saxlanılır;
- checkbox olmayan boolean `false` kimi saxlanılır;
- yalnız bir default status qalır;
- default status silinmir və deaktiv edilmir;
- istifadədə olan status silinmir;
- sort yalnız mövcud və unikal UID qəbul edir;
- sort transaction rollback edir;
- delete soft-delete qaydasına uyğundur;
- API yalnız active/public qeydləri düzgün sıra ilə qaytarır.

Frontend:

- əsas “Status və Tiplər” menyusu düzgün açılır;
- daxili sidebar cari səhifəni aktiv göstərir;
- create modalı açılır;
- edit formu mövcud məlumatla gəlir;
- validasiya xətaları input yanında görünür;
- save sonrası siyahı yenilənir;
- sortable yalnız handle ilə işləyir;
- sort xətasında əvvəlki sıra bərpa olunur;
- delete confirm işləyir;
- mobil görünüşdə sidebar, cədvəl və modal istifadə olunur;
- klaviatura ilə düymələrə və sort alternativinə çatmaq mümkündür.

## 21. Yekun qəbul meyarları

Implementasiya hazır sayılır, əgər:

- Gopanel-də “Status və Tiplər” parent menyusu görünür;
- bölmə daxilində status/type səhifələri sidebar ilə dəyişdirilə bilir;
- bütün səhifələr eyni vizual və texniki CRUD müqaviləsindən istifadə edir;
- create/edit AJAX modal ilə işləyir;
- delete təhlükəsiz biznes qaydaları ilə qorunur;
- drag-and-drop sıralama qalıcıdır və xəta zamanı geri qayıdır;
- backend permission və request validasiyası mövcuddur;
- statuslara məxsus default/public/active qaydaları tətbiq olunur;
- yeni lookup minimal təkrar kodla əlavə edilə bilir;
- başqa layihədə elan modulu olmasa belə struktur həmin layihənin status və tiplərinə uyğunlaşdırıla bilir.
