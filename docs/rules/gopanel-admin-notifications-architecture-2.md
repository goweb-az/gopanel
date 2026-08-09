# Gopanel Admin Bildirişləri Arxitekturası (geniş analiz)

> ⚠️ **Bu sənəd ARXA PLAN materialıdır, addım-addım qayda deyil.**
> Gopanel-də bildiriş bölməsi qurulanda əvvəlcə
> [gopanel-notifications-module.md](gopanel-notifications-module.md) oxunur —
> orada bu layihənin real fayl adları, route-ları və konfiqurasiyası ilə
> yazılmış icra ardıcıllığı var. Bu fayl isə arxitektura seçimlərinin
> geniş izahını saxlayır (nə üçün belə, hansı alternativlər var).
>
> Sənəddəki `Listing`, `Tender`, `Company`, `NEW_REVIEW` kimi adlar **nümunədir** —
> hədəf layihədə onların yerini real domen modelləri və hadisələri tutur
> (məsələn `Product`, `Order`, `Müraciət`).

Bu sənəd başqa Laravel layihəsində işləyən developer və ya süni intellekt üçün reusable **Admin Bildiriş (Admin Notification)** implementasiya spesifikasiyasıdır. Model, tip və mətn adları nümunədir; hədəf layihədə `Listing`, `Tender`, `Company` və ya `NEW_REVIEW` olmaya bilər — onların yerinə layihənin real domen modelləri və hadisələri istifadə edilməlidir.

Məqsəd: gopanel (admin panel) içində istənilən adminə çoxkanallı (database + mail/sms/telegram/webpush), tipləşmiş, oxundu/silindi statuslu, header-də canlı badge və dropdown ilə görünən bildiriş sistemi qurmaq; göndərməni domen servislərindən ayırmaq və queue ilə əsas prosesi bloklamamaq.

Bu sistem son istifadəçi (user/mobil) bildirişlərindən **tam ayrıdır**. User bildirişləri `notifications` cədvəlində, admin bildirişləri isə ayrıca `admin_notifications` cədvəlində saxlanılır. İki sistemi qarışdırmaq olmaz.

---

## 1. Hədəf davranış

Admin bildiriş sistemi:

- domen hadisəsi baş verdikdə (yeni elan, yeni user, yeni ödəniş və s.) bütün aktiv adminlərə bildiriş yaratmalıdır;
- göndərməni **queue** ilə etməlidir ki, istifadəçinin cavabını gecikdirməsin;
- hər bildirişi bir `type` (enum) ilə işarələməlidir; tip icon, rəng, label və render template-ini müəyyən edir;
- database kanalını həmişə saxlamalı, əlavə kanalları (mail/sms/telegram/webpush) opsional göndərməlidir;
- header-də zəng ikonu altında oxunmamış sayı (badge) və son bildirişlərin dropdown-unu göstərməlidir;
- dropdown-da infinite scroll (səhifə-səhifə yükləmə) və periodik polling ilə badge yeniləməsi olmalıdır;
- tam siyahı səhifəsində oxundu et / sil / hamısını oxundu / oxunmuşları sil əməliyyatlarını AJAX ilə etməlidir;
- hər bildirişin öz tipinə uyğun zəngin **detail** səhifəsi olmalıdır;
- hər adminin yalnız öz bildirişlərini görməsini authorization ilə təmin etməlidir;
- göndərmə xətası (məs. mail server düşüb) əsas prosesi partlatmamalı, yalnız loglanmalıdır.

### Məsuliyyət ayrımı

```text
Domen servisi   → yalnız "bu hadisə oldu" deyir (dispatch)
Job             → aktiv adminləri gəzir, kanalları çağırır
Service         → send/dispatch API + oxundu/sil/say sorğuları
Method (kanal)  → bir kanala göndərmənin konkret məntiqi
Enum            → type → icon/color/label/template map
Controller      → yalnız HTTP: index/header/view/read/delete
Blade           → shell, list, dropdown, detail template-lər
JavaScript      → polling, infinite scroll, mark-read/delete AJAX
```

---

## 2. Fayl strukturu

```text
app/
├── Enums/Notification/
│   └── AdminNotificationTypeEnum.php
├── Models/Gopanel/
│   └── AdminNotification.php
├── Http/Controllers/Gopanel/Admins/
│   └── AdminNotificationController.php
├── Jobs/
│   └── SendAdminNotificationJob.php
└── Services/Gopanel/Notification/
    ├── AdminNotificationService.php
    ├── AdminNotificationMethodFactory.php
    └── Methods/
        ├── AdminDatabaseNotification.php
        ├── AdminMailNotification.php
        ├── AdminSmsNotification.php
        ├── AdminTelegramNotification.php
        └── AdminWebPushNotification.php

database/migrations/
└── xxxx_xx_xx_create_admin_notifications_table.php

resources/views/gopanel/
├── blocks/
│   ├── head.blade.php                 # <meta name="admin-notif-url">
│   ├── header.blade.php               # @include notification-header
│   └── notification-header.blade.php  # zəng + dropdown shell
└── pages/admin-notifications/
    ├── index.blade.php                # tam siyahı
    ├── show.blade.php                 # detail səhifəsi
    ├── partials/
    │   └── header-list.blade.php      # dropdown list item-ləri (AJAX)
    └── templates/
        ├── default.blade.php          # compact badge-lər (siyahıda)
        ├── new_listing.blade.php
        ├── ...
        └── detail/
            ├── default.blade.php      # zəngin detail (show səhifəsində)
            ├── new_listing.blade.php
            └── ...

public/assets/gopanel/js/main.js       # polling + infinite scroll + mark-read
routes/gopanel.php
tests/Unit/AdminNotificationModuleTest.php
```

---

## 3. Verilənlər bazası sxemi

`notifiable` morph sahələri bildirişi domen obyektinə (Listing, User, Tender…) bağlamaq üçündür və nullable-dır. `level` gələcəkdə önəm/prioritet üçün saxlanılır. `data` json istənilən əlavə strukturlaşmış məlumatı tutur və template-lərdə istifadə olunur.

```php
Schema::create('admin_notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
    $table->nullableMorphs('notifiable');           // notifiable_type + notifiable_id
    $table->string('type', 50)->default('default'); // enum value
    $table->smallInteger('level')->default(1);      // önəm/prioritet
    $table->string('title');
    $table->text('message');
    $table->string('action_url', 500)->nullable();
    $table->json('data')->nullable();
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['admin_id', 'read_at']);     // unread count
    $table->index(['admin_id', 'created_at']);  // siyahı / dropdown
    $table->index('type');
});
```

> **Diqqət:** `admins` cədvəlinin adı hədəf layihədə fərqli ola bilər (`users`, `staff`, `moderators`). `constrained()`-i real admin cədvəlinə yönəldin. Uzun cədvəl adlarında FK adı 64 simvol limitini aşarsa, açıq qısa FK adı verin.

---

## 4. Type Enum

Enum sistemin mərkəzidir: hər tip öz icon, rəng, label və iki template-ini (compact + detail) müəyyən edir. Yeni bir bildiriş növü əlavə etmək = bura bir `case` + dörd `match` sətri + iki blade faylı əlavə etmək.

