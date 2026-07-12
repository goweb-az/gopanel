# Gopanel Hierarxik Category CRUD Arxitekturası

Bu sənəd başqa Laravel layihəsində işləyən developer və ya süni intellekt üçün implementasiya spesifikasiyasıdır. Məqsəd Aquastores modelini kor-koranə köçürmək deyil; Gopanel-də parent/child ağac strukturlu kateqoriya CRUD-unun təhlükəsiz, genişlənə bilən və təkrar istifadə olunan formada necə qurulacağını göstərməkdir.

`Category` adı nümunədir. Eyni pattern aşağıdakılara tətbiq oluna bilər:

- məhsul kateqoriyaları;
- departamentlər;
- menyu elementləri;
- xidmət qrupları;
- sənəd qovluqları;
- region ağacı;
- başqa self-referencing strukturlar.

AI əvvəlcə hədəf layihənin Laravel versiyasını, route-model binding, translation, media, permission, response və frontend kitabxana konvensiyalarını yoxlamalı, sonra nümunələri uyğunlaşdırmalıdır.

## 1. Hədəf davranış

Gopanel Category səhifəsi bunları təmin etməlidir:

- yalnız root kateqoriyaları ilkin səhifədə yükləmək;
- child-ları node ilk dəfə açılanda AJAX ilə lazy-load etmək;
- eyni modal və Blade formu ilə create/edit etmək;
- çoxdilli `name`, `description`, `slug` saxlamaq;
- parent seçmək və cycle yaranmasını bloklamaq;
- eyni parent daxilində drag-and-drop reorder etmək;
- node-u başqa parent altına təhlükəsiz daşımaq;
- aktivlik və təqdimat sahələrini idarə etmək;
- istifadə olunan və child-ı olan kateqoriyanın silinmə qaydasını qorumaq;
- hər mutasiyanı server-side validation və permission ilə qorumaq.

Index ağacı lazy-load edilir. Edit formunda parent selector üçün bütün ağac lazım ola bilər. Böyük dataset-də bütün ağacı recursive eager-load etmək əvəzinə axtarışlı AJAX tree-select istifadə edilməlidir.

## 2. Tövsiyə olunan fayl strukturu

```text
app/
├── Http/Controllers/Gopanel/Category/
│   └── CategoryController.php
├── Http/Requests/Gopanel/Category/
│   ├── CategorySaveRequest.php
│   ├── CategoryMoveRequest.php
│   └── CategorySortRequest.php
├── Models/Category/
│   └── Category.php
├── Services/Category/
│   └── CategoryService.php
├── Queries/Gopanel/Category/
│   └── CategoryTreeQuery.php             # ağac böyük/mürəkkəbdirsə
└── Policies/
    └── CategoryPolicy.php

resources/views/gopanel/pages/categories/
├── index.blade.php
├── partials/
│   ├── node.blade.php
│   ├── children.blade.php
│   └── form.blade.php
└── inc/
    └── modal.blade.php

public/assets/gopanel/js/modules/
└── categories.js

database/
├── migrations/*_create_categories_table.php
├── seeders/CategorySeeder.php
└── seeders/data/categories.json           # böyük ağac üçün

tests/Feature/Gopanel/Category/
└── CategoryControllerTest.php
```

Sadə layihədə service/query sinifləri məcburi deyil. Controller-də biznes qaydaları böyüyürsə service, ağac query-ləri böyüyürsə query class ayrılmalıdır.

