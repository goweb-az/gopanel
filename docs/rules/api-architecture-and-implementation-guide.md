# Laravel API arxitekturası və tətbiq bələdçisi

## Sənədin məqsədi

Bu sənəd başqa Laravel layihəsində web frontend və mobil tətbiq üçün API qatını quracaq proqramçı və ya süni intellekt modeli üçün texniki tapşırıqdır. Məqsəd Aquastores-dəki işlək yanaşmaları saxlamaq, mövcud təkrarları və təhlükəli hissələri isə yeni layihəyə daşımamaqdır.

Bu sənəd yalnız endpoint siyahısı deyil. Route, middleware, autentifikasiya, Form Request, controller, query, service, repository, resource, response, exception, config, enum, fayl yükləmə, keş, log, queue, təhlükəsizlik, versiyalama, test və API sənədləşdirməsinin sərhədlərini müəyyən edir.

> **Vacib qeyd:** sənəddə işlədilən `Product` modeli və ona aid class/route adları yalnız neytral texniki nümunədir. Hədəf layihədə product modulu yoxdursa bunlar həmin layihənin real domen resursu (`Article`, `Order`, `Service`, `Customer` və s.) ilə əvəz edilməlidir. Bu sənəd heç bir konkret CRUD modulunun yaradılmasını tələb etmir.

## Mövcud layihənin qısa analizi

Layihədə API iki ayrıca client müqaviləsinə bölünüb:

- `api/mobile/v1` — mobil tətbiq;
- `api/web/v1` — web frontend.

`RouteServiceProvider` hər ikisinə `api` middleware qrupunu, sonra uyğun olaraq `mobile.api` və `web.api` middleware-ni tətbiq edir. Qorunan mobil route-lar `mobile.auth`, qorunan web route-lar `web.auth` istifadə edir. JWT əməliyyatları `JwtService` üzərindən aparılır. Dil `Accept-Language` header-indən seçilir və cavaba `Content-Language` əlavə olunur.

Kod bazasında təxminən aşağıdakı paralel struktur mövcuddur:

- 82 mobil və 88 web API controller;
- 77 mobil və 76 web Form Request;
- 97 mobil və 104 web Resource.

Müsbət cəhətlər:

- URL-də client və versiya aydın görünür;
- route-lar domenlərə görə qruplaşdırılıb;
- statik route-lar wildcard route-lardan əvvəl yazılıb;
- `{product:uid}` kimi explicit route model binding istifadə olunur;
- validation controller-dən Form Request-ə çıxarılıb;
- təqdimat Resource qatında saxlanılır;
- əməliyyatlar Service, yazma/query məntiqi Repository və Query qatlarına bölünür;
- siyahılarda pagination və eager loading nəzərə alınır;
- mobil middleware həm optional, həm məcburi auth ssenarisini dəstəkləyir.

Yeni layihəyə olduğu kimi daşınmamalı hissələr:

- web və mobile üçün eyni controller/request/resource siniflərinin kütləvi surətdə kopyalanması;
- mobile və web cavab envelope-larının fərqli olması (`success: true` və `status: success`);
- hər controller metodunda `try/catch (Throwable)` yazılması;
- exception kodunu HTTP status kimi qəbul etmək;
- middleware auth xətasının ümumi response factory-dən kənarda əl ilə yaradılması;
- JWT üçün çox uzun default TTL;
- production CORS üçün `allowed_origins = ['*']`;
- public response-da daxili database `id`-lərinin client-ə verilməsi;
- bəzi resource-larda eyni məlumatın `location` və `locations` kimi təkrarlanması;
- service içində dependency container əvəzinə `new LogService(...)` işlədilməsi;
- böyük repository-lərdə query, mutation, upload və biznes qaydalarının qarışması;
- API ölçüsü ilə müqayisədə az feature/contract test.

## Əsas arxitektura qərarı: client-ə görə yox, domenə görə paylaş

