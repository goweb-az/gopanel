# Gopanel Dashboard Arxitekturası

Bu sənəd başqa Laravel layihəsində işləyən developer və ya süni intellekt üçün reusable Dashboard implementasiya spesifikasiyasıdır. Model adları nümunədir. Hədəf layihədə `Listing`, `Tender` və ya `Company` olmaya bilər; onların yerinə layihənin real domen modelləri istifadə edilməlidir.

Məqsəd ağır aggregate query-ləri ilkin HTML render-dən ayırmaq, dashboard widget-lərini müstəqil AJAX ilə yükləmək və controller/query/Blade/JavaScript məsuliyyətlərini aydın saxlamaqdır.

## 1. Hədəf davranış

Dashboard:

- ilkin səhifəni DB aggregate query-ləri olmadan tez göstərməlidir;
- stat kartlarını, qrafikləri və son qeydləri müstəqil yükləməlidir;
- hər widget üçün loading, empty, success və error state göstərməlidir;
- tarix intervalını server-side validate etməlidir;
- cari dövrü eyni uzunluqlu əvvəlki dövrlə müqayisə etməlidir;
- köhnə AJAX cavabının yeni filtri overwrite etməsini bloklamalıdır;
- permission olmayan widget-i həm Blade, həm backend response-dan çıxarmalıdır;
- böyük query-lər üçün index/cache/timeout strategiyası saxlamalıdır.

Üç endpoint bu sənəddə tövsiyə olunan balansdır:

```text
stats   → say və trend kartları
charts  → qrafik JSON-ları
latest  → server-rendered table partial-ları
```

Kiçik dashboard bir endpoint istifadə edə bilər. Çox böyük dashboard widget-level endpoint istifadə edə bilər. Əsas qayda müstəqil loading və failure isolation-dır.

## 2. Fayl strukturu

```text
app/
├── Http/Controllers/Gopanel/DashboardController.php
├── Http/Requests/Gopanel/DashboardRangeRequest.php
├── Queries/Gopanel/DashboardQuery.php
└── Services/Gopanel/DashboardCache.php          # lazım olarsa

resources/views/gopanel/
├── dashboard.blade.php
└── pages/dashboard/
    ├── components/
    │   ├── stat-card.blade.php
    │   └── chart-card.blade.php
    └── inc/
        ├── latest_items.blade.php
        └── latest_users.blade.php

public/assets/gopanel/js/modules/dashboard.js
routes/gopanel.php
tests/Feature/Gopanel/DashboardTest.php
```

## 3. Route və permission strukturu

```php
Route::middleware(['gopanel', 'can:gopanel.dashboard.view'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');
        Route::get('/charts', [DashboardController::class, 'charts'])->name('charts');
        Route::get('/latest', [DashboardController::class, 'latest'])->name('latest');
    });
});
```

GET uyğundur, çünki endpoint-lər state dəyişmir. Response private admin data saxlayırsa public/proxy cache header verilməməlidir.

## 4. Tarix request-i

Tarixi yalnız `ajax()` olduqda constructor-da initialize etmək olmaz. Endpoint adi GET və ya test client ilə çağırılanda typed property uninitialized qala bilər.

```php
final class DashboardRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('gopanel')?->can('gopanel.dashboard.view') === true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function range(): DashboardDateRange
    {
        $timezone = config('app.timezone');
        $from = CarbonImmutable::createFromFormat('Y-m-d', $this->validated('from'), $timezone)->startOfDay();
        $to = CarbonImmutable::createFromFormat('Y-m-d', $this->validated('to'), $timezone)->endOfDay();

        if ($from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['to' => 'Maksimum tarix intervalı 366 gündür.']);
        }

        return new DashboardDateRange($from, $to);
    }
}
```

`DashboardDateRange` kiçik immutable value object ola bilər:

```php
final readonly class DashboardDateRange
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
    ) {}

    public function previous(): self
    {
        $days = $this->from->diffInDays($this->to) + 1;
        $previousTo = $this->from->subDay()->endOfDay();
        return new self($previousTo->subDays($days - 1)->startOfDay(), $previousTo);
    }
}
```