```php
<?php

namespace App\Enums\Notification;

enum AdminNotificationTypeEnum: string
{
    case DEFAULT         = 'default';
    case NEW_LISTING     = 'new_listing';
    case NEW_TENDER      = 'new_tender';
    case NEW_USER        = 'new_user';
    case PAYMENT         = 'payment';
    case SUBSCRIPTION    = 'subscription';
    case REPORT          = 'report';
    case SYSTEM_ALERT    = 'system_alert';
    case TENDER_PROPOSAL = 'tender_proposal';
    case TENDER_QUESTION = 'tender_question';
    case NEW_REVIEW      = 'new_review';

    public function label(): string
    {
        return match ($this) {
            self::DEFAULT         => 'General',
            self::NEW_LISTING     => 'New Listing',
            self::NEW_TENDER      => 'New Tender',
            self::NEW_USER        => 'New User',
            self::PAYMENT         => 'Payment',
            self::SUBSCRIPTION    => 'Subscription',
            self::REPORT          => 'Report',
            self::SYSTEM_ALERT    => 'System Alert',
            self::TENDER_PROPOSAL => 'Tender Proposal',
            self::TENDER_QUESTION => 'Tender Question',
            self::NEW_REVIEW      => 'New Review',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DEFAULT         => 'bx bx-bell',
            self::NEW_LISTING     => 'bx bx-list-plus',
            self::NEW_TENDER      => 'bx bx-file-plus',
            self::NEW_USER        => 'bx bx-user-plus',
            self::PAYMENT         => 'bx bx-credit-card',
            self::SUBSCRIPTION    => 'bx bx-badge-check',
            self::REPORT          => 'bx bx-error-circle',
            self::SYSTEM_ALERT    => 'bx bx-cog',
            self::TENDER_PROPOSAL => 'bx bx-send',
            self::TENDER_QUESTION => 'bx bx-question-mark',
            self::NEW_REVIEW      => 'bx bx-star',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DEFAULT         => 'primary',
            self::NEW_LISTING     => 'info',
            self::NEW_TENDER      => 'warning',
            self::NEW_USER        => 'success',
            self::PAYMENT         => 'success',
            self::SUBSCRIPTION    => 'primary',
            self::REPORT          => 'danger',
            self::SYSTEM_ALERT    => 'warning',
            self::TENDER_PROPOSAL => 'info',
            self::TENDER_QUESTION => 'secondary',
            self::NEW_REVIEW      => 'warning',
        };
    }

    /** Siyahıda (index) göstərilən compact badge template-i */
    public function template(): string
    {
        $base = 'gopanel.pages.admin-notifications.templates.';

        return match ($this) {
            self::NEW_LISTING     => $base . 'new_listing',
            self::NEW_TENDER      => $base . 'new_tender',
            self::NEW_USER        => $base . 'new_user',
            self::PAYMENT         => $base . 'payment',
            self::SUBSCRIPTION    => $base . 'subscription',
            self::REPORT          => $base . 'report',
            self::SYSTEM_ALERT    => $base . 'system_alert',
            self::TENDER_PROPOSAL => $base . 'tender_proposal',
            self::TENDER_QUESTION => $base . 'tender_question',
            self::NEW_REVIEW      => $base . 'new_review',
            default               => $base . 'default',
        };
    }

    /** Detail (show) səhifəsində göstərilən zəngin template */
    public function detailTemplate(): string
    {
        $base = 'gopanel.pages.admin-notifications.templates.detail.';

        return match ($this) {
            self::NEW_LISTING     => $base . 'new_listing',
            self::NEW_TENDER      => $base . 'new_tender',
            self::NEW_USER        => $base . 'new_user',
            self::PAYMENT         => $base . 'payment',
            self::SUBSCRIPTION    => $base . 'subscription',
            self::REPORT          => $base . 'report',
            self::SYSTEM_ALERT    => $base . 'system_alert',
            self::TENDER_PROPOSAL => $base . 'tender_proposal',
            self::TENDER_QUESTION => $base . 'tender_question',
            self::NEW_REVIEW      => $base . 'new_review',
            default               => $base . 'default',
        };
    }
}
```

> `default` case-i həmişə saxlanılmalıdır — `tryFrom()` naməlum tip üçün buna düşür və blade heç vaxt `@include` xətası vermir. Template blade-i mövcud deyilsə, blade-də `@includeIf` istifadə edin (aşağıda).

---

## 5. Model

Model yalnız cast, relation, scope və presentation accessor-ları saxlayır. Blade-də inline enum/match yazmamaq üçün `type_icon`, `type_color`, `type_label`, `detail_template`, `detail_page_template` accessor-ları verilir.

```php
<?php

namespace App\Models\Gopanel;

use App\Enums\Notification\AdminNotificationTypeEnum;
use App\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminNotification extends BaseModel
{
    use SoftDeletes;

    protected $table = 'admin_notifications';

    protected $fillable = [
        'admin_id', 'notifiable_type', 'notifiable_id', 'type', 'level',
        'title', 'message', 'action_url', 'data', 'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
        'level'   => 'integer',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ─────────────────────────────────────────────────
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeForAdmin(Builder $query, int $adminId): Builder
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // ── Helpers ────────────────────────────────────────────────
    public function isRead(): bool
    {
        return ! is_null($this->read_at);
    }

    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => Carbon::now()]);
        }
    }

    // ── Presentation accessors (blade-də inline məntiq yazmamaq üçün) ──
    public function getTypeEnumAttribute(): AdminNotificationTypeEnum
    {
        return AdminNotificationTypeEnum::tryFrom($this->type) ?? AdminNotificationTypeEnum::DEFAULT;
    }

    public function getTypeIconAttribute(): string
    {
        return $this->type_enum->icon();
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type_enum->color();
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type_enum->label();
    }

    public function getDetailTemplateAttribute(): string      // compact (index)
    {
        return $this->type_enum->template();
    }

    public function getDetailPageTemplateAttribute(): string  // zəngin (show)
    {
        return $this->type_enum->detailTemplate();
    }
}
```

---

## 6. Route və permission

Bütün route-lar `gopanel` middleware qrupunda olmalıdır. Detail/read/delete-də route-model-binding (`{notification}`) istifadə olunur, sahiblik controller-də yoxlanılır.

```php
Route::group(['middleware' => 'gopanel'], function () {

    Route::prefix('admin-notifications')->name('admin-notifications.')->group(function () {
        Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
        Route::get('/header', [AdminNotificationController::class, 'header'])->name('header');
        Route::get('/view/{notification}', [AdminNotificationController::class, 'view'])->name('view');
        Route::post('/{notification}/read', [AdminNotificationController::class, 'markRead'])->name('read');
        Route::post('/read-all', [AdminNotificationController::class, 'markAllRead'])->name('read-all');
        Route::delete('/{notification}', [AdminNotificationController::class, 'delete'])->name('delete');
        Route::post('/delete-read', [AdminNotificationController::class, 'deleteAllRead'])->name('delete-read');
    });

});
```

`header`, `read`, `read-all`, `delete-read` endpoint-ləri AJAX-dır. Response private admin data saxladığı üçün cache header verilməməlidir. İcazə sistemi (permission) varsa `can:gopanel.notifications.view` əlavə edilə bilər; minimum tələb — admin auth guard.

---

## 7. Controller

Controller **nazik** olmalıdır: yalnız HTTP giriş/çıxış. Bütün sorğu və biznes məntiqi Service-dədir. Hər əməliyyatda bildirişin cari adminə aid olması yoxlanılır (`abort_if 403`).

```php
<?php

namespace App\Http\Controllers\Gopanel\Admins;

use App\Http\Controllers\Controller;
use App\Models\Gopanel\AdminNotification;
use App\Services\Gopanel\Notification\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    public function __construct(protected AdminNotificationService $service) {}

    protected function admin()
    {
        return Auth::guard('gopanel')->user();
    }

    /** Tam bildirişlər səhifəsi */
    public function index()
    {
        $admin         = $this->admin();
        $notifications = $this->service->getAll($admin);
        $unreadCount   = $this->service->unreadCount($admin);

        return view('gopanel.pages.admin-notifications.index', compact('notifications', 'unreadCount'));
    }

    /** Tək bildirişin detail səhifəsi (açılanda oxundu işarələnir) */
    public function view(AdminNotification $notification)
    {
        abort_if($notification->admin_id !== $this->admin()->id, 403);
        $this->service->markRead($notification);

        return view('gopanel.pages.admin-notifications.show', compact('notification'));
    }

    /** Header dropdown HTML — ilk yükləmə + scroll pagination (AJAX) */
    public function header(Request $request): JsonResponse
    {
        $admin   = $this->admin();
        $page    = max(1, (int) $request->get('page', 1));
        $perPage = 8;

        $paginator = AdminNotification::forAdmin($admin->id)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $html = view('gopanel.pages.admin-notifications.partials.header-list', [
            'recent' => collect($paginator->items()),
        ])->render();

        return response()->json([
            'html'         => $html,
            'unread_count' => $this->service->unreadCount($admin),
            'has_more'     => $paginator->hasMorePages(),
            'next_page'    => $paginator->hasMorePages() ? $page + 1 : null,
        ]);
    }

    /** Tək bildirişi oxundu işarələ */
    public function markRead(AdminNotification $notification): JsonResponse
    {
        abort_if($notification->admin_id !== $this->admin()->id, 403);
        $this->service->markRead($notification);

        return response()->json(['success' => true]);
    }

    /** Hamısını oxundu et */
    public function markAllRead(): JsonResponse
    {
        $count = $this->service->markAllRead($this->admin());

        return response()->json(['success' => true, 'count' => $count]);
    }

    /** Tək bildirişi sil */
    public function delete(AdminNotification $notification): JsonResponse
    {
        abort_if($notification->admin_id !== $this->admin()->id, 403);
        $this->service->delete($notification);

        return response()->json(['success' => true]);
    }

    /** Oxunmuş bildirişləri toplu sil */
    public function deleteAllRead(): JsonResponse
    {
        $count = $this->service->deleteAllRead($this->admin());

        return response()->json(['success' => true, 'count' => $count]);
    }
}
```