Web və mobile eyni biznes obyektləri və eyni response müqaviləsindən istifadə edirsə, ayrıca sinif yaratmaq olmaz. Əvvəl ortaq API yazılmalıdır. Yalnız real müqavilə fərqi varsa client adapteri yaradılmalıdır.

Tövsiyə edilən qovluq strukturu:

```text
app/
├── Actions/
│   └── Product/CreateProductAction.php
├── DTOs/
│   └── Product/CreateProductData.php
├── Enums/
│   └── Product/ProductCondition.php
├── Exceptions/
│   └── Domain/
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── Auth/
│   │   ├── Account/
│   │   └── Marketplace/
│   ├── Middleware/Api/
│   ├── Requests/Api/V1/
│   ├── Resources/Api/V1/
│   └── Responses/Api/
├── Policies/
├── Queries/
│   └── Product/ListProductsQuery.php
├── Repositories/
│   └── Product/ProductRepository.php
├── Services/
│   ├── Auth/
│   ├── Product/
│   └── Media/
└── Support/Api/
    ├── ApiErrorCode.php
    └── PaginationMeta.php

routes/
└── api/
    ├── v1.php
    ├── v1/
    │   ├── public.php
    │   ├── auth.php
    │   ├── account.php
    │   └── marketplace.php
    └── mobile-v1.php      # yalnız həqiqi mobil fərqləri varsa
```

Client-lər tam fərqli release dövrünə və payload-a malikdirsə `/api/web/v1` və `/api/mobile/v1` saxlanıla bilər. Bu halda belə Service, Action, Query, Repository, Policy və əksər Request ortaq qalmalıdır; yalnız Controller/Resource adapterləri ayrılmalıdır.

## Sorğunun tam axını

```text
HTTP request
  -> global middleware (proxy, CORS, maintenance, body size)
  -> api middleware (rate limit, bindings)
  -> locale/client/version middleware
  -> optional və ya required authentication
  -> Form Request: normalize + authorize + validate
  -> Controller: use-case çağırışı
  -> Action/Service: biznes qaydası və transaction
  -> Query/Repository: database əməliyyatı
  -> API Resource: public təqdimat
  -> vahid Response Factory
  -> JSON response + request ID + Content-Language
```

Controller heç vaxt request-dən başlayıb bütün biznes və database işini özü görməməlidir.

## Route sistemi

### URL və versiyalama

İlk stabil müqavilə `/api/v1` altında qurulmalıdır. Versiya route faylı və namespace-də görünməlidir. `v2` yalnız backward incompatible dəyişiklik olduqda açılır. Sadəcə yeni optional field əlavə etmək üçün yeni versiya yaradılmır.

```php
Route::prefix('api/v1')
    ->name('api.v1.')
    ->middleware(['api', 'api.context'])
    ->group(base_path('routes/api/v1.php'));
```

`routes/api/v1.php` kiçik domen fayllarını daxil etməlidir:

```php
require __DIR__.'/v1/public.php';
require __DIR__.'/v1/auth.php';
require __DIR__.'/v1/marketplace.php';
require __DIR__.'/v1/account.php';
```

### REST adlandırması

```text
GET    /products                 siyahı
POST   /products                 yaratmaq
GET    /products/{product:uid}   detal
PUT    /products/{product:uid}   tam yeniləmə
PATCH  /products/{product:uid}   qismən yeniləmə
DELETE /products/{product:uid}   silmək
PATCH  /products/{product:uid}/status
PATCH  /products/{product:uid}/media/order
```

Action endpoint-ləri yalnız resurs vəziyyəti sadə CRUD ilə ifadə edilmədikdə işlədilir: `/publish`, `/cancel`, `/approve`. `toggle` rahat olsa da idempotent deyil; mümkün olduqda `PUT /favorites/{product:uid}` və `DELETE /favorites/{product:uid}` seçilməlidir.

Qaydalar:

- public, optional-auth və protected route qrupları açıq ayrılmalıdır;
- statik segmentlər `{id}` wildcard-dan əvvəl yazılmalıdır;
- `whereUuid`, `whereNumber` və ya explicit binding tətbiq edilməlidir;
- public identifier kimi `uid/uuid/slug` istifadə edilməli, daxili `id` gizli qalmalıdır;
- route adı mütləq verilməlidir;
- controller class import edilməli, string controller yazılmamalıdır;
- böyük bir route faylı yüzlərlə endpoint saxlamamalıdır;
- route cache ilə uyğun closure-siz production route-lar seçilməlidir.

## Mobil və web API fərqi necə idarə edilməlidir

Ayrılmalı nümunələr:

- mobil app version check, platform, device ID və push token;
- mobil üçün daha kiçik payload və image variantları;
- web üçün SEO URL, breadcrumb və desktop filter metadata;
- client-ə xas auth üsulu və release compatibility header-i.

Ortaq qalmalı nümunələr:

- product yaratma və status dəyişmə biznes qaydası;
- validation qaydalarının əsas hissəsi;
- authorization və ownership;
- database query və transaction;
- error kodları və əsas response envelope;
- enum və domen modelləri.

Eyni endpoint yalnız field seçiminə görə dəyişirsə iki controller kopyalamaq əvəzinə client context və conditional Resource field istifadə edilə bilər. Client adı body-dən etibarlı sayılmamalı; route group, signed app header və ya server tərəfindən tanınan context-dən gəlməlidir.

## Middleware arxitekturası

Middleware yalnız bütün request-ə aid HTTP concern daşımalıdır:

- CORS;
- rate limit;
- locale;
- authentication;
- request/correlation ID;
- client platform və app version yoxlaması;
- response header-ləri;
- maintenance və payload ölçüsü.

Biznes qaydası middleware-də olmamalıdır. Məsələn “yalnız aktiv satıcı product yarada bilər” Policy və ya Service qaydasıdır.

Tövsiyə edilən middleware ardıcıllığı:

```text
HandleCors
RequestId
ThrottleRequests
ResolveApiLocale
ResolveClientContext
AuthenticateApi (yalnız protected qrup)
SubstituteBindings
```

`Accept-Language: az-Latn-AZ,az;q=0.9,en;q=0.8` düzgün parse edilməli, aktiv dillərlə müqayisə edilməli, fallback tətbiq edilməli və `Content-Language` qaytarılmalıdır. Hər request-də languages cədvəli sorğulanmamalı, aktiv dil kodları keşlənməlidir.

Optional auth etibarsız tokeni səssiz anonymous-a çevirməməlidir. Tövsiyə:

- token yoxdur — anonymous davam etsin;
- token var və etibarlıdır — user context qurulsun;
- token var, amma expired/invalid-dir — `401` qaytarılsın.

## Authentication və authorization

Bir layihədə auth strategiyası şüurlu seçilməlidir:

- first-party web SPA üçün HttpOnly cookie + Sanctum + CSRF daha təhlükəsiz default-dur;
- native mobile üçün qısaömürlü access token və rotation edilən refresh token istifadə edilə bilər;
- JWT seçilirsə blacklist/revocation, device sessions, refresh rotation və logout bütün hallarda işləməlidir.

Access token illərlə yaşamamalıdır. Məsələn 15–60 dəqiqə access, daha uzun refresh müddəti tətbiq edilə bilər. Token secret heç vaxt repoda saxlanmamalıdır.

Authentication user-in kim olduğunu, authorization isə bu əməliyyata icazəsini müəyyən edir. Ownership controller-də əl ilə müqayisə edilmək əvəzinə Policy-də saxlanmalıdır:

```php
public function update(User $user, Product $product): bool
{
    return $product->owner_id === $user->id && $user->is_active;
}
```

Form Request `authorize()` içində `$this->user()->can('update', $this->route('product'))` çağırmalıdır. Resurs mövcuddur, amma user görə bilmirsə məlumat sızmasının qarşısı üçün bəzi hallarda `404`, digər hallarda `403` siyasəti əvvəlcədən müəyyən edilməlidir.

## Form Request necə yazılmalıdır