DB timestamp-ları UTC saxlanılırsa user timezone sərhədləri query-dən əvvəl UTC-yə çevrilməlidir.

## 5. Controller strukturu

Controller query yazmamalı və Blade widget markup hesablamamalıdır:

```php
final class DashboardController extends GoPanelController
{
    public function index(): View
    {
        $to = today();
        $from = today()->subDays(29);

        return view('gopanel.dashboard', [
            'dateFrom' => $from->format('Y-m-d'),
            'dateTo' => $to->format('Y-m-d'),
            'dateRangeLabel' => $from->format('d/m/Y').' – '.$to->format('d/m/Y'),
        ]);
    }

    public function stats(DashboardRangeRequest $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => (new DashboardQuery($request->range()))->stats(),
        ]);
    }

    public function charts(DashboardRangeRequest $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => (new DashboardQuery($request->range()))->charts(),
        ]);
    }

    public function latest(DashboardRangeRequest $request): JsonResponse
    {
        $query = new DashboardQuery($request->range());

        return response()->json([
            'status' => 'success',
            'data' => [
                'items_html' => view('gopanel.pages.dashboard.inc.latest_items', [
                    'items' => $query->latestItems(),
                ])->render(),
                'users_html' => view('gopanel.pages.dashboard.inc.latest_users', [
                    'users' => $query->latestUsers(),
                ])->render(),
            ],
        ]);
    }
}
```

Response formatı bütün endpoint-lərdə eyni olmalıdır:

```json
{
  "status": "success",
  "data": {},
  "message": null
}
```

Validation `422`, permission `403`, gözlənilməyən xəta `500` qaytarmalıdır. Production response exception stack/message göstərməməlidir.

## 6. Query class məsuliyyəti

Query class:

- yalnız read/query və response data formalaşdırır;
- Request, View və HTTP response tanımır;
- cari və əvvəlki periodu eyni qayda ilə istifadə edir;
- chart contract-larını stabil saxlayır;
- lazım olan relation-ları eager-load edir.

```php
final class DashboardQuery
{
    public function __construct(private readonly DashboardDateRange $range) {}

    public function stats(): array
    {
        return [
            'users' => $this->trend(User::query()),
            'orders' => $this->trend(Order::query()),
            'products' => $this->trend(Product::query()),
        ];
    }

    private function trend(Builder $query, string $column = 'created_at'): array
    {
        $previous = $this->range->previous();
        $currentCount = (clone $query)->whereBetween($column, [$this->range->from, $this->range->to])->count();
        $previousCount = (clone $query)->whereBetween($column, [$previous->from, $previous->to])->count();

        if ($previousCount === 0) {
            return [
                'current' => $currentCount,
                'previous' => 0,
                'change' => null,
                'trend' => $currentCount > 0 ? 'new' : 'neutral',
            ];
        }

        $change = (($currentCount - $previousCount) / $previousCount) * 100;

        return [
            'current' => $currentCount,
            'previous' => $previousCount,
            'change' => round($change, 1),
            'trend' => $change > 0 ? 'increase' : ($change < 0 ? 'decrease' : 'neutral'),
        ];
    }
}
```

Model class string qəbul edən generic helper mümkündür, amma `Builder` qəbul etmək əlavə filter-ləri qoruyur:

```php
$this->trend(Order::query()->where('is_test', false));
```

## 7. Chart response contract-ları

Line/bar/area time-series:

```json
{
  "categories": ["2026-07-01", "2026-07-02"],
  "series": [
    {"name": "Sifarişlər", "data": [12, 18]},
    {"name": "İstifadəçilər", "data": [5, 9]}
  ]
}
```

Donut/pie:

```json
{
  "labels": ["Gözləyir", "Tamamlanıb"],
  "series": [14, 31],
  "colors": ["#f1b44c", "#34c38f"]
}
```

Bar/funnel/heatmap lazım olarsa ayrıca stabil contract müəyyən edilməlidir. Dashboard yalnız line və donut ilə məhdud deyil.

## 8. Tarix oxu və DB uyğunluğu

Bucket seçimi:

```text
0–31 gün     → daily
32–180 gün   → weekly və ya daily
181–366 gün  → monthly
```