> `header` endpoint-i hər 5 saniyədə bir çağırılır (polling). Ona görə sorğusu yüngül olmalı və `['admin_id', 'created_at']` indeksindən istifadə etməlidir.

---

## 8. Service

Service iki məsuliyyəti birləşdirir: **göndərmə** (sinxron `send`/`sendToAll` + asinxron `dispatch`) və **oxu/sorğu** (say, siyahı, oxundu, sil). Göndərmədə hər kanal ayrıca `try/catch` içindədir — bir kanal düşsə digərləri işləyir, database record isə həmişə qaytarılır.

```php
<?php

namespace App\Services\Gopanel\Notification;

use App\Enums\Notification\AdminNotificationTypeEnum;
use App\Jobs\SendAdminNotificationJob;
use App\Models\Gopanel\Admin;
use App\Models\Gopanel\AdminNotification;
use App\Services\Activity\LogService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Throwable;

class AdminNotificationService
{
    /** database həmişə daxildir; firebase/mobilepush admin üçün dəstəklənmir */
    public const DEFAULT_CHANNELS = ['database'];
    public const ALL_CHANNELS     = ['database', 'mail', 'sms', 'telegram', 'webpush'];

    protected LogService $log;

    public function __construct()
    {
        $this->log = new LogService('admin-notifications');
    }

    // ── Göndərmə — sinxron ─────────────────────────────────────

    /** Tək adminə bildiriş göndər (istənilən kanallar) */
    public function send(
        Admin $admin,
        AdminNotificationTypeEnum $type,
        string $title,
        string $message,
        array $channels = self::DEFAULT_CHANNELS,
        array $data = [],
        ?Model $notifiable = null,
        ?string $actionUrl = null,
        int $level = 1,
    ): ?AdminNotification {
        $dbRecord = null;

        foreach ($channels as $channel) {
            try {
                $method = AdminNotificationMethodFactory::create($channel);

                if ($channel === 'database') {
                    $dbRecord = $method->send(
                        $admin, $type, $title, $message, $data,
                        $notifiable?->id,
                        $notifiable ? get_class($notifiable) : null,
                        $actionUrl,
                        $level,
                    );
                } else {
                    $method->send($admin, $title, $message, $data);
                }
            } catch (Throwable $e) {
                $this->log->error("Admin bildiriş xətası [{$channel}]: " . $e->getMessage(), [
                    'admin_id' => $admin->id,
                    'type'     => $type->value,
                    'channel'  => $channel,
                ]);
            }
        }

        return $dbRecord;
    }

    /** Bütün aktiv adminlərə (sinxron) */
    public function sendToAll(
        AdminNotificationTypeEnum $type,
        string $title,
        string $message,
        array $channels = self::DEFAULT_CHANNELS,
        array $data = [],
        ?Model $notifiable = null,
        ?string $actionUrl = null,
        int $level = 1,
    ): void {
        Admin::where('is_active', true)->each(
            fn (Admin $admin) => $this->send($admin, $type, $title, $message, $channels, $data, $notifiable, $actionUrl, $level)
        );
    }

    // ── Göndərmə — asinxron (queue) ────────────────────────────

    /**
     * Bütün aktiv adminlərə queue ilə göndər (əsas prosesi bloklamır).
     * Domen servisləri BUNU çağırmalıdır.
     */
    public static function dispatch(
        AdminNotificationTypeEnum $type,
        string $title,
        string $message,
        array $channels = self::DEFAULT_CHANNELS,
        array $data = [],
        ?int $notifiableId = null,
        ?string $notifiableType = null,
        ?string $actionUrl = null,
        int $level = 1,
    ): void {
        SendAdminNotificationJob::dispatch(
            $type, $title, $message, $channels, $data,
            $notifiableId, $notifiableType, $actionUrl, $level
        );
    }

    // ── Oxundu / Sil ───────────────────────────────────────────

    public function markRead(AdminNotification $notification): void
    {
        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => Carbon::now()]);
        }
    }

    public function markAllRead(Admin $admin): int
    {
        return AdminNotification::forAdmin($admin->id)
            ->unread()
            ->update(['read_at' => Carbon::now()]);
    }

    public function delete(AdminNotification $notification): void
    {
        $notification->delete();
    }

    public function deleteAllRead(Admin $admin): int
    {
        return AdminNotification::forAdmin($admin->id)
            ->whereNotNull('read_at')
            ->delete();
    }

    // ── Sorğular ───────────────────────────────────────────────

    public function unreadCount(Admin $admin): int
    {
        return AdminNotification::forAdmin($admin->id)->unread()->count();
    }

    public function getRecent(Admin $admin, int $limit = 10): Collection
    {
        return AdminNotification::forAdmin($admin->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getAll(Admin $admin, int $perPage = 20): LengthAwarePaginator
    {
        return AdminNotification::forAdmin($admin->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
```

> `dispatch()` **static**-dır ki, domen servisləri konstruktor injection olmadan çağıra bilsin. `send()` isə instance metodudur və Job-un içindən istifadə oluna bilər.

---

## 9. Kanal method-ları

Hər kanal ayrı bir class-dır və `Factory` ilə yaradılır. Bu, yeni kanal əlavə etməyi (məs. `slack`) izolyasiya edir. `database` method-u imzası fərqlidir (tam struktur qəbul edir və `AdminNotification` qaytarır); qalan kanallar sadə `(admin, title, message, data)` imzası ilə işləyir.

### Factory

```php
<?php

namespace App\Services\Gopanel\Notification;

use App\Services\Gopanel\Notification\Methods\AdminDatabaseNotification;
use App\Services\Gopanel\Notification\Methods\AdminMailNotification;
use App\Services\Gopanel\Notification\Methods\AdminSmsNotification;
use App\Services\Gopanel\Notification\Methods\AdminTelegramNotification;
use App\Services\Gopanel\Notification\Methods\AdminWebPushNotification;
use InvalidArgumentException;

class AdminNotificationMethodFactory
{
    public static function create(string $channel): AdminDatabaseNotification|AdminMailNotification|AdminSmsNotification|AdminTelegramNotification|AdminWebPushNotification
    {
        return match ($channel) {
            'database' => new AdminDatabaseNotification(),
            'mail'     => new AdminMailNotification(),
            'sms'      => new AdminSmsNotification(),
            'telegram' => new AdminTelegramNotification(),
            'webpush'  => new AdminWebPushNotification(),
            default    => throw new InvalidArgumentException("Naməlum admin bildiriş kanalı: {$channel}"),
        };
    }

    public static function supported(): array
    {
        return ['database', 'mail', 'sms', 'telegram', 'webpush'];
    }
}
```

### Database kanalı (əsas)

`title`/`message` XSS-dən qorunmaq üçün `strip_tags` + `html_entity_decode` edilir. Xəta record-u `null` qaytarır, prosesi partlatmır.