Hər write endpoint ayrıca, niyyət bildirən request almalıdır:

```text
StoreProductRequest
UpdateProductRequest
ChangeProductStatusRequest
ReorderProductMediaRequest
ListProductsRequest
```

Request-in məsuliyyəti:

- input normalization (`prepareForValidation`);
- authorization (`authorize`);
- syntax və sərhəd validation (`rules`);
- lazım olduqda lokal validation mesajları;
- controller üçün təmiz data/DTO təqdim etmək.

```php
final class StoreProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => is_string($this->title) ? trim($this->title) : $this->title,
            'phone' => PhoneNormalizer::normalize($this->phone),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'category_uid' => ['required', 'uuid', Rule::exists('categories', 'uid')->where('is_active', true)],
            'price' => ['required', 'decimal:0,2', 'gte:0'],
            'media' => ['sometimes', 'array', 'max:10'],
            'media.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:10240'],
        ];
    }

    public function data(): CreateProductData
    {
        return CreateProductData::fromArray($this->validated());
    }
}
```

Qaydalar:

- controller-də `$request->all()` işlədilməməlidir;
- yalnız `validated()`, `safe()` və ya DTO istifadə edilməlidir;
- query filter-ləri də validation almalıdır: `page`, `per_page`, `sort`, `direction`, filter enum-ları;
- `per_page` server maksimumu ilə məhdudlaşdırılmalıdır, məsələn 1–100;
- DB-yə toxunan mürəkkəb biznes qaydaları validator-a doldurulmamalıdır;
- create və update rules ayrılmalıdır; çoxlu `sometimes/nullable` qarışıqlığı ilə bir request hər iki işi görməməlidir;
- boolean və array input-lar normalize edilməlidir;
- client-in göndərdiyi `user_id`, `status_id`, `is_admin` kimi etibarlı olmayan sahələr qəbul edilməməlidir;
- faylda yalnız extension deyil, MIME, ölçü və say yoxlanmalıdır.

Web və mobile validation eynidirsə ortaq request istifadə edin. Yalnız fərqli payload varsa shared base rules və iki nazik adapter request yaradın.

## Controller necə yazılmalıdır

Controller HTTP adapteridir. O:

- validated input alır;
- Policy nəticəsini tətbiq edir;
- Action/Service/Query çağırır;
- Resource və düzgün status qaytarır.

```php
final class ProductController extends ApiController
{
    public function store(StoreProductRequest $request, CreateProductAction $action): JsonResponse
    {
        $product = $action->execute($request->user(), $request->data());

        return ApiResponse::created(
            new ProductDetailResource($product),
            message: __('api.product_created'),
        );
    }
}
```

Controller-də bunlar olmamalıdır:

- uzun Eloquent query;
- `DB::transaction` daxilində bütün use-case;
- fayl adlandırma və storage qaydası;
- notification channel seçimi;
- hər metodda təkrarlanan `try/catch Throwable`;
- raw modelin birbaşa JSON-a qaytarılması;
- `new Service()`.

## Action, Service, Query və Repository sərhədləri

### Action

Bir use-case-i ifadə edir: `CreateProductAction`, `ApproveOrderAction`, `ResetPasswordAction`. Transaction sərhədini idarə edə bilər və bir neçə service/repository-ni koordinasiya edir.

### Service

Təkrar istifadə olunan biznes capability-dir: qiymət hesablama, OTP, subscription icazəsi, media emalı, notification göndərilməsi. Service HTTP Request və JsonResponse tanımamalıdır.

### Query

Read model və siyahı sorğuları üçündür. Filter, sort, eager load, select, pagination burada saxlanılır. Məsələn:

```php
final class ListProductsQuery
{
    public function paginate(ProductFilters $filters): LengthAwarePaginator
    {
        return Product::query()
            ->select(['id', 'uid', 'title', 'price', 'currency_id', 'company_id'])
            ->with(['currency:id,uid,code,symbol', 'company:id,uid,name'])
            ->published()
            ->when($filters->categoryUid, fn ($q, $uid) => $q->whereHas('categories', fn ($c) => $c->where('uid', $uid)))
            ->orderBy($filters->sortColumn(), $filters->direction)
            ->paginate($filters->perPage)
            ->withQueryString();
    }
}
```