MySQL nümunəsi:

```php
$periodExpression = $monthly
    ? "DATE_FORMAT(created_at, '%Y-%m')"
    : "DATE(created_at)";
```

PostgreSQL üçün `DATE_TRUNC`, SQLite üçün `strftime` lazımdır. AI database driver-i yoxlamadan MySQL-specific SQL kopyalamamalıdır.

Bütün interval label-ları PHP-də yaradılmalı və DB-də qeydi olmayan bucket-lar `0` ilə doldurulmalıdır.

```php
return [
    'categories' => $labels,
    'series' => [[
        'name' => 'Sifarişlər',
        'data' => array_map(fn ($label) => (int) ($raw[$label] ?? 0), $labels),
    ]],
];
```

## 9. Latest semantikası

İki variantdan biri seçilməlidir:

1. **Global latest** — tarix picker-dən asılı deyil. Tarix dəyişəndə `latest` yenidən yüklənməməlidir.
2. **Range latest** — seçilən intervaldakı son qeydlərdir və query `whereBetween` istifadə etməlidir.

Bu sənəddə range latest qəbul edilir:

```php
public function latestItems(int $limit = 5): Collection
{
    return Item::query()
        ->with(['status', 'owner'])
        ->whereBetween('created_at', [$this->range->from, $this->range->to])
        ->latest('created_at')
        ->limit($limit)
        ->get();
}
```

Server-rendered partial route linkləri, authorization və presentation məntiqinin JS-də təkrarlanmasının qarşısını alır.

## 10. Blade strukturu

Dashboard Blade yalnız layout, widget shell-ləri, loader-lər və JS config saxlamalıdır. Aggregate query etməməlidir.

Dashboard ilkin HTML render olunan anda data hələ gəlmədiyi üçün bütün stat, chart və latest bloklarında loader əvvəlcədən görünməlidir. JavaScript data gəldikdən sonra loader-i gizlədib content-i göstərməlidir.

### Təkrar istifadə olunan stat card

```blade
{{-- components/stat-card.blade.php --}}
@can($permission)
<div class="col-md-6 col-xl-3">
    <a href="{{ $url }}" class="card mini-stats-wid dashboard-stat-link" id="stat-{{ $key }}">
        <div class="card-body">
            <div class="d-flex">
                <div class="flex-grow-1">
                    <p class="text-muted fw-medium">{{ $title }}</p>
                    <h4 class="stat-value" aria-live="polite">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </h4>
                </div>
                <div class="mini-stat-icon"><i class="{{ $icon }}"></i></div>
            </div>
            <div class="mt-4">
                <span class="stat-trend badge"></span>
                <span class="text-muted">Əvvəlki dövrlə müqayisə</span>
            </div>
        </div>
    </a>
</div>
@endcan
```

Kliklənən kart `div + JS redirect` əvəzinə mümkün olduqda semantik `<a>` olmalıdır.

### Chart card

```blade
<div class="card dashboard-chart-card" data-widget="activity">
    <div class="card-header"><h4>Aktivlik</h4></div>
    <div class="card-body">
        <div class="widget-loading text-center py-4">
            <span class="spinner-border text-primary"></span>
        </div>
        <div class="widget-error text-center text-danger py-4" hidden></div>
        <div class="widget-empty text-center text-muted py-4" hidden>Məlumat yoxdur</div>
        <div id="activity-chart" class="widget-content" hidden></div>
    </div>
</div>
```

### Latest table

```blade
<tbody id="latest-items-body" aria-live="polite">
    <tr class="widget-loading-row">
        <td colspan="3" class="text-center"><span class="spinner-border"></span></td>
    </tr>
</tbody>
```

### Config

Inline interpolation yerinə JSON encode istifadə edilməlidir:

```blade
<script>
window.dashboardConfig = @json([
    'dateFrom' => $dateFrom,
    'dateTo' => $dateTo,
    'statsUrl' => route('gopanel.dashboard.stats'),
    'chartsUrl' => route('gopanel.dashboard.charts'),
    'latestUrl' => route('gopanel.dashboard.latest'),
]);
</script>
<script src="{{ asset('assets/gopanel/js/modules/dashboard.js') }}"></script>
```