```php
<?php

namespace App\Services\Gopanel\Notification\Methods;

use App\Enums\Notification\AdminNotificationTypeEnum;
use App\Models\Gopanel\Admin;
use App\Models\Gopanel\AdminNotification;
use App\Services\Activity\LogService;

class AdminDatabaseNotification
{
    public function send(
        Admin $admin,
        AdminNotificationTypeEnum $type,
        string $title,
        string $message,
        array $data = [],
        ?int $notifiableId = null,
        ?string $notifiableType = null,
        ?string $actionUrl = null,
        int $level = 1,
    ): ?AdminNotification {
        try {
            return AdminNotification::create([
                'admin_id'        => $admin->id,
                'type'            => $type->value,
                'title'           => html_entity_decode(strip_tags($title)),
                'message'         => html_entity_decode(strip_tags($message)),
                'data'            => ! empty($data) ? $data : null,
                'notifiable_type' => $notifiableType,
                'notifiable_id'   => $notifiableId,
                'action_url'      => $actionUrl,
                'level'           => $level,
            ]);
        } catch (\Throwable $e) {
            LogService::channel('admin-notifications')
                ->error('AdminDatabaseNotification xətası: ' . $e->getMessage(), ['admin_id' => $admin->id]);

            return null;
        }
    }
}
```

### Mail kanalı

`data['email_template']` verilibsə template email, yoxdursa basic email göndərir. Mail queue-ya verilir.

```php
<?php

namespace App\Services\Gopanel\Notification\Methods;

use App\Models\Gopanel\Admin;
use App\Services\Activity\LogService;
use App\Services\Mail\MailService;

class AdminMailNotification
{
    public function send(Admin $admin, string $title, string $message, array $data = []): void
    {
        $log = new LogService('admin-notifications');

        if (empty($admin->email)) {
            $log->warning('AdminMailNotification: adminin emaili yoxdur', ['admin_id' => $admin->id]);
            return;
        }

        try {
            $service = new MailService();
            $service->enableQueue(true);

            if (! empty($data['email_template'])) {
                $service->sendTemplateEmail($admin->email, $data['email_template'], $data);
            } else {
                $service->sendBasicEmail($admin->email, $message, $title);
            }
        } catch (\Throwable $e) {
            $log->error('AdminMailNotification xətası: ' . $e->getMessage(), [
                'admin_email' => $admin->email,
                'exception'   => $e->getTraceAsString(),
            ]);
        }
    }
}
```

### SMS / Telegram / WebPush kanalları (provider-ə hazır skeleton)

Admin modelində telefon/telegram sahəsi olmadığından, hədəf `data`-dan gəlir (`data['phone']`, `data['telegram_chat_id']`). Provider inteqrasiyası gələnə qədər mock loglanır — bu, sistemin gələcəyə açıq qalmasını təmin edir.

```php
// AdminSmsNotification.php
public function send(Admin $admin, string $title, string $message, array $data = []): void
{
    $log   = new LogService('admin-notifications');
    $phone = $data['phone'] ?? null;

    if (empty($phone)) {
        $log->warning('AdminSmsNotification: telefon nömrəsi verilməyib', ['admin_id' => $admin->id]);
        return;
    }

    try {
        $smsText = \Illuminate\Support\Str::limit(html_entity_decode(strip_tags($message)), 150);
        // SMS provider inteqrasiyası gələndə buraya əlavə olunacaq.
        $log->info('Admin SMS göndərmə (mock)', ['phone' => $phone, 'message' => $smsText]);
    } catch (\Throwable $e) {
        $log->error('AdminSmsNotification xətası: ' . $e->getMessage(), ['admin_id' => $admin->id]);
    }
}
```

```php
// AdminTelegramNotification.php
public function send(Admin $admin, string $title, string $message, array $data = []): void
{
    $log    = new LogService('admin-notifications');
    $chatId = $data['telegram_chat_id'] ?? null;

    if (empty($chatId)) {
        $log->warning('AdminTelegramNotification: telegram_chat_id verilməyib', ['admin_id' => $admin->id]);
        return;
    }

    try {
        // Telegram Bot API inteqrasiyası gələndə buraya əlavə olunacaq.
        $log->info('Admin Telegram göndərmə (mock)', ['chat_id' => $chatId, 'title' => $title]);
    } catch (\Throwable $e) {
        $log->error('AdminTelegramNotification xətası: ' . $e->getMessage(), ['admin_id' => $admin->id]);
    }
}
```

`AdminWebPushNotification` da eyni imza ilə (`send(admin, title, message, data)`) yazılır və browser push provider gələnə qədər mock loglayır.

---

## 10. Job (queue)

Job aktiv adminləri gəzir, hər biri üçün seçilmiş kanalları çağırır. Hər kanal ayrıca `try/catch` içindədir — bir adminin bir kanalı düşsə digərləri davam edir.

```php
<?php

namespace App\Jobs;

use App\Enums\Notification\AdminNotificationTypeEnum;
use App\Models\Gopanel\Admin;
use App\Services\Activity\LogService;
use App\Services\Gopanel\Notification\AdminNotificationMethodFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendAdminNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected AdminNotificationTypeEnum $type,
        protected string $title,
        protected string $message,
        protected array $channels = ['database'],
        protected array $data = [],
        protected ?int $notifiableId = null,
        protected ?string $notifiableType = null,
        protected ?string $actionUrl = null,
        protected int $level = 1,
    ) {}

    public function handle(): void
    {
        Admin::where('is_active', true)->each(function (Admin $admin) {
            foreach ($this->channels as $channel) {
                try {
                    $method = AdminNotificationMethodFactory::create($channel);

                    if ($channel === 'database') {
                        $method->send(
                            $admin, $this->type, $this->title, $this->message,
                            $this->data, $this->notifiableId, $this->notifiableType,
                            $this->actionUrl, $this->level,
                        );
                    } else {
                        $method->send($admin, $this->title, $this->message, $this->data);
                    }
                } catch (Throwable $e) {
                    LogService::channel('admin-notifications', false)
                        ->error("Admin bildiriş job xətası [{$channel}]: " . $e->getMessage(), [
                            'type'     => $this->type->value,
                            'admin_id' => $admin->id,
                        ]);
                }
            }
        });
    }
}
```

> `notifiable` obyektini Job-a bütöv model kimi ötürmək yerinə `notifiableId` + `notifiableType` (string) ötürülür — bu, queue payload-unu kiçik saxlayır və serialization problemlərinin qarşısını alır. Çox admin olan sistemdə `Admin::where(...)->each()` yerinə `chunk()` və ya adminlər üzrə ayrı-ayrı job düşünülməlidir.

---

## 11. Trigger — domen servislərindən necə göndərilir

Domen servisi yalnız `AdminNotificationService::dispatch(...)` çağırır. Konstruktor injection, kanal siyahısı və ya admin gəzişi ilə maraqlanmır.

```php
// app/Services/Listing/ListingService.php
use App\Enums\Notification\AdminNotificationTypeEnum;
use App\Services\Gopanel\Notification\AdminNotificationService;

private function dispatchListingCreatedNotification(Listing $listing): void
{
    AdminNotificationService::dispatch(
        AdminNotificationTypeEnum::NEW_LISTING,
        'Yeni elan əlavə edildi',
        "Yeni elan moderasiyaya göndərildi: {$listing->title}",
        data: ['listing_title' => $listing->title, 'company_name' => $listing->company?->name],
        notifiableId: $listing->id,
        notifiableType: Listing::class,
        actionUrl: route('gopanel.listings.show', $listing->uid),
    );
}
```

Əlavə kanallar lazım olduqda `channels` ötürülür:

```php
AdminNotificationService::dispatch(
    AdminNotificationTypeEnum::PAYMENT,
    'Yeni ödəniş',
    "Ödəniş qəbul edildi: {$amount} AZN",
    channels: ['database', 'mail'],
    data: ['amount' => $amount, 'email_template' => 'emails.admin.payment'],
);
```