Sort column whitelist-dən seçilməlidir; request-dən gələn column adı `orderBy`-a birbaşa verilməməlidir.

### Repository

Persistence əməliyyatını gizlədir: model yaratmaq, update etmək, aggregate-i saxlamaq, xüsusi lock/upsert. Sadə `Model::find()` üçün formal repository məcburi deyil. Query, upload, notification və bütün biznes qaydasını tək nəhəng repository-yə yığmaq olmaz.

Transaction Action/Service səviyyəsində use-case-i bütöv əhatə etməlidir. Xarici API, email və push transaction içində sinxron çağırılmamalı; event `afterCommit` və queue ilə işləməlidir.

## API Resource və response müqaviləsi

Bütün client-lər üçün bir envelope seçilməlidir:

```json
{
  "success": true,
  "message": "Product created.",
  "data": {},
  "meta": {
    "request_id": "01J..."
  }
}
```

Siyahı:

```json
{
  "success": true,
  "data": [],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 145,
      "last_page": 8
    }
  },
  "links": {
    "next": "...",
    "prev": null
  }
}
```

Xəta:

```json
{
  "success": false,
  "message": "Validation failed.",
  "code": "VALIDATION_ERROR",
  "errors": {
    "title": ["The title field is required."]
  },
  "meta": {
    "request_id": "01J..."
  }
}
```

Resource qaydaları:

- yalnız public contract field-lərini qaytarsın;
- daxili primary key, secret, pivot və lazımsız timestamp açılmasın;
- relation yalnız əvvəlcədən load edilibsə `whenLoaded()` ilə verilsin;
- resource içində query işləməsin;
- tarixlər ISO-8601 və timezone ilə verilsin;
- pul float deyil, integer minor unit və ya dəqiq decimal string kimi təqdim edilsin;
- URL-lər mərkəzi public/CDN URL generatorundan keçsin;
- list və detail resource ayrı ola bilər;
- mövcud field silinməsi və tip dəyişməsi breaking change sayılır.

## Exception və HTTP status sistemi

Controller-lərdə ümumi exception tutmaq əvəzinə global exception handler API request-i tanıyıb vahid JSON qaytarmalıdır.

Xəritə:

| Hadisə | HTTP | Stabil kod |
|---|---:|---|
| Validation | 422 | `VALIDATION_ERROR` |
| Token yoxdur/etibarsızdır | 401 | `UNAUTHENTICATED` |
| İcazə yoxdur | 403 | `FORBIDDEN` |
| Model yoxdur | 404 | `RESOURCE_NOT_FOUND` |
| Conflict/state keçidi yanlışdır | 409 | `STATE_CONFLICT` |
| Rate limit | 429 | `RATE_LIMITED` |
| Gözlənilməz xəta | 500 | `INTERNAL_ERROR` |

Exception-un `$e->getCode()` dəyəri HTTP status deyil. Domen exception-u ayrıca stabil error code və status daşımalıdır. Production-da stack trace, SQL, file path və exception message client-ə açılmamalıdır; tam məlumat request ID ilə server loguna yazılmalıdır.

## Enum və lookup məlumatları

Kod davranışını dəyişən, qapalı və deploy olmadan dəyişməyən dəyərlər PHP backed enum olmalıdır: platform, media type, permission key, error code.

Admin paneldən idarə olunan, tərcümə, sort, active/passive və əlavə metadata daşıyan status/type-lar database lookup cədvəlində qalmalıdır. API onlara daxili `id` ilə yox, stabil `key` və ya `uid` ilə müraciət etməlidir.

```php
enum ApiErrorCode: string
{
    case Validation = 'VALIDATION_ERROR';
    case Unauthenticated = 'UNAUTHENTICATED';
    case Forbidden = 'FORBIDDEN';
    case NotFound = 'RESOURCE_NOT_FOUND';
    case Conflict = 'STATE_CONFLICT';
}
```