Production-da `?v={{ time() }}` istifadə edilməməlidir; hər page load cache-i pozur. Vite/Mix manifest hash və ya application version istifadə edilməlidir.

### Dashboard CSS nümunəsi

```css
.dashboard-stat-link {
    display: block;
    color: inherit;
    text-decoration: none;
    transition: transform .15s ease, box-shadow .15s ease;
}

.dashboard-stat-link:hover {
    color: inherit;
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1rem rgba(15, 23, 42, .08);
}

.dashboard-stat-link:focus-visible {
    outline: 3px solid rgba(85, 110, 230, .3);
    outline-offset: 2px;
}

.dashboard-chart-card .card-body {
    position: relative;
    min-height: 360px;
}

.widget-loading,
.widget-error,
.widget-empty {
    min-height: 280px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.widget-content {
    min-height: 320px;
}

.widget-loading[hidden],
.widget-error[hidden],
.widget-empty[hidden],
.widget-content[hidden] {
    display: none !important;
}

.dashboard-stat-error {
    color: #f46a6a;
    font-size: .8125rem;
}

.dashboard-skeleton {
    overflow: hidden;
    position: relative;
    background: #eef2f7;
    border-radius: .25rem;
}

.dashboard-skeleton::after {
    content: "";
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.7), transparent);
    animation: dashboard-shimmer 1.2s infinite;
}

@keyframes dashboard-shimmer {
    100% { transform: translateX(100%); }
}

@media (prefers-reduced-motion: reduce) {
    .dashboard-stat-link,
    .dashboard-skeleton::after {
        transition: none;
        animation: none;
    }
}
```

Spinner istifadə olunursa Bootstrap `spinner-border`; daha sakit görünüş istənirsə skeleton class-ları istifadə edilə bilər. Eyni widget daxilində spinner və skeleton birlikdə göstərilməməlidir.

## 11. JavaScript modulu

Modul state və request-ləri idarə etməlidir:

```javascript
$(function () {
    var state = {
        charts: {},
        requests: { stats: null, charts: null, latest: null },
        sequence: 0
    };

    initDateRange();
    reloadDashboard();
});
```

### Request race müdafiəsi

Tarix tez dəyişəndə köhnə cavab yeni nəticəni overwrite etməməlidir:

```javascript
function requestBlock(key, options) {
    if (state.requests[key]) {
        state.requests[key].abort();
    }

    state.requests[key] = $.ajax(options).always(function () {
        state.requests[key] = null;
    });

    return state.requests[key];
}
```

### Ümumi reload

```javascript
function rangeParams() {
    return {
        from: window.dashboardConfig.dateFrom,
        to: window.dashboardConfig.dateTo
    };
}

function reloadDashboard() {
    loadStats();
    loadCharts();
    loadLatest(); // yalnız range-latest seçilibsə
}
```

Bu çağırışlar paraleldir; biri uğursuz olduqda digərləri işləməyə davam edir.

### Stats AJAX

```javascript
function loadStats() {
    showStatsLoading();

    requestBlock('stats', {
        url: dashboardConfig.statsUrl,
        type: 'GET',
        dataType: 'json',
        data: rangeParams()
    }).done(function (response) {
        Object.keys(response.data || {}).forEach(function (key) {
            renderStat(key, response.data[key]);
        });
    }).fail(function (xhr, status) {
        if (status !== 'abort') showStatsError(xhr);
    });
}

function renderStat(key, metric) {
    var $card = $('#stat-' + key);
    $card.find('.stat-value').text(metric.display_value ?? metric.current ?? 0);

    var $trend = $card.find('.stat-trend').removeClass(
        'bg-soft-success text-success bg-soft-danger text-danger bg-soft-secondary text-secondary'
    );

    if (metric.trend === 'new') {
        $trend.text('Yeni').addClass('bg-soft-success text-success');
    } else if (metric.trend === 'neutral') {
        $trend.text('0%').addClass('bg-soft-secondary text-secondary');
    } else {
        $trend.text((metric.change > 0 ? '+' : '') + metric.change + '%')
            .addClass(metric.trend === 'increase'
                ? 'bg-soft-success text-success'
                : 'bg-soft-danger text-danger');
    }
}
```