> **Qayda:** domen kodu heç vaxt birbaşa `AdminNotification::create(...)` yazmamalıdır. Həmişə `dispatch()` (queue) və ya nadir sinxron hallarda `->send()` istifadə olunur. Bu, kanal məntiqini, log-u və admin gəzişini bir yerdə saxlayır.

---

## 12. Blade — Header zəng + dropdown

### `blocks/head.blade.php` — meta tag (JS bunu oxuyur)

```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="admin-notif-url" content="{{ route('gopanel.admin-notifications.header') }}">
```

### `blocks/header.blade.php` — include

```blade
@include('gopanel.blocks.notification-header')
```

### `blocks/notification-header.blade.php` — zəng + dropdown shell

Siyahı ilk açılışda boşdur (spinner); JS `admin-notif-url`-dən doldurur. Badge iki yerdə göstərilir (ikon üstündə + dropdown başlığında).

```blade
<div class="dropdown d-inline-block">
    <button type="button"
            class="btn header-item noti-icon waves-effect position-relative"
            id="page-header-notifications-dropdown"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="bx bx-bell bx-tada font-size-22"></i>
        <span class="badge bg-danger rounded-pill position-absolute top-0 end-0 mt-1"
              id="admin-notif-badge"
              style="display:none;font-size:10px;min-width:18px;padding:2px 5px;"></span>
    </button>

    <div class="dropdown-menu dropdown-menu-end p-0 shadow" style="width:340px;max-width:95vw;">

        {{-- Başlıq --}}
        <div class="px-3 py-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-bell text-primary font-size-16"></i>
                <h6 class="mb-0 fw-semibold">Bildirişlər</h6>
                <span id="admin-notif-badge-inline" class="badge bg-danger rounded-pill"
                      style="display:none;font-size:10px;"></span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:void(0);" id="btn-header-mark-all"
                   class="text-muted font-size-12 d-flex align-items-center gap-1" title="Hamısını oxundu et">
                    <i class="bx bx-check-double font-size-15"></i><span>Oxundu</span>
                </a>
                <a href="{{ route('gopanel.admin-notifications.index') }}" class="text-primary font-size-12 fw-semibold">
                    Hamısına bax
                </a>
            </div>
        </div>

        {{-- Siyahı (JS AJAX ilə doldurur) --}}
        <div data-simplebar style="max-height:320px;overflow-y:auto;" id="admin-notif-list">
            <div class="text-center py-4">
                <i class="bx bx-loader bx-spin text-muted font-size-20"></i>
            </div>
        </div>

        {{-- Footer --}}
        <div class="border-top px-3 py-2 text-center">
            <a href="{{ route('gopanel.admin-notifications.index') }}"
               class="text-primary font-size-13 fw-semibold d-flex align-items-center justify-content-center gap-1">
                Bütün bildirişlərə bax <i class="bx bx-right-arrow-alt font-size-16"></i>
            </a>
        </div>
    </div>
</div>
```

### `partials/header-list.blade.php` — dropdown list item-ləri (server-rendered)

Bu partial `header` endpoint-i tərəfindən render olunub JSON `html` sahəsində qaytarılır. User content `{{ }}` ilə escape olunur.

```blade
@forelse($recent as $notification)
    <div class="notif-drop-item @if(!$notification->isRead()) notif-drop-unread @endif"
         data-id="{{ $notification->id }}">
        <a href="{{ route('gopanel.admin-notifications.view', $notification) }}"
           class="d-flex align-items-start gap-3 text-reset text-decoration-none px-3 py-25"
           onclick="adminNotificationRead({{ $notification->id }}, this)">

            <div class="flex-shrink-0 mt-1">
                <div class="notif-drop-avatar bg-{{ $notification->type_color }}-subtle">
                    <i class="{{ $notification->type_icon }} text-{{ $notification->type_color }}"></i>
                </div>
            </div>

            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <p class="mb-0 font-size-13 @if(!$notification->isRead()) fw-semibold text-dark @else text-body @endif text-truncate"
                       style="max-width:190px;">
                        {{ $notification->title }}
                    </p>
                    @if(!$notification->isRead())
                        <span class="notif-drop-dot bg-danger flex-shrink-0"></span>
                    @endif
                </div>
                <p class="mb-1 font-size-12 text-muted text-truncate" style="max-width:210px;">
                    {{ $notification->message }}
                </p>
                <p class="mb-0 font-size-11 text-muted">
                    <i class="mdi mdi-clock-outline me-1"></i>{{ $notification->created_at->diffForHumans() }}
                </p>
            </div>
        </a>
    </div>
@empty
    <div class="text-center py-4 px-3">
        <div class="avatar-sm mx-auto mb-3">
            <span class="avatar-title rounded-circle bg-light text-muted font-size-20"><i class="bx bx-bell-off"></i></span>
        </div>
        <p class="mb-0 font-size-13 text-muted">Bildiriş yoxdur</p>
    </div>
@endforelse
```

CSS (partial-ın altında və ya global stylesheet-də) `.notif-drop-*` və `.bg-*-subtle` class-larını təyin edir — aşağıda bax.

---

## 13. Blade — Tam siyahı səhifəsi (`index.blade.php`)

Səhifə: başlıq + unread badge, toolbar (hamısını oxundu / oxunmuşları sil), sonra hər bildiriş üçün sətir. Oxunmamışlar sol accent + "YENİ" badge alır. Tipə uyğun compact template `@includeIf` ilə əlavə olunur.