## 3. Database strukturu

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->uuid('uid')->unique();
    $table->string('key', 100)->unique();
    $table->foreignId('parent_id')
        ->nullable()
        ->constrained('categories')
        ->nullOnDelete();

    $table->string('type', 30)->default('both');
    $table->string('icon')->nullable();
    $table->string('icon_type', 20)->default('font');
    $table->string('color', 20)->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->boolean('show_in_home')->default(false);
    $table->boolean('show_in_menu')->default(false);
    $table->unsignedInteger('home_order')->default(0);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['parent_id', 'sort_order']);
    $table->index(['is_active', 'show_in_menu']);
    $table->index(['is_active', 'show_in_home', 'home_order']);
});
```

Aquastores-də `name`, `description`, `slug` polymorphic `field_translations` cədvəlində saxlanılır. Başqa layihədə translation yoxdursa bu sütunlar `categories` cədvəlinə əlavə edilə bilər.

Qərarlar əvvəlcədən müəyyən edilməlidir:

- parent silinəndə child-lar root-a keçir (`nullOnDelete`), subtree silinir, yoxsa silmə bloklanır;
- maksimum ağac dərinliyi varmı;
- slug hər dil üzrə global unikaldır, yoxsa eyni parent daxilində unikaldır;
- eyni kateqoriya birdən çox parent altında ola bilərmi; ola bilərsə adjacency list deyil pivot/closure table lazımdır.

## 4. Model strukturu

```php
class Category extends BaseModel
{
    use SoftDeletes, AddUuid, Translation;

    public array $translatedAttributes = ['name', 'description', 'slug'];
    public string $slug_key = 'name';

    protected $fillable = [
        'uid', 'key', 'parent_id', 'type', 'icon', 'icon_type', 'color',
        'sort_order', 'is_active', 'show_in_home', 'show_in_menu',
        'home_order',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'home_order' => 'integer',
        'is_active' => 'boolean',
        'show_in_home' => 'boolean',
        'show_in_menu' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
```

`childrenRecursive` yalnız parent dropdown, export və tree traversal lazım olduqda istifadə edilməlidir. Index-də yalnız `withCount('children')` istifadə olunmalıdır.

DB-də cycle constraint qurmaq çətindir; cycle müdafiəsi server-side service daxilində edilməlidir.

## 5. Route müqaviləsi

Tövsiyə olunan route-lar:

```php
Route::prefix('categories')
    ->name('categories.')
    ->middleware('can:gopanel.categories.view')
    ->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/{category}/children', [CategoryController::class, 'children'])
            ->name('children');

        Route::get('/get/form/{category?}', [CategoryController::class, 'getForm'])
            ->middleware('can:gopanel.categories.edit')
            ->name('get.form');

        Route::post('/save/{category?}', [CategoryController::class, 'save'])
            ->middleware('can:gopanel.categories.edit')
            ->name('save');

        Route::patch('/sort', [CategoryController::class, 'sort'])
            ->middleware('can:gopanel.categories.edit')
            ->name('sort');

        Route::patch('/{category}/move', [CategoryController::class, 'move'])
            ->middleware('can:gopanel.categories.edit')
            ->name('move');

        Route::patch('/{category}/toggle', [CategoryController::class, 'toggle'])
            ->middleware('can:gopanel.categories.edit')
            ->name('toggle');

        Route::delete('/{category}', [CategoryController::class, 'destroy'])
            ->middleware('can:gopanel.categories.delete')
            ->name('destroy');
    });
```

Mövcud layihə `get/form/{item?}` və `save/{item?}` istifadə edə bilər. Standart Laravel optional binding-in parametr olmayanda avtomatik `new Category()` yaratdığı güman edilməməlidir. Təhlükəsiz controller forması:

```php
public function getForm(?string $id = null)
{
    $category = $id ? Category::resolveByKey($id) : new Category();
}
```

Generic endpoint client-dən PHP class adı və sütun adı qəbul etməməlidir. Sort, toggle və delete ya Category controller-də olmalı, ya da server-side whitelist edilmiş lookup key ilə generic service-ə ötürülməlidir.

## 6. FormRequest-lər

### Save request

```php
final class CategorySaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('gopanel')?->can('gopanel.categories.edit') === true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['required', Rule::in(['product', 'service', 'both'])],
            'icon_type' => ['required', Rule::in(['font', 'image'])],
            'icon' => ['nullable', 'string', 'max:255'],
            'icon_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['required', 'boolean'],
            'show_in_home' => ['required', 'boolean'],
            'show_in_menu' => ['required', 'boolean'],
            'home_order' => ['nullable', 'integer', 'min:0'],
            'name' => ['required', 'array'],
            'name.*' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:5000'],
            'slug' => ['nullable', 'array'],
            'slug.*' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