### Chart AJAX və lifecycle

```javascript
function loadCharts() {
    showAllChartLoading();

    requestBlock('charts', {
        url: dashboardConfig.chartsUrl,
        dataType: 'json',
        data: rangeParams()
    }).done(function (response) {
        renderTimeSeries('activity', response.data.activity);
        renderDonut('statuses', response.data.statuses);
    }).fail(function (xhr, status) {
        if (status !== 'abort') showAllChartErrors(xhr);
    });
}

function replaceChart(key, element, options) {
    if (state.charts[key]) {
        state.charts[key].destroy();
        state.charts[key] = null;
    }

    state.charts[key] = new ApexCharts(element, options);
    return state.charts[key].render();
}
```

Empty data olduqda əvvəlki chart destroy edilməli, chart container təmizlənməli və empty state göstərilməlidir.

### Latest AJAX

```javascript
function loadLatest() {
    showLatestLoading();

    requestBlock('latest', {
        url: dashboardConfig.latestUrl,
        dataType: 'json',
        data: rangeParams()
    }).done(function (response) {
        $('#latest-items-body').html(response.data.items_html);
        $('#latest-users-body').html(response.data.users_html);
    }).fail(function (xhr, status) {
        if (status !== 'abort') showLatestError(xhr);
    });
}
```

Serverdən gələn HTML yalnız layihənin öz authenticated endpoint-indən gəlməlidir. Blade user content-i `{{ }}` ilə escape etməlidir.

## 12. Date picker

```javascript
function initDateRange() {
    $('#dashboardDateRange').daterangepicker({
        startDate: moment(dashboardConfig.dateFrom, 'YYYY-MM-DD'),
        endDate: moment(dashboardConfig.dateTo, 'YYYY-MM-DD'),
        maxSpan: { days: 366 },
        locale: { format: 'DD/MM/YYYY' },
        ranges: {
            'Bu gün': [moment(), moment()],
            'Son 7 gün': [moment().subtract(6, 'days'), moment()],
            'Son 30 gün': [moment().subtract(29, 'days'), moment()],
            'Bu ay': [moment().startOf('month'), moment().endOf('month')]
        }
    }, function (start, end) {
        dashboardConfig.dateFrom = start.format('YYYY-MM-DD');
        dashboardConfig.dateTo = end.format('YYYY-MM-DD');
        $('#dateRangeLabel').text(start.format('DD/MM/YYYY') + ' – ' + end.format('DD/MM/YYYY'));
        reloadDashboard();
    });
}
```

Frontend limit UX üçündür; backend maksimum intervalı yenə yoxlamalıdır.

## 13. Latest partial

```blade
@forelse($items as $item)
<tr>
    <td>
        <a href="{{ route('gopanel.items.show', $item->uid) }}">
            {{ $item->title }}
        </a>
        <small class="text-muted">{{ $item->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</small>
    </td>
    <td>{{ $item->owner?->name ?? '—' }}</td>
    <td>
        @if($item->status)
            <span class="badge {{ $item->status->class_name }}">
                {{ $item->status->name }}
            </span>
        @else
            <span class="badge bg-secondary">Bilinmir</span>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="3" class="text-center text-muted">Məlumat tapılmadı</td></tr>
@endforelse
```

Status rəngi dinamik status modelində saxlanırsa Blade-də hardcoded `match` yazılmamalıdır. Modeldən gələn class whitelist/sanitize edilməlidir.

## 14. Widget state contract

Hər widget dörd haldan yalnız birini göstərməlidir:

```text
loading → spinner/skeleton
success → data/chart/table
empty   → “Məlumat yoxdur”
error   → görünən xəta + retry düyməsi
```

Helper nümunəsi:

```javascript
function setWidgetState($widget, state, message) {
    $widget.find('.widget-loading').prop('hidden', state !== 'loading');
    $widget.find('.widget-content').prop('hidden', state !== 'success');
    $widget.find('.widget-empty').prop('hidden', state !== 'empty');
    $widget.find('.widget-error')
        .prop('hidden', state !== 'error')
        .text(message || 'Məlumat yüklənmədi');
}
```