```blade
@extends('gopanel.layouts.main')

@section('content')
<div class="page-content"><div class="container-fluid">

    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0 font-size-18">
            Admin Bildirişləri
            @if($unreadCount > 0)
                <span class="badge bg-danger rounded-pill ms-2">{{ $unreadCount }}</span>
            @endif
        </h4>
    </div>

    <div class="card shadow-sm">
        <div class="card-header border-bottom d-flex align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bx-bell font-size-18 text-primary"></i>
                <h5 class="card-title mb-0">Bütün Bildirişlər</h5>
            </div>
            <div class="d-flex gap-2">
                @if($unreadCount > 0)
                    <button type="button" class="btn btn-sm btn-soft-primary" id="btn-mark-all-read">
                        <i class="bx bx-check-double me-1"></i>Hamısını oxundu et
                    </button>
                @endif
                <button type="button" class="btn btn-sm btn-soft-danger" id="btn-delete-read">
                    <i class="bx bx-trash me-1"></i>Oxunmuşları sil
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            @forelse($notifications as $notification)
                <div class="notif-row d-flex align-items-stretch border-bottom position-relative @if(!$notification->isRead()) notif-unread @endif"
                     id="notif-row-{{ $notification->id }}">

                    @if(!$notification->isRead())
                        <div class="notif-accent bg-{{ $notification->type_color }}"></div>
                    @endif

                    <div class="notif-icon-col d-flex align-items-center justify-content-center px-3">
                        <div class="avatar-sm">
                            <span class="avatar-title rounded-circle bg-{{ $notification->type_color }} bg-soft text-{{ $notification->type_color }} font-size-18">
                                <i class="{{ $notification->type_icon }}"></i>
                            </span>
                        </div>
                    </div>

                    <div class="notif-body flex-grow-1 py-3 pe-3">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <h6 class="mb-0 @if(!$notification->isRead()) fw-bold @endif">{{ $notification->title }}</h6>
                                    @if(!$notification->isRead())
                                        <span class="badge bg-danger" style="font-size:9px;padding:2px 5px;">YENİ</span>
                                    @endif
                                </div>
                                <p class="text-muted mb-2 font-size-13">{{ $notification->message }}</p>
                                <div class="d-flex align-items-center flex-wrap gap-3 font-size-12 text-muted">
                                    <span><i class="mdi mdi-tag-outline me-1"></i>{{ $notification->type_label }}</span>
                                    <span><i class="mdi mdi-clock-outline me-1"></i>{{ $notification->created_at->diffForHumans() }}</span>
                                    @if($notification->isRead())
                                        <span class="text-success"><i class="mdi mdi-check-circle-outline me-1"></i>Oxundu</span>
                                    @endif
                                </div>

                                {{-- Tipə uyğun compact template --}}
                                @if($notification->data)
                                    <div class="mt-2">
                                        @includeIf($notification->detail_template, ['notification' => $notification])
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex flex-shrink-0 align-items-center gap-1 pt-1">
                                <a href="{{ route('gopanel.admin-notifications.view', $notification) }}"
                                   class="btn btn-sm btn-soft-info" title="Ətraflı bax"><i class="bx bx-show"></i></a>
                                @if(!$notification->isRead())
                                    <button type="button" class="btn btn-sm btn-soft-success btn-mark-read"
                                            data-id="{{ $notification->id }}" title="Oxundu et"><i class="bx bx-check"></i></button>
                                @endif
                                <button type="button" class="btn btn-sm btn-soft-danger btn-delete-notif"
                                        data-id="{{ $notification->id }}" title="Sil"><i class="bx bx-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bx bx-bell-off font-size-48 d-block mb-3 text-muted"></i>
                    <h6 class="text-muted">Bildiriş yoxdur</h6>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="card-footer border-top">{{ $notifications->links() }}</div>
        @endif
    </div>

</div></div>
@endsection

@push('scripts')
<script>
$(function () {
    var markReadUrl    = '{{ route('gopanel.admin-notifications.read', ':id') }}';
    var deleteUrl      = '{{ route('gopanel.admin-notifications.delete', ':id') }}';
    var markAllReadUrl = '{{ route('gopanel.admin-notifications.read-all') }}';
    var deleteReadUrl  = '{{ route('gopanel.admin-notifications.delete-read') }}';

    $(document).on('click', '.btn-mark-read', function () {
        var id = $(this).data('id'), $row = $('#notif-row-' + id);
        $.post(markReadUrl.replace(':id', id), function () {
            $row.removeClass('notif-unread');
            $row.find('.notif-accent').remove();
            $row.find('h6').removeClass('fw-bold');
            $row.find('.badge.bg-danger').remove();
            $row.find('.btn-mark-read').remove();
        });
    });

    $(document).on('click', '.btn-delete-notif', function () {
        var id = $(this).data('id'), $row = $('#notif-row-' + id);
        $.ajax({ url: deleteUrl.replace(':id', id), type: 'DELETE',
            success: function () { $row.fadeOut(250, function () { $(this).remove(); }); } });
    });

    $('#btn-mark-all-read').on('click', function () {
        $.post(markAllReadUrl, function () { location.reload(); });
    });

    $('#btn-delete-read').on('click', function () {
        Swal.fire({ title: 'Əminsiniz?', text: 'Bütün oxunmuş bildirişlər silinəcək.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Bəli, sil', cancelButtonText: 'Ləğv et',
        }).then(function (r) {
            if (r.isConfirmed) $.post(deleteReadUrl, function () { location.reload(); });
        });
    });
});
</script>
@endpush
```

> AJAX POST/DELETE üçün CSRF lazımdır. Ya `<meta name="csrf-token">`-dan global `$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': ... } })` qurun, ya da hər sorğuya `_token` əlavə edin (aşağıdakı `main.js` `_token` göndərir).

---

## 14. Blade — Detail səhifəsi (`show.blade.php`)

İki sütunlu layout: solda əsas mesaj + tipə uyğun **zəngin** template (`detail_page_template`), sağda metadata (status, tip, tarix, əlaqəli obyekt, action URL, data açar-dəyərləri). Controller səhifə açılanda bildirişi oxundu edir.

```blade
@extends('gopanel.layouts.main')

@section('content')
<div class="page-content"><div class="container-fluid">

    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0 font-size-18">Bildiriş Detalı</h4>
        <a href="{{ route('gopanel.admin-notifications.index') }}" class="btn btn-sm btn-soft-secondary">
            <i class="bx bx-arrow-back me-1"></i>Bütün bildirişlər
        </a>
    </div>

    <div class="row">
        {{-- Sol: əsas məzmun --}}
        <div class="col-xl-8">
            <div class="card shadow-sm">
                <div class="card-header border-bottom d-flex align-items-center justify-content-between py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title rounded-circle bg-{{ $notification->type_color }} bg-soft text-{{ $notification->type_color }} font-size-20">
                                <i class="{{ $notification->type_icon }}"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-semibold">{{ $notification->title }}</h5>
                            <span class="badge bg-{{ $notification->type_color }} bg-soft text-{{ $notification->type_color }} font-size-11 mt-1">
                                {{ $notification->type_label }}
                            </span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        @if($notification->action_url)
                            <a href="{{ $notification->action_url }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bx bx-link-external me-1"></i>Keç
                            </a>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btn-delete-this"
                                data-id="{{ $notification->id }}"><i class="bx bx-trash me-1"></i>Sil</button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1 font-size-12 text-muted">
                                <i class="mdi mdi-clock-outline"></i>
                                <span>{{ $notification->created_at->format('d.m.Y H:i') }}</span>
                                <span>({{ $notification->created_at->diffForHumans() }})</span>
                            </div>
                            <p class="font-size-15 mb-0 text-dark">{{ $notification->message }}</p>
                        </div>
                    </div>

                    {{-- Tipə uyğun zəngin detail --}}
                    <div class="border-top pt-4">
                        @includeIf($notification->detail_page_template, ['notification' => $notification])
                    </div>
                </div>
            </div>
        </div>

        {{-- Sağ: metadata --}}
        <div class="col-xl-4">
            <div class="card shadow-sm">
                <div class="card-header border-bottom py-3">
                    <h6 class="mb-0 d-flex align-items-center gap-2"><i class="bx bx-info-circle text-primary"></i> Məlumatlar</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless mb-0 notif-meta-table"><tbody>
                        <tr>
                            <td class="text-muted font-size-12 py-2 ps-3" style="width:40%;">Status</td>
                            <td class="py-2">
                                @if($notification->isRead())
                                    <span class="badge bg-success bg-soft text-success">Oxunub</span>
                                @else
                                    <span class="badge bg-warning bg-soft text-warning">Oxunmayıb</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="border-top">
                            <td class="text-muted font-size-12 py-2 ps-3">Tip</td>
                            <td class="py-2">
                                <span class="badge bg-{{ $notification->type_color }} bg-soft text-{{ $notification->type_color }}">
                                    {{ $notification->type_label }}
                                </span>
                            </td>
                        </tr>
                        <tr class="border-top">
                            <td class="text-muted font-size-12 py-2 ps-3">Tarix</td>
                            <td class="font-size-13 py-2">{{ $notification->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                        @if($notification->notifiable_type && $notification->notifiable_id)
                            <tr class="border-top">
                                <td class="text-muted font-size-12 py-2 ps-3">Əlaqəli obyekt</td>
                                <td class="font-size-13 py-2">
                                    <span class="badge bg-light text-dark border">
                                        {{ class_basename($notification->notifiable_type) }} #{{ $notification->notifiable_id }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                        @if(!empty($notification->data))
                            <tr class="border-top">
                                <td class="text-muted font-size-12 py-2 ps-3">Data</td>
                                <td class="py-2">
                                    @foreach($notification->data as $key => $val)
                                        @if(is_scalar($val))
                                            <div class="font-size-12">
                                                <span class="text-muted">{{ $key }}:</span>
                                                <span class="fw-semibold ms-1">{{ $val }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                        @endif
                    </tbody></table>
                </div>
            </div>
        </div>
    </div>

</div></div>
@endsection

@push('scripts')
<script>
$(function () {
    var deleteUrl = '{{ route('gopanel.admin-notifications.delete', ':id') }}';
    var indexUrl  = '{{ route('gopanel.admin-notifications.index') }}';

    $('#btn-delete-this').on('click', function () {
        var id = $(this).data('id');
        Swal.fire({ title: 'Bildirişi silmək istəyirsiniz?', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Bəli, sil', cancelButtonText: 'Ləğv et',
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({ url: deleteUrl.replace(':id', id), type: 'DELETE',
                    success: function () { window.location.href = indexUrl; } });
            }
        });
    });
});
</script>
@endpush
```

---