```

Default dil üçün `name` ayrıca required edilməli, dil key-ləri aktiv dil kodları ilə whitelist edilməlidir. SVG qəbul edilirsə sanitize edilməlidir; sanitize infrastrukturu yoxdursa SVG qəbul edilməməlidir.

### Sort request

```php
return [
    'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
    'orders' => ['required', 'array', 'min:1'],
    'orders.*' => [
        'required', 'distinct', 'uuid',
        Rule::exists('categories', 'uid')->whereNull('deleted_at'),
    ],
];
```

### Move request

```php
return [
    'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
    'position' => ['nullable', 'integer', 'min:0'],
];
```

## 7. Query və service məsuliyyəti

### CategoryTreeQuery

```php
final class CategoryTreeQuery
{
    public function roots(): Collection
    {
        return Category::query()
            ->roots()
            ->withCount('children')
            ->ordered()
            ->get();
    }

    public function children(Category $category): Collection
    {
        return $category->children()
            ->withCount('children')
            ->get();
    }

    public function parentOptions(?Category $editing = null): array
    {
        // Kiçik ağac üçün recursive eager-load + flatten.
        // Böyük ağac üçün AJAX tree-select istifadə et.
    }
}
```

### CategoryService

Service aşağıdakı biznes qaydalarını saxlamalıdır:

- save və translation/media/meta yazılmasını transaction daxilində aparmaq;
- parent-in category-nin özü və descendant-ı olmadığını yoxlamaq;
- maksimum depth qaydasını yoxlamaq;
- move zamanı köhnə və yeni sibling sıralarını normallaşdırmaq;
- silmə strategiyasını tətbiq etmək;
- cache invalidate etmək.

```php
final class CategoryService
{
    public function save(Category $category, array $data, CategorySaveRequest $request): Category
    {
        return DB::transaction(function () use ($category, $data, $request) {
            $this->assertValidParent($category, $data['parent_id'] ?? null);

            $category->fill(Arr::except($data, [
                'name', 'description', 'slug', 'icon_image', 'meta',
            ]));

            if (!$category->exists) {
                $category->sort_order = $this->nextOrder($category->parent_id);
            }

            $category->save();
            $this->storeIcon($category, $request);
            TranslationHelper::create($category, $request);
            PageMetaDataHelper::save($category, $request->input('meta', []), $request->file('meta', []));

            return $category->fresh();
        });
    }

    private function assertValidParent(Category $category, ?int $parentId): void
    {
        if ($parentId === null) return;
        if ($category->exists && $category->id === $parentId) {
            throw ValidationException::withMessages(['parent_id' => 'Kateqoriya öz parent-i ola bilməz.']);
        }
        if ($category->exists && in_array($parentId, $this->descendantIds($category), true)) {
            throw ValidationException::withMessages(['parent_id' => 'Kateqoriya alt kateqoriyasına daşına bilməz.']);
        }
    }
}
```

Fayl yazılması DB transaction ilə tam rollback edilmir. Upload əvvəl uğurlu olub DB rollback edərsə orphan faylı silmək üçün cleanup mexanizmi olmalıdır.

## 8. Controller strukturu

Controller nazik olmalıdır:

```php
final class CategoryController extends GoPanelController
{
    public function __construct(
        private readonly CategoryTreeQuery $tree,
        private readonly CategoryService $service,
    ) {}

    public function index(): View
    {
        return view('gopanel.pages.categories.index', [
            'categories' => $this->tree->roots(),
        ]);
    }

    public function children(Category $category, Request $request): JsonResponse
    {
        $html = view('gopanel.pages.categories.partials.children', [
            'children' => $this->tree->children($category),
            'depth' => max(1, (int) $request->integer('depth', 1)),
        ])->render();

        return response()->json(['status' => 'success', 'html' => $html]);
    }