Client-in göndərdiyi enum dəyəri `Rule::enum(...)` ilə yoxlanmalıdır. DB sütunu üçün enum cast istifadə oluna bilər.

## Config və environment

API-yə aid konfiqurasiya bir yerdə görünməlidir:

```php
// config/api.php
return [
    'default_version' => 'v1',
    'default_per_page' => 20,
    'max_per_page' => 100,
    'request_id_header' => 'X-Request-Id',
    'supported_locales' => ['az', 'en', 'ru'],
    'mobile' => [
        'min_version' => env('MOBILE_MIN_VERSION'),
    ],
];
```

`.env` yalnız environment fərqini saxlamalıdır. Kod daxilində `env()` çağırılmamalı, `config()` istifadə edilməlidir. Production üçün:

- CORS origin whitelist olmalıdır;
- credentials istifadə edilirsə wildcard qadağandır;
- trusted proxies/hosts düzgün qurulmalıdır;
- JWT/Sanctum strategiyasından yalnız biri əsas həqiqət olmalıdır;
- upload disk, public/CDN base URL, queue və cache ayrıca environment config almalıdır;
- secrets loga və response-a düşməməlidir.

## Rate limiting və abuse müdafiəsi

Bir ümumi `60/min` bütün endpoint-lər üçün kifayət deyil. Named limiter-lər yaradılmalıdır:

- public reads: IP + normal limit;
- authenticated writes: user ID;
- login: email/IP və sərt limit;
- OTP/send/reset: recipient + IP və çox sərt limit;
- upload/search: ayrıca xərc əsaslı limit;
- webhook: signature və provider qaydası.

`429` cavabında standart error envelope və `Retry-After` olmalıdır.

## Fayl yükləmə və media

Fayl prosesi controller/repository daxilində qarışdırılmamalıdır. `MediaService` və storage adapter aşağıdakıları etməlidir:

- MIME, ölçü, say və image dimension validation;
- təsadüfi təhlükəsiz storage adı;
- original filename-i yalnız metadata kimi saxlamaq;
- virus scan tələb olunan faylları quarantine etmək;
- image optimization/thumbnail işini queue-ya ötürmək;
- DB və storage uyğunsuzluğu üçün cleanup strategiyası;
- private fayllar üçün signed temporary URL;
- public fayllar üçün vahid CDN/public URL generatoru;
- ownership yoxlanmadan delete etməmək.

Client heç vaxt server storage path-i və disk adını contract kimi almamalıdır.

## Cache, queue, event və notification

Lookup, menu, translation, config və az dəyişən public kontent keşlənə bilər. Cache key versioned və locale/client context-i ehtiva etməlidir. Write əməliyyatında uyğun tag/key invalidate edilməlidir.

Email, push, ağır media emalı, import/export və xarici sistem inteqrasiyası queue işi olmalıdır. DB commit baş vermədən job dispatch edilməməlidir. Job idempotent, retry/backoff və failed-job müşahidəsinə malik olmalıdır.

Domain event HTTP event-dən ayrıdır. Məsələn `ProductPublished` event-i notification, search indexing və analytics listener-lərini controller-dən ayırır.

## Log və observability

Hər request üçün server request ID yaratmalı və response header/meta-da qaytarmalıdır. Structured log minimum bunları daşımalıdır:

- request ID;
- route adı, method və status;
- authenticated user UID (varsa);
- client platform və app version;
- müddət;
- exception class və təhlükəsiz context.

Password, token, OTP, Authorization header, şəxsi sənəd və bütöv request body loglanmamalıdır. Health/readiness endpoint, queue monitorinqi, error tracking və slow-query ölçümü nəzərdə tutulmalıdır.

## Database və performans