## 15. Blade — Tip template-ləri (compact + detail)

Hər tip üçün **iki** blade: `templates/<type>.blade.php` (siyahıda kiçik badge-lər) və `templates/detail/<type>.blade.php` (detail səhifəsində zəngin kartlar). Hər ikisi yalnız `$notification->data` üzərində işləyir və dəyər yoxdursa heç nə göstərmir.

### Compact — `templates/new_listing.blade.php`

```blade
@php $data = $notification->data ?? []; @endphp
<div class="d-flex flex-wrap gap-2 font-size-12">
    @if(!empty($data['listing_title']))
        <span class="badge bg-info bg-soft text-info"><i class="bx bx-list-ul me-1"></i>{{ $data['listing_title'] }}</span>
    @endif
    @if(!empty($data['company_name']))
        <span class="badge bg-secondary bg-soft text-secondary"><i class="bx bx-building me-1"></i>{{ $data['company_name'] }}</span>
    @endif
    @if(!empty($data['category']))
        <span class="badge bg-warning bg-soft text-warning"><i class="bx bx-tag me-1"></i>{{ $data['category'] }}</span>
    @endif
</div>
```

### Detail — `templates/detail/new_listing.blade.php`

```blade
@php $d = $notification->data ?? []; @endphp
<div class="notif-detail-card"><div class="row g-3">

    @if(!empty($d['listing_title']))
    <div class="col-12">
        <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-info bg-soft">
            <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title rounded-circle bg-info bg-soft text-info font-size-20"><i class="bx bx-list-ul"></i></span>
            </div>
            <div>
                <p class="text-muted font-size-12 mb-0">Elan adı</p>
                <h5 class="mb-0 text-info">{{ $d['listing_title'] }}</h5>
            </div>
        </div>
    </div>
    @endif

    @if(!empty($d['company_name']))
    <div class="col-sm-6">
        <div class="detail-field">
            <span class="detail-label"><i class="bx bx-building me-1"></i>Şirkət</span>
            <span class="detail-value">{{ $d['company_name'] }}</span>
        </div>
    </div>
    @endif

    @if($notification->action_url)
    <div class="col-12 mt-2">
        <a href="{{ $notification->action_url }}" class="btn btn-sm btn-soft-info" target="_blank">
            <i class="bx bx-link-external me-1"></i>Elanı Gopaneldə aç
        </a>
    </div>
    @endif

</div></div>
```

### `templates/default.blade.php` və `templates/detail/default.blade.php`

`default` template-lər həmişə mövcud olmalıdır — naməlum tip və ya tipə uyğun template yoxdursa fallback rolunu oynayır. Sadəcə `$notification->data`-nı açar-dəyər kimi göstərir:

```blade
{{-- templates/detail/default.blade.php --}}
@php $d = $notification->data ?? []; @endphp
@if(!empty($d))
<div class="row g-3">
    @foreach($d as $key => $val)
        @if(is_scalar($val))
        <div class="col-sm-6">
            <div class="detail-field">
                <span class="detail-label">{{ $key }}</span>
                <span class="detail-value">{{ $val }}</span>
            </div>
        </div>
        @endif
    @endforeach
</div>
@else
    <p class="text-muted mb-0">Əlavə məlumat yoxdur.</p>
@endif
```

> `@includeIf` istifadə olunur ki, hansısa tip üçün blade hələ yaradılmayıbsa səhifə partlamasın. Amma məsləhət: hər enum case üçün ən azı `default`-a bənzər sadə template yarat.

---

## 16. JavaScript modulu (`public/assets/gopanel/js/main.js`)

Modul üç işi görür: **polling** (badge + siyahı hər 5s yenilənir), **infinite scroll** (dropdown-da səhifə-səhifə yükləmə), **mark-read** (dropdown item-ə kliklədikdə). URL `<meta name="admin-notif-url">`-dən oxunur; header endpoint `/header`-dir və digər endpoint-lər ondan `replace` ilə düzəldilir.

```javascript
// ─── Admin Bildiriş Polling + Infinite Scroll ────────────────────────────
var token = document.querySelector('meta[name="csrf-token"]')
    ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : null;

var adminNotifUrl = document.querySelector('meta[name="admin-notif-url"]')
    ? document.querySelector('meta[name="admin-notif-url"]').getAttribute('content') : null;

var _notifPage    = 1;
var _notifHasMore = false;
var _notifLoading = false;

function loadAdminNotifications(append) {
    if (!adminNotifUrl || !$('#admin-notif-list').length) return;
    if (_notifLoading) return;

    _notifLoading = true;
    var page = append ? _notifPage : 1;

    $.ajax({
        url: adminNotifUrl + '?page=' + page,
        type: 'GET',
        success: function (res) {
            if (append) {
                $('#admin-notif-list .notif-load-more-spinner').remove();
                $('#admin-notif-list').append(res.html);
            } else {
                $('#admin-notif-list').html(res.html);
                _notifPage = 1;
            }

            _notifHasMore = res.has_more || false;
            if (_notifHasMore) _notifPage = res.next_page;

            var count = res.unread_count || 0;
            if (count > 0) {
                $('#admin-notif-badge').text(count).show();
                $('#admin-notif-badge-inline').text(count).show();
            } else {
                $('#admin-notif-badge').hide();
                $('#admin-notif-badge-inline').hide();
            }
        },
        complete: function () { _notifLoading = false; }
    });
}

function adminNotificationRead(id, el) {
    if (!token) return;
    var $item = $(el);
    if ($item.hasClass('unread-notification')) {
        $.post(
            adminNotifUrl.replace('/header', '/' + id + '/read'),
            { _token: token },
            function () { $item.removeClass('unread-notification'); }
        );
    }
}

function initAdminNotifScroll() {
    var $dropdown = $('#admin-notif-list');
    if (!$dropdown.length) return;

    $dropdown.off('scroll.adminNotif').on('scroll.adminNotif', function () {
        if (!_notifHasMore || _notifLoading) return;
        var threshold = 60;
        if (this.scrollTop + this.clientHeight >= this.scrollHeight - threshold) {
            $dropdown.append('<div class="notif-load-more-spinner text-center py-2"><i class="bx bx-loader bx-spin text-muted"></i></div>');
            loadAdminNotifications(true);
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {

    if (adminNotifUrl) {
        loadAdminNotifications(false);
        setInterval(function () { loadAdminNotifications(false); }, 5000); // polling
    }

    // Dropdown açılanda scroll listener qur (simplebar hazır olduqdan sonra)
    $(document).on('shown.bs.dropdown', '[data-bs-toggle="dropdown"]', function () {
        initAdminNotifScroll();
    });

    // Header-dən "hamısını oxundu"
    $(document).on('click', '#btn-header-mark-all', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!token) return;
        $.post(
            adminNotifUrl.replace('/header', '/read-all'),
            { _token: token },
            function () { loadAdminNotifications(false); }
        );
    });
});
```

### JS qeydləri

- **Polling intervalı** 5s-dir. Çox admin/çox tab-lı sistemdə bu server yükünü artıra bilər — 15–30s daha təhlükəsiz default-dur, və ya tab görünmür (`document.hidden`) ikən polling dayandırıla bilər.
- `adminNotifUrl.replace('/header', ...)` yanaşması bütün endpoint-ləri bir meta URL-dən düzəldir; bu, JS-də route hardcode etməkdən yaxşıdır, amma header route-unun `/header` ilə bitməsi şərtdir.
- Infinite scroll simplebar/native scroll konteynerinin `scroll` event-inə bağlanır; dropdown hər açılanda yenidən bağlanır (`off/on`).
- Mark-read `unread-notification` class-ına baxır — partial-da item-lərə bu class-ı əlavə etmək gərəkdirsə `notif-drop-unread` yerinə/əlavəsinə istifadə edin.

---

## 17. CSS

Siyahı, dropdown və detail üçün class-lar. `.bg-*-subtle` Bootstrap versiyanızda yoxdursa burada təyin olunur.