    public function getForm(?string $id = null): JsonResponse
    {
        $category = $id ? Category::resolveByKey($id) : new Category();
        abort_unless($category, 404);

        return response()->json([
            'status' => 'success',
            'html' => view('gopanel.pages.categories.partials.form', [
                'item' => $category,
                'route' => route('gopanel.categories.save', $category->identifier_id),
                'parents' => $this->tree->parentOptions($category->exists ? $category : null),
                'languages' => Language::active()->ordered()->get(),
            ])->render(),
        ]);
    }

    public function save(CategorySaveRequest $request, ?string $id = null): JsonResponse
    {
        $category = $id ? Category::resolveByKey($id) : new Category();
        abort_unless($category, 404);
        $saved = $this->service->save($category, $request->validated(), $request);

        return response()->json([
            'status' => 'success',
            'message' => $category->wasRecentlyCreated ? 'Kateqoriya yaradıldı.' : 'Kateqoriya yeniləndi.',
            'redirect' => route('gopanel.categories.index'),
            'data' => ['uid' => $saved->uid],
        ]);
    }

    public function sort(CategorySortRequest $request): JsonResponse
    {
        $this->service->sort($request->validated());
        return response()->json(['status' => 'success', 'message' => 'Sıralama yeniləndi.']);
    }

    public function move(CategoryMoveRequest $request, Category $category): JsonResponse
    {
        $this->service->move($category, $request->validated());
        return response()->json(['status' => 'success', 'message' => 'Kateqoriya daşındı.']);
    }
}
```

Validation `422`, permission `403`, tapılmayan model `404` qaytarmalıdır. Exception mesajını olduğu kimi client-ə çıxarmaq production üçün düzgün deyil; gözlənilməyən xəta loglanmalı və generik mesaj qaytarılmalıdır.

## 9. Blade strukturu

### Index

```blade
@extends('gopanel.layouts.main')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="page-title-box d-flex justify-content-between">
            <h4>Kateqoriyalar</h4>
            @can('gopanel.categories.add')
                <button type="button" id="open-create-modal"
                        class="btn btn-success"
                        data-route="{{ route('gopanel.categories.get.form') }}">
                    Əlavə et
                </button>
            @endcan
        </div>

        <div id="category-tree"
             data-sort-url="{{ route('gopanel.categories.sort') }}">
            @forelse($categories as $category)
                @include('gopanel.pages.categories.partials.node', [
                    'category' => $category,
                    'depth' => 0,
                ])
            @empty
                <div class="card"><div class="card-body text-center text-muted">Kateqoriya tapılmadı</div></div>
            @endforelse
        </div>
    </div>
</div>

@include('gopanel.pages.categories.inc.modal')
@endsection
```

Root və child üçün ayrı-ayrı böyük markup təkrarlamaq əvəzinə ortaq `node.blade.php` istifadə edilməsi daha təhlükəsizdir.

### Node partial

```blade
<article class="category-node card mb-2"
         data-id="{{ $category->uid }}"
         data-parent-id="{{ $category->parent_id }}">
    <header class="category-node-header d-flex align-items-center gap-2">
        <button type="button" class="category-drag-handle btn btn-link"
                aria-label="{{ $category->name }} sırasını dəyiş">
            <i class="fas fa-grip-vertical"></i>
        </button>

        @if($category->children_count > 0)
            <button type="button" class="category-toggle btn btn-link"
                    aria-expanded="false"
                    data-children-url="{{ route('gopanel.categories.children', $category) }}?depth={{ $depth + 1 }}">
                <i class="fas fa-chevron-right toggle-icon"></i>
            </button>
        @endif

        <span class="category-name flex-grow-1">{{ $category->name }}</span>

        @can('gopanel.categories.edit')
            <a class="edit btn btn-sm btn-outline-success"
               href="{{ route('gopanel.categories.get.form', $category->uid) }}">Düzəliş et</a>
        @endcan

        @can('gopanel.categories.delete')
            <button type="button" class="category-delete btn btn-sm btn-outline-danger"
                    data-url="{{ route('gopanel.categories.destroy', $category->uid) }}">Sil</button>
        @endcan
    </header>

    <div class="category-children"
         data-parent-id="{{ $category->id }}"
         data-loaded="0"
         hidden></div>