## 15. Cache və performans

- metric query-lərində `created_at` index olmalıdır;
- status/type chart-larında foreign key + `created_at` composite index faydalı ola bilər;
- response-lar admin/user permission-a görə fərqlənirsə cache key permission scope saxlamalıdır;
- stats/charts üçün 30–120 saniyəlik cache istifadə edilə bilər;
- cache key: widget + from + to + locale + permission scope;
- latest çox dinamikdirsə cache edilməyə bilər;
- eyni dashboard request-də eyni lookup cədvəli təkrar query edilməməlidir;
- interval uzandıqca bucket sayı azaldılmalıdır;
- query timeout və slow query monitorinqi olmalıdır;
- böyük scale-da pre-aggregated daily metrics cədvəli düşünülməlidir.

## 16. Təhlükəsizlik

- bütün endpoint-lər auth və permission tələb edir;
- `from/to` validated və limitlidir;
- widget linkləri də permission ilə render edilir;
- JSON yalnız lazım olan aggregate data qaytarır;
- partial user content-i escape edir;
- raw HTML yalnız server-owned presentation üçün istifadə olunur;
- exception detail production-da gizlədilir;
- CDN dependency istifadə edilirsə CSP/SRI və fallback düşünülür.

## 17. Test checklist

Backend:

- admin olmayan istifadəçi `403`/redirect alır;
- permission olmayan widget response-da yoxdur;
- səhv formatlı tarix `422` qaytarır;
- `to < from` bloklanır;
- 366 gündən böyük interval bloklanır;
- timezone gün sərhədləri düzgündür;
- cari və əvvəlki period eyni uzunluqdadır;
- previous=0/current>0 `new` qaytarır;
- hər ikisi 0 olduqda `neutral` qaytarır;
- chart missing bucket-ları 0 ilə doldurur;
- empty chart contract düzgündür;
- latest seçilmiş semantikaya uyğun tarix filter edir;
- partial HTML escape olunur;
- query sayı və maksimum response vaxtı ölçülür;
- cache key interval/locale/permission-u ayırır.

Frontend:

- ilkin loading state görünür;
- stats, charts, latest paralel yüklənir;
- biri uğursuz olduqda digərləri işləyir;
- tarix dəyişəndə əvvəlki request abort edilir;
- abort error mesajı göstərmir;
- chart render-dən əvvəl əvvəlki instance destroy edilir;
- empty və error state fərqlidir;
- retry işləyir;
- `new`, `neutral`, `increase`, `decrease` düzgün göstərilir;
- mobil layout və chart resize işləyir;
- screen reader üçün `aria-live` mövcuddur.

## 18. Yeni layihədə tətbiq ardıcıllığı

1. Dashboard-da göstəriləcək real metric və widget-ləri siyahıya al.
2. Hər widget üçün permission və link müəyyən et.
3. Tarix semantikasını və timezone-u müəyyən et.
4. `DashboardRangeRequest` və date range value object yarat.
5. Query class-da stats, chart və latest contract-larını yaz.
6. Controller-də yalnız request → query → response axını saxla.
7. Blade-də component shell, loading/empty/error state-ləri yarat.
8. JS-də request abort, widget state və chart lifecycle yaz.
9. DB indekslərini və cache strategiyasını əlavə et.
10. Feature və frontend smoke test-ləri yaz.

## 19. Yekun qəbul meyarları

Implementasiya hazır sayılır, əgər:

- ilkin dashboard ağır aggregate query olmadan açılır;
- controller nazik, query class test edilə biləndir;
- tarix intervalı validated, timezone-aware və limitlidir;
- stats/charts/latest müstəqil failure isolation ilə işləyir;
- köhnə AJAX request yeni state-i overwrite etmir;
- hər widget loading/success/empty/error state saxlayır;
- chart contract və instance lifecycle sabitdir;
- latest tarix semantikası açıq və backend/frontend-də eynidir;
- permission, escape, cache və DB indeksləri nəzərə alınıb;
- model və kitabxana adları başqa layihənin domeninə uyğun dəyişdirilə bilir.