```css
/* index siyahısı */
.notif-row { transition: background-color .15s; }
.notif-row:hover { background-color: #f8f9fc; }
.notif-unread { background-color: #f4f7ff; }
.notif-accent { width: 4px; flex-shrink: 0; }
.notif-icon-col { width: 72px; flex-shrink: 0; }

/* header dropdown */
.py-25 { padding-top: 10px; padding-bottom: 10px; }
.notif-drop-item { border-bottom: 1px solid #f0f0f0; transition: background .12s; }
.notif-drop-item:last-child { border-bottom: none; }
.notif-drop-item:hover { background-color: #f8f9fb; }
.notif-drop-unread { background-color: #f4f7ff; border-left: 3px solid #556ee6; }
.notif-drop-unread:hover { background-color: #edf1ff; }
.notif-drop-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; font-size: 16px;
}
.notif-drop-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }

.bg-primary-subtle   { background-color: #eef2ff !important; }
.bg-success-subtle   { background-color: #e8f7ee !important; }
.bg-danger-subtle    { background-color: #fdecea !important; }
.bg-warning-subtle   { background-color: #fff8e6 !important; }
.bg-info-subtle      { background-color: #e6f5fd !important; }
.bg-secondary-subtle { background-color: #f0f0f4 !important; }

/* detail template sahələri */
.detail-field {
    display: flex; flex-direction: column; gap: 4px;
    padding: 10px 14px; background: #f8f9fb; border-radius: 8px; height: 100%;
}
.detail-label {
    font-size: 11px; color: #74788d; font-weight: 500;
    text-transform: uppercase; letter-spacing: .4px;
}
.detail-value { font-size: 14px; color: #343a40; font-weight: 500; }
.notif-meta-table td { vertical-align: middle; }
.notif-meta-table tr:hover td { background-color: #f8f9fc; }
```

---

## 18. Layout inteqrasiyası — yığcam xülasə

1. `blocks/head.blade.php`-ə iki meta əlavə et: `csrf-token` və `admin-notif-url`.
2. `blocks/header.blade.php`-də zəng ikonuna `@include('gopanel.blocks.notification-header')`.
3. `main.js` layout-un altında yüklənir (jQuery + Bootstrap dropdown + SweetAlert2 tələb olunur).
4. Route qrupu `gopanel` middleware altında.
5. Domen servislərində uyğun yerlərdə `AdminNotificationService::dispatch(...)`.
6. Queue işçisi işləməlidir (`php artisan queue:work`), əks halda queue kanalında bildiriş yaranmaz. Test/dev üçün `QUEUE_CONNECTION=sync` istifadə oluna bilər.

---

## 19. Təhlükəsizlik

- bütün route-lar `gopanel` auth middleware tələb edir;
- `view/read/delete` əməliyyatlarında `abort_if($notification->admin_id !== $admin->id, 403)` — admin başqasının bildirişini görə/silə bilməz;
- `title`/`message` database-ə yazılmadan `strip_tags` + `html_entity_decode` edilir (stored XSS-in qarşısı);
- blade bütün user/data content-ini `{{ }}` ilə escape edir; `{!! !!}` istifadə olunmur;
- `action_url` xarici keçid ola bildiyi üçün `target="_blank"` verilir; mümkünsə yalnız daxili route-lara icazə verin və ya host whitelist edin;
- `header` partial-ı yalnız server-owned presentation-dur; JSON `html` sahəsi authenticated endpoint-dən gəlir;
- polling/AJAX POST-ları CSRF token tələb edir;
- exception detalı log-a yazılır, response-da göstərilmir.

---

## 20. Performans və miqyas

- `['admin_id', 'read_at']` indeksi unread count üçün; `['admin_id', 'created_at']` siyahı/dropdown üçün kritikdir;
- `header` endpoint hər 5s çağırıldığından yüngül olmalı — yalnız `paginate(8)` + `count()`;
- çox admin olan sistemdə `SendAdminNotificationJob` içində `Admin::each()` yerinə `chunk()` və ya per-admin job düşünün;
- unread count çox oxunursa qısa (10–30s) cache düşünülə bilər, amma real-time hissi azalır;
- polling intervalını 5s-dən 15–30s-ə qaldırmaq server yükünü ciddi azaldır;
- köhnə oxunmuş bildirişləri təmizləmək üçün scheduled job (məs. 90 gündən köhnə `read_at`) əlavə edin;
- `notifiable` üzərindən çox sorğu olarsa polymorphic index/eager-load nəzərə alın.

---

## 21. Test checklist

Backend:

- admin olmayan istifadəçi bütün route-larda `403`/redirect alır;
- admin başqa adminin bildirişini `view/read/delete` edə bilmir (`403`);
- `dispatch()` queue-ya `SendAdminNotificationJob` atır;
- job yalnız `is_active` adminlərə database record yaradır;
- `database` kanalı həmişə record yaradır, əlavə kanal xətası record-u pozmur;
- `title`/`message` HTML tag-larından təmizlənir;
- `markAllRead` yalnız cari adminin unread-lərini yeniləyir;
- `deleteAllRead` yalnız `read_at` dolu olanları silir (soft delete);
- `header` endpoint düzgün `has_more`/`next_page`/`unread_count` qaytarır;
- `unreadCount` yalnız cari admin + `read_at IS NULL` sayır.

Frontend:

- səhifə yüklənəndə badge + dropdown dolur;
- yeni bildiriş 5s içində badge-də görünür;
- dropdown-da aşağı scroll növbəti səhifəni yükləyir;
- item-ə klik oxundu edir və dropdown bağlanmadan işləyir;
- "hamısını oxundu" badge-i sıfırlayır;
- index-də mark-read sətri anında yeniləyir (accent/badge/düymə çıxır);
- silmə sətri fade ilə çıxarır;
- oxunmuşları sil təsdiq (Swal) tələb edir;
- tipə uyğun compact/detail template düzgün render olunur;
- data boş olduqda template heç nə göstərmir, səhifə partlamaz.

---

## 22. Yeni layihədə tətbiq ardıcıllığı

1. Real admin cədvəlini (`admins`/`users`/`staff`) və auth guard-ı təsbit et.
2. `admin_notifications` migration-ını yarat və `constrained()`-i düz cədvələ yönəlt.
3. Layihənin domen hadisələrini siyahıla (yeni sifariş, yeni user, ödəniş…) və hər biri üçün enum `case` seç.
4. `AdminNotificationTypeEnum`-u bu case-lərlə + icon/color/label/template ilə yaz.
5. Model, Service, Factory, Method-lar, Job-u köçür/adapte et.
6. Route qrupu + Controller-i əlavə et.
7. `head`/`header`/`notification-header` blade-lərini və meta tag-ı əlavə et.
8. `index` + `show` + `partials/header-list` blade-lərini əlavə et.
9. Hər tip üçün `templates/<type>` və `templates/detail/<type>` (+ `default`) yaz.
10. `main.js` polling/scroll/mark-read blokunu əlavə et.
11. Domen servislərində uyğun yerlərdə `AdminNotificationService::dispatch(...)` çağır.
12. Queue işçisini işə sal, feature test-lərini yaz.

---

## 23. Yekun qəbul meyarları

Implementasiya hazır sayılır, əgər:

- domen hadisəsi `dispatch()` çağırır və əsas prosesi bloklamır (queue);
- bütün aktiv adminlər database bildirişi alır; əlavə kanal xətası record-u pozmur;
- header-də canlı badge + dropdown + infinite scroll işləyir;
- index-də oxundu/sil/hamısını oxundu/oxunmuşları sil AJAX ilə işləyir;
- hər admin yalnız öz bildirişlərini görür və idarə edir (`403` qorunması);
- hər tip öz icon/color/label və compact/detail template-i ilə düzgün render olunur;
- `title`/`message` escape/sanitize olunur, blade `{{ }}` istifadə edir;
- DB indeksləri unread count və siyahı sorğularını dəstəkləyir;
- enum/model/service adları başqa layihənin domeninə uyğun dəyişdirilə bilir;
- user (son istifadəçi) bildiriş sistemi ilə admin bildiriş sistemi tam ayrı qalır.
```