</article>
```

PHP model class və dəyişdirilə bilən sütun adı DOM-a yazılmamalıdır. DOM yalnız resource UID, endpoint URL və görünüş metadata-sı daşımalıdır.

### Children partial

```blade
@foreach($children as $category)
    @include('gopanel.pages.categories.partials.node', [
        'category' => $category,
        'depth' => $depth,
    ])
@endforeach
```

### Form partial

Formun kökü həmişə eyni contract-a sahib olmalıdır:

```blade
<form id="data-form" action="{{ $route }}" enctype="multipart/form-data">
    {{-- language tabs: name[az], description[az], slug[az] --}}
    {{-- icon_type + font icon/image upload --}}
    {{-- color --}}
    {{-- parent_id --}}
    {{-- is_active, show_in_home, show_in_menu --}}
    {{-- home_order --}}
    {{-- meta fields --}}
</form>
```

Qaydalar:

- label/input `for` və `id` ilə bağlanmalıdır;
- default dilin adı required işarələnməlidir;
- edit olunan node və bütün descendant-ları parent select-dən çıxarılmalıdır;
- `icon_type=image` olduqda font input, `font` olduqda file input gizlənməlidir;
- mövcud media preview və remove seçimi olmalıdır;
- server `422` xətaları input yanında göstərilməlidir;
- yeni qeyd default-ları backend və frontend-də eyni olmalıdır.

## 10. JavaScript və AJAX strukturu

Category modulu aşağıdakı state-i saxlamalıdır:

```javascript
$(function () {
    var state = {
        loadingChildren: new Set(),
        pendingSort: null,
        pendingMove: null
    };
});
```

### Category CSS nümunəsi

Bu stillər `resources/views/.../categories/index.blade.php` daxilində `@push('styles')` ilə və ya ayrıca `categories.css` faylında saxlanıla bilər:

```css
.category-tree {
    position: relative;
}

.category-node {
    border: 1px solid #e9edf3;
    border-radius: .5rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
}

.category-node-header {
    min-height: 48px;
    padding: .5rem .75rem;
    background: #fff;
}

.category-children {
    margin-left: 1.25rem;
    border-left: 2px solid #e5ecf9;
}

.category-children > .category-node {
    margin: .25rem 0 .25rem .75rem;
    box-shadow: none;
}

.category-drag-handle {
    cursor: grab;
    color: #98a2b3;
}

.category-drag-handle:active {
    cursor: grabbing;
}

.category-node-placeholder {
    min-height: 48px;
    margin-bottom: .5rem;
    background: #eef4ff;
    border: 2px dashed #7ba7eb;
    border-radius: .5rem;
}

.category-node.is-saving {
    opacity: .65;
    pointer-events: none;
}

.category-tree-loader {
    position: absolute;
    inset: 0;
    z-index: 20;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 3rem;
    background: rgba(255, 255, 255, .65);
    backdrop-filter: blur(1px);
}

.category-inline-loader {
    display: inline-flex;
    align-items: center;
    gap: .375rem;
    color: #667085;
    font-size: .8125rem;
}

.category-toggle[aria-busy="true"] {
    pointer-events: none;
}

@media (max-width: 767.98px) {
    .category-node-header {
        align-items: flex-start !important;
        flex-wrap: wrap;
    }

    .category-children {
        margin-left: .5rem;
    }

    .category-node-actions {
        width: 100%;
        padding-left: 2.5rem;
    }
}
```

Tree wrapper daxilində əməliyyat loader-i üçün shell:

```blade
<div id="category-tree" class="category-tree" data-sort-url="...">
    <div class="category-tree-loader" hidden>
        <div class="text-center">
            <span class="spinner-border text-primary" role="status"></span>
            <div class="small text-muted mt-2">Yadda saxlanılır...</div>
        </div>
    </div>
    {{-- category nodes --}}