- Resource-un istifadə etdiyi relation-lar Query-də eager load edilməlidir;
- `select` ilə lazımi sütunlar məhdudlaşdırılmalıdır;
- filter və sort sütunları indekslənməlidir;
- böyük offset pagination üçün cursor pagination düşünülməlidir;
- list endpoint-də `withCount/withAvg` seçilməli, loop daxilində query edilməməlidir;
- transaction qısa saxlanmalıdır;
- bulk əməliyyatlar loop içində yüzlərlə ayrıca query əvəzinə upsert/batch işlətməlidir;
- list query üçün maksimum `per_page` məcburidir;
- N+1 test və ya development guard ilə aşkarlanmalıdır.

## Təhlükəsizlik checklist-i

- HTTPS məcburidir;
- mass assignment yalnız validated/DTO data ilə edilir;
- SQL sort/filter whitelist-lənir;
- authorization hər protected object üçün yoxlanılır;
- CORS origin-lər məhduddur;
- token qısaömürlü, revoke edilə bilən və rotation-lıdır;
- refresh token plain text saxlanmır;
- OTP hashed, expiring və attempt-limited-dir;
- upload MIME/size/dimension/ownership yoxlanılır;
- response daxili ID, secret və stack trace vermir;
- webhook signature və replay protection tətbiq edir;
- idempotency tələb edən payment/create endpoint-ləri `Idempotency-Key` dəstəkləyir;
- dependency və auth hadisələri audit loglanır;
- soft-deleted və inactive resurslar public query-dən çıxarılır.

## API dokumentasiyası və contract idarəsi

OpenAPI 3 sənədi source-of-truth kimi saxlanmalıdır. Hər endpoint üçün:

- method və URL;
- auth tələbi;
- header-lər;
- request schema və nümunə;
- success schema;
- bütün mümkün error code-lar;
- pagination/filter/sort;
- rate limit qeydi;
- deprecation məlumatı göstərilməlidir.

Swagger UI yalnız uyğun mühitdə qorunaraq açıla bilər. Mobil komanda üçün release etməzdən əvvəl generated client/schema və backward compatibility yoxlanmalıdır. Köhnələn endpoint `Deprecation`/`Sunset` header-ləri və keçid müddəti ilə elan edilməlidir.

## Test strategiyası

Əsas ağırlıq Feature və contract testlərində olmalıdır:

```text
tests/Feature/Api/V1/Auth/LoginTest.php
tests/Feature/Api/V1/Catalog/ListProductsTest.php
tests/Feature/Api/V1/Catalog/CreateProductTest.php
tests/Unit/Actions/Product/CreateProductActionTest.php
tests/Unit/Resources/ProductResourceTest.php
```

Hər endpoint üçün minimum:

- success;
- validation error;
- unauthenticated;
- forbidden/ownership;
- not found;
- filter/sort/pagination;
- response JSON shape;
- locale;
- rate limit kritik endpoint-də;
- DB side-effect və event/job dispatch;
- N+1 və query count kritik siyahılarda.

Web və mobile eyni contract-dırsa shared contract test hər ikisinə tətbiq edilməlidir. Resource field-ləri snapshot-a kor-koranə bağlamaq əvəzinə mühüm schema və field type-ları yoxlanmalıdır.

## Nümunə tam modul

Neytral nümunə kimi Product yaratma modulunun faylları aşağıda göstərilir. Hədəf layihədə bu domen yoxdursa bütün adlar real resursla əvəz edilməlidir:

```text
routes/api/v1/account.php
app/Http/Controllers/Api/V1/Catalog/ProductController.php
app/Http/Requests/Api/V1/Catalog/StoreProductRequest.php
app/DTOs/Product/CreateProductData.php
app/Actions/Product/CreateProductAction.php
app/Repositories/Product/ProductRepository.php
app/Services/Media/MediaService.php
app/Policies/ProductPolicy.php
app/Http/Resources/Api/V1/Product/ProductDetailResource.php
app/Events/Product/ProductSubmittedForReview.php
tests/Feature/Api/V1/Catalog/CreateProductTest.php
```

Axın:

1. Route `auth` və rate-limit middleware tətbiq edir.
2. Route model binding public UID istifadə edir.
3. Form Request normalize, authorize və validate edir.
4. Request yalnız validated data-dan DTO yaradır.
5. Controller Action çağırır.
6. Action transaction daxilində repository və domain service-ləri koordinasiya edir.
7. Event commit-dən sonra notification/index job-larını başladır.
8. Controller Resource-u vahid `ApiResponse` ilə `201` qaytarır.
9. Global handler bütün xətaları eyni contract-a çevirir.

## Mövcud Aquastores strukturundan mərhələli keçid

1. Web və mobile response formatını vahidləşdirin.
2. Global API exception renderer yaradıb controller `try/catch` təkrarını silin.
3. CORS whitelist və qısa token TTL tətbiq edin.
4. Public Resource-lardan daxili `id` və təkrar field-ləri çıxarın; bu breaking change-dirsə yeni versiyada edin.
5. Eyni web/mobile Request-ləri shared request-ə birləşdirin.
6. Eyni controller metodlarını shared controller/use-case-ə daşıyın.
7. Eyni Resource-ları shared core resource və lazım olduqda nazik client adapterinə çevirin.
8. Böyük repository-ləri Query, Repository, MediaService və Action üzrə ayırın.
9. Policy və domain exception-ları standartlaşdırın.
10. OpenAPI contract və yüksək riskli Feature testləri əlavə edin.

Bu refactor bir dəfəyə bütün endpoint-lərdə aparılmamalıdır. Bir domen seçilməli, yeni standart tam tətbiq edilməli, contract testlə qorunmalı və sonra digər domenlərə keçirilməlidir.

## Süni intellekt modeli üçün icra qaydaları

Bu sənədi alan model aşağıdakı ardıcıllıqla işləməlidir:

1. Mövcud framework versiyası, auth paketi, route provider, exception handler və response formatını analiz etsin.
2. Endpoint inventarı çıxarsın: public/optional/protected, web/mobile, read/write.
3. Mövcud client contract-ı testlə sabitləmədən geniş refactor etməsin.
4. Yeni qovluqları yalnız real məsuliyyət olduqda yaratsın; boş “enterprise” qatları yaratmasın.
5. Ortaq biznes məntiqini client qovluqlarında kopyalamasın.
6. Hər write endpoint-də Form Request + Policy + Action/Service + Resource axınını qursun.
7. Hər list endpoint-də validated filter, whitelist sort, eager loading və pagination tətbiq etsin.
8. Global exception və vahid response contract qursun.
9. Secrets, CORS, rate limit, token TTL və public field-ləri təhlükəsizlik baxımından yoxlasın.
10. Feature test, route list və OpenAPI schema ilə nəticəni təsdiqləsin.

Model mövcud mobil və web siniflərinin fərqli olmasını avtomatik olaraq onların ayrı qalmalı olduğuna sübut saymamalıdır. Əvvəl real request/response və davranış fərqini müqayisə etməlidir.

## Qəbul meyarları

- API versiyalı və domenlərə bölünmüş route strukturuna malikdir.
- Public, optional-auth və protected route-lar aydın ayrılıb.
- Mobil/web fərqi yalnız real contract fərqi olan qatlarda saxlanılır.
- Bütün input Form Request ilə normalize, authorize və validate edilir.
- Controller nazikdir və biznes/database məntiqi daşımır.
- Query, Action/Service və Repository sərhədləri aydındır.
- Bütün response və error-lar vahid JSON müqaviləsinə malikdir.
- Exception-lar global handler-də təhlükəsiz render olunur.
- Public Resource daxili ID və həssas sahə açmır.
- Locale, CORS, auth, rate limit və request ID middleware-ləri standartdır.
- Token ömrü, revoke/refresh və device session siyasəti müəyyən edilib.
- Upload, cache, queue, event və log qaydaları tətbiq edilib.
- OpenAPI sənədi və kritik Feature/contract testləri mövcuddur.
- `route:cache`, `config:cache` və production deployment ilə uyğunluq yoxlanılıb.