</div>
```

### Child lazy-load

```javascript
$(document).on('click', '.category-toggle', function () {
    var $button = $(this);
    var $node = $button.closest('.category-node');
    var $container = $node.children('.category-children');
    var url = $button.data('children-url');

    if ($container.data('loaded') === 1) {
        toggleChildren($button, $container);
        return;
    }

    if ($button.data('loading')) return;
    $button.data('loading', true).prop('disabled', true).attr('aria-busy', 'true');
    setToggleLoading($button, true);

    $.ajax({ url: url, type: 'GET', dataType: 'json' })
        .done(function (response) {
            if (response.status !== 'success') throw new Error(response.message || 'Yükləmə xətası');
            $container.html(response.html).data('loaded', 1);
            initSortable($container);
            initDynamicUi($container);
            showChildren($button, $container);
        })
        .fail(function (xhr) {
            showError(xhr);
        })
        .always(function () {
            $button.data('loading', false).prop('disabled', false).attr('aria-busy', 'false');
            setToggleLoading($button, false);
        });
});
```

Eyni node üçün paralel iki children request göndərilməməlidir. Xəta olduqda `data-loaded=0` qalmalıdır ki, retry mümkün olsun.

Child məlumatı gələnə qədər toggle chevron spinner ilə əvəz edilməlidir:

```javascript
function setToggleLoading($button, loading) {
    var $icon = $button.find('.toggle-icon');
    $icon.toggleClass('fa-chevron-right fa-chevron-down', !loading);
    $icon.toggleClass('fa-spinner fa-spin', loading);
}
```

### Sortable

Serverə serialize olunmuş DOM string deyil, açıq UID array göndərmək daha yaxşıdır:

```javascript
function readOrder($container) {
    return $container.children('.category-node').map(function () {
        return $(this).data('id');
    }).get();
}

function initSortable($container) {
    var previousOrder = [];

    $container.sortable({
        items: '> .category-node',
        handle: '.category-drag-handle',
        placeholder: 'category-node-placeholder',
        tolerance: 'pointer',
        start: function () {
            previousOrder = readOrder($container);
        },
        update: function (event, ui) {
            if (ui.sender) return;
            saveOrder($container, previousOrder);
        }
    });
}

function saveOrder($container, previousOrder) {
    $container.sortable('disable');
    setTreeLoading(true, 'Sıralama yadda saxlanılır...');
    $.ajax({
        url: $('#category-tree').data('sort-url'),
        type: 'PATCH',
        dataType: 'json',
        data: {
            parent_id: $container.data('parent-id') || null,
            orders: readOrder($container)
        }
    }).fail(function (xhr) {
        restoreOrder($container, previousOrder);
        showError(xhr);
    }).always(function () {
        $container.sortable('enable');
        setTreeLoading(false);
    });
}
```

```javascript
function setTreeLoading(loading, message) {
    var $loader = $('#category-tree .category-tree-loader');
    $loader.prop('hidden', !loading);
    if (message) $loader.find('.small').text(message);
    $('#category-tree').attr('aria-busy', loading ? 'true' : 'false');
}
```

### Cross-parent move

Move request yeni parent və yeni position-u birlikdə göndərməlidir. Backend move və hər iki sibling qrupunun reorder-ini bir transaction-da etməlidir.

Frontend əvvəlki parent/order snapshot saxlamalı və request uğursuz olduqda node-u əvvəlki yerə qaytarmalıdır. Move və sortable callback-lərinin eyni drag üçün iki ayrı zidd request göndərməməsinə diqqət edilməlidir.

Move request zamanı da `setTreeLoading(true, 'Kateqoriya daşınır...')` çağırılmalı, `.always()` daxilində söndürülməlidir. Beləliklə Category-də üç loader səviyyəsi olur:

1. form AJAX ilə açılarkən modal body loader-i;
2. child-lar gələnə qədər node toggle spinner-i;
3. sort/move saxlanarkən tree overlay loader-i.

### Create/edit modal

Ortaq `crud.js` istifadə edilirsə contract:

```text
#open-create-modal[data-route]
.edit[href]
#lookup-form-modal
#form-wrap
#data-form[action]
#save-form-btn
```

Save zamanı `FormData`, `processData:false`, `contentType:false` istifadə edilməlidir. Düymə request müddətində disable edilməli, double submit bloklanmalı, `422` field errors göstərilməlidir.

Modal form yüklənənə qədər:

```javascript
$('#form-wrap').html(
    '<div class="text-center py-5">' +
        '<span class="spinner-border text-primary" role="status"></span>' +
        '<div class="text-muted small mt-2">Form yüklənir...</div>' +
    '</div>'
);
```

CSRF token layout-dakı `<meta name="csrf-token">`-dan global `$.ajaxSetup` ilə göndərilə bilər.

## 11. Sort və move backend qaydası

```php
public function sort(array $data): void
{
    DB::transaction(function () use ($data) {
        $siblings = Category::query()
            ->where('parent_id', $data['parent_id'])
            ->whereIn('uid', $data['orders'])
            ->lockForUpdate()
            ->get()
            ->keyBy('uid');

        if ($siblings->count() !== count($data['orders'])) {
            throw ValidationException::withMessages(['orders' => 'Siyahı natamamdır.']);
        }

        foreach ($data['orders'] as $index => $uid) {
            $siblings[$uid]->update(['sort_order' => $index + 1]);
        }
    });
}
```

Göndərilən bütün node-ların eyni parent-ə aid olduğu yoxlanmalıdır. Başqa parent-in UID-sini daxil edib icazəsiz reorder etmək mümkün olmamalıdır.

## 12. Delete strategiyası

Layihə aşağıdakılardan birini açıq seçməlidir:

1. Child və bağlı obyekt varsa delete bloklanır — admin sistemləri üçün ən təhlükəsiz default.
2. Child-lar root-a daşınır — DB `nullOnDelete` davranışı.
3. Bütün subtree soft-delete olunur — service-də recursive transaction tələb edir.

Tövsiyə:

```php
if ($category->children()->exists()) {
    throw ValidationException::withMessages(['category' => 'Alt kateqoriyası olan kateqoriya silinə bilməz.']);
}

if ($category->listings()->exists()) {
    throw ValidationException::withMessages(['category' => 'İstifadə olunan kateqoriyanı deaktiv edin.']);
}
```

Hard delete UI-dan göndərilən flag ilə açılmamalıdır.

## 13. Seeder necə yazılmalıdır

Seeder təkrar işlədikdə duplicate yaratmamalı, parent əvvəl child sonra yaradılmalı və translation-lar ayrıca upsert edilməlidir.

Kiçik ağac nümunəsi:

```php
final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->data() as $index => $node) {
                $this->seedNode($node, null, $index + 1);
            }
        });
    }

    private function seedNode(array $node, ?int $parentId, int $order): Category
    {
        $category = Category::withTrashed()->updateOrCreate(
            ['key' => $node['key']],
            [
                'parent_id' => $parentId,
                'type' => $node['type'] ?? 'both',
                'icon' => $node['icon'] ?? null,
                'icon_type' => $node['icon_type'] ?? 'font',
                'color' => $node['color'] ?? null,
                'sort_order' => $order,
                'is_active' => $node['is_active'] ?? true,
                'show_in_home' => $node['show_in_home'] ?? false,
                'show_in_menu' => $node['show_in_menu'] ?? true,
                'home_order' => $node['home_order'] ?? 0,
            ]
        );

        if ($category->trashed()) $category->restore();

        foreach ($node['translations'] as $locale => $translation) {
            TranslationHelper::basic($category, [$locale => $translation['name']], 'name');
            TranslationHelper::basic(
                $category,
                [$locale => $translation['slug'] ?? Str::slug($translation['name'])],
                'slug'
            );
            TranslationHelper::basic(
                $category,
                [$locale => $translation['description'] ?? null],
                'description'
            );
        }

        foreach ($node['children'] ?? [] as $index => $child) {
            $this->seedNode($child, $category->id, $index + 1);
        }

        return $category;
    }
}
```

Data nümunəsi:

```php
private function data(): array
{
    return [
        [
            'key' => 'equipment',
            'icon' => 'fas fa-tools',
            'color' => '#1565C0',
            'translations' => [
                'az' => ['name' => 'Avadanlıqlar', 'slug' => 'avadanliqlar'],
                'en' => ['name' => 'Equipment', 'slug' => 'equipment'],
            ],
            'children' => [
                [
                    'key' => 'pumps',
                    'translations' => [
                        'az' => ['name' => 'Nasoslar', 'slug' => 'nasoslar'],
                        'en' => ['name' => 'Pumps', 'slug' => 'pumps'],
                    ],
                ],
            ],
        ],
    ];
}
```

Stabil matching üçün translation name istifadə etmək zəifdir: ad dəyişəndə yeni qeyd yarana bilər. Buna görə nümunədə ayrıca unikal `key` sütunu istifadə edilir. Mövcud schema dəyişdirilə bilmirsə ayrıca unikal `code`/`seed_key` sütunu əlavə edilməlidir. Böyük ağac `database/seeders/data/categories.json` faylından oxuna bilər.

Seeder qaydaları:

- `create()` ilə kor-koranə duplicate yaratma;
- `updateOrCreate()` üçün stabil texniki açar istifadə et;
- soft-delete olunmuş qeydi tap və restore et;
- slug unikallığını DB constraint ilə də qoru;
- yalnız aktiv dillərin translation-larını yaz;
- media faylını təkrar kopyalama;
- parent icon-u child-lara məcburi tətbiq etmə;
- seeder transaction istifadə et, lakin böyük fayl/media əməliyyatında chunk strategiyasını nəzərə al;
- mock/demo seeder-i production foundation seeder-dən ayır.

## 14. Cache və performans

- `parent_id, sort_order` composite index olmalıdır;
- children endpoint yalnız direct child və `children_count` gətirməlidir;
- index bütün recursive tree-ni eager-load etməməlidir;
- parent dropdown böyükdürsə server-side search istifadə etməlidir;
- public category tree cache edilə bilər;
- save/move/sort/toggle/delete zamanı category tree cache invalidasiya edilməlidir;
- N+1 translation və children count query-ləri test edilməlidir.

## 15. Test checklist

Backend:

- permission olmayan admin read/mutate edə bilmir;
- root index yalnız root node-ları gətirir;
- children endpoint yalnız direct child qaytarır;
- create/edit və translation işləyir;
- invalid parent `422` qaytarır;
- category öz parent-i ola bilmir;
- category descendant altına daşına bilmir;
- maksimum depth qorunur;
- sort yalnız eyni parent sibling-lərini qəbul edir;
- sort UID-ləri distinct olmalıdır;
- move köhnə/yeni sibling sırasını normallaşdırır;
- child və ya bağlı obyekt olan category delete qaydasına uyur;
- upload validation və orphan cleanup işləyir;
- seeder ikinci dəfə işləyəndə duplicate yaratmır;
- cache mutasiyadan sonra təmizlənir.

Frontend:

- node ilk expand-da bir dəfə yüklənir;
- loading zamanı ikinci request getmir;
- xəta sonrası retry mümkündür;
- inject edilən node-larda tooltip/toggle/sort işləyir;
- sort xətasında DOM rollback edir;
- move xətasında node əvvəlki parent/position-a qayıdır;
- double submit bloklanır;
- `422` xətaları input yanında göstərilir;
- mobil görünüş istifadə oluna bilir;
- drag üçün klaviatura alternativi mövcuddur.

## 16. Yekun qəbul meyarları

Implementasiya hazır sayılır, əgər:

- index sürətli açılır və tree lazy-load olunur;
- Blade node markup-ı təkrarlanmır;
- controller request/query/service məsuliyyətlərini qarışdırmır;
- bütün mutasiyalar validated və permission ilə qorunur;
- client PHP model class və database column göndərmir;
- create/edit modalı vahid AJAX contract ilə işləyir;
- sort və move transaction + frontend rollback ilə təhlükəsizdir;
- delete strategiyası açıq müəyyən edilib;
- translation/media/meta axını layihənin real ehtiyacına uyğunlaşdırılıb;
- seeder idempotent və recursive-dir;
- başqa layihədə `Category` əvəzinə başqa tree entity ilə tətbiq edilə bilir.
