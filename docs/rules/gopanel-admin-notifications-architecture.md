# Gopanel Admin Notifications: Reusable Architecture and Implementation Task

## 1. Purpose

This document explains how another AI model or developer should build or migrate an admin-notification system into a Laravel Gopanel.

It describes notifications sent to panel administrators about application events such as new records, moderation requests, payments, reports or system alerts. It is separate from notifications sent to public users and separate from marketing campaigns or scheduled customer notifications.

The target project may not have listings, tenders, subscriptions or reviews. Notification types and templates must be adapted to its real modules.

Required capabilities:

- persistent database notifications per admin;
- recipient selection by admin, role, permission or audience rule;
- header bell with unread counter and incremental loading;
- full paginated notification inbox;
- type-specific list and detail presentation;
- mark one/all as read;
- delete one or read notifications;
- queued multi-channel delivery;
- database, mail and optional external channels;
- correct permissions, ownership, retries, idempotency, audit and retention;
- Blade, JavaScript/AJAX, loaders, empty/error states and tests.

## 2. Existing Gopanel behavior analyzed

The source implementation has these useful ideas:

- one `admin_notifications` row per recipient admin;
- polymorphic link to the business object;
- enum-based type label, icon, color and Blade template;
- a header dropdown loaded with AJAX pagination;
- an unread badge and mark-read actions;
- full inbox and type-specific detail page;
- `AdminNotificationService` for read/delete and sending;
- queue job for broadcasting an event to active admins;
- channel factory for database, email, SMS, Telegram and web push;
- ownership checks before read/view/delete.

The migrated architecture must also correct these weaknesses:

- one queue job must not loop through an unlimited number of admins and channels;
- swallowed channel exceptions must not make failed deliveries appear successful;
- SMS, Telegram and web-push placeholders must not be advertised as working channels;
- channel recipients should come from verified admin preferences, not arbitrary event `data`;
- notification types should not depend on many model accessors returning Blade paths;
- action URLs must be validated to prevent unsafe external/open-redirect behavior;
- metadata and detail templates must use documented payload schemas;
- permissions should be explicit even though each admin normally sees only their own inbox;
- inline page JavaScript/CSS should move to versioned assets.

## 3. Core concepts

Use distinct concepts:

| Concept | Meaning |
|---|---|
| Event | something happened in the business domain |
| Notification definition | type, title/message and payload derived from the event |
| Audience | which administrators should receive it |
| Inbox record | persistent notification belonging to one admin |
| Delivery | an attempt through database/mail/etc. |
| Read state | whether that admin read the inbox item |
| Action | safe link/button related to the notification |

The domain event should not know Blade template paths or perform SMTP calls. It should dispatch a notification command after its transaction commits.

## 4. Recommended file structure

```text
app/
  Enums/AdminNotifications/
    AdminNotificationType.php
    AdminNotificationLevel.php
    AdminNotificationChannel.php
  Events/
    DomainEvent.php
  Http/Controllers/Gopanel/AdminNotificationController.php
  Http/Requests/Gopanel/AdminNotifications/
    AdminNotificationIndexRequest.php
    MarkNotificationsReadRequest.php
  Jobs/AdminNotifications/
    ResolveAdminNotificationAudience.php
    DeliverAdminNotification.php
  Models/Gopanel/
    AdminNotification.php
    AdminNotificationDelivery.php
    AdminNotificationPreference.php
  Queries/Gopanel/AdminNotifications/
    AdminNotificationInboxQuery.php
  Services/Gopanel/AdminNotifications/
    AdminNotificationDispatcher.php
    AdminNotificationAudienceResolver.php
    AdminNotificationInboxService.php
    AdminNotificationPayloadRegistry.php
    Channels/
      AdminNotificationChannel.php
      DatabaseChannel.php
      MailChannel.php
      TelegramChannel.php
      WebPushChannel.php
  ViewModels/Gopanel/AdminNotificationViewModel.php
resources/views/gopanel/admin-notifications/
  index.blade.php
  show.blade.php
  partials/header-dropdown.blade.php
  partials/inbox-item.blade.php
  partials/empty.blade.php
  templates/compact/*.blade.php
  templates/detail/*.blade.php
public/assets/gopanel/js/modules/admin-notifications.js
tests/Feature/Gopanel/AdminNotifications/
tests/Unit/AdminNotifications/
```

## 5. Database design

### Inbox table

```php
Schema::create('admin_notifications', function (Blueprint $table) {
    $table->id();
    $table->ulid('uid')->unique();
    $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
    $table->nullableMorphs('subject');
    $table->string('event_id', 100)->nullable();
    $table->string('type', 80)->index();
    $table->string('level', 20)->default('info')->index();
    $table->string('title', 255);
    $table->text('message');
    $table->string('action_name', 80)->nullable();
    $table->string('action_url', 500)->nullable();
    $table->json('data')->nullable();
    $table->timestamp('read_at')->nullable();
    $table->timestamp('seen_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['admin_id', 'read_at', 'created_at']);
    $table->index(['admin_id', 'type', 'created_at']);
    $table->unique(['admin_id', 'event_id'], 'admin_notification_event_unique');
});
```

`event_id` is an idempotency key. Make it required for retryable domain events when possible, for example `listing.created:01J...`. The unique constraint prevents queue retries from creating duplicates for the same admin.

`seen_at` is optional. Use it only if opening the dropdown should mean “seen” while opening detail means “read”. If the product does not need that distinction, omit it.

### Delivery attempts

For multi-channel reliability, track delivery separately:

```php
Schema::create('admin_notification_deliveries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('admin_notification_id')->constrained()->cascadeOnDelete();
    $table->string('channel', 30);
    $table->string('status', 20)->default('pending');
    $table->unsignedSmallInteger('attempts')->default(0);
    $table->string('provider_message_id')->nullable();
    $table->text('last_error')->nullable();
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('failed_at')->nullable();
    $table->timestamps();

    $table->unique(['admin_notification_id', 'channel']);
});
```

Do not store secrets, full provider credentials or sensitive message bodies in delivery errors.

### Preferences

Optional preferences:

```text
admin_id + type/category + channel + enabled
```

Critical security alerts may ignore opt-out according to product policy. Store verified channel destinations on the admin/profile integration, not inside arbitrary notification payload data.

## 6. Notification type registry

Use an enum or registry for presentation and payload validation:

```php
enum AdminNotificationType: string
{
    case DEFAULT = 'default';
    case NEW_USER = 'new_user';
    case PAYMENT_REVIEW = 'payment_review';
    case CONTENT_REVIEW = 'content_review';
    case REPORT_OPENED = 'report_opened';
    case SYSTEM_ALERT = 'system_alert';

    public function presentation(): array
    {
        return match ($this) {
            self::NEW_USER => [
                'label' => 'New user',
                'icon' => 'bx bx-user-plus',
                'color' => 'success',
                'compact_view' => 'gopanel.admin-notifications.templates.compact.new-user',
                'detail_view' => 'gopanel.admin-notifications.templates.detail.new-user',
            ],
            default => [
                'label' => 'General',
                'icon' => 'bx bx-bell',
                'color' => 'primary',
                'compact_view' => 'gopanel.admin-notifications.templates.compact.default',
                'detail_view' => 'gopanel.admin-notifications.templates.detail.default',
            ],
        };
    }
}
```

Keep a default fallback for old or removed types. Template mapping must come from trusted application code, never directly from a database/request value.

Define a payload schema per type. Example:

```php
'new_user' => [
    'required' => ['user_uid', 'display_name'],
    'optional' => ['email_masked', 'register_platform'],
    'forbidden' => ['password', 'token', 'otp'],
],
```

Prefer stable scalar snapshots in `data`. Do not serialize full Eloquent models into queue payloads or JSON columns.

## 7. Level/severity

Use semantic values rather than unexplained integers:

```text
info | success | warning | critical
```

Level affects color, ordering priority and external channels only when explicitly configured. It must not grant authorization or allow raw HTML.

## 8. Audience resolution

Do not always send every event to every active admin. Supported rules may include:

- one explicit admin;
- admins with a permission, such as `gopanel.listings.review`;
- admins with one or more roles;
- team/tenant/region ownership;
- all active admins for true system-wide alerts.

```php
final class AdminNotificationAudienceResolver
{
    public function ids(AdminNotificationAudience $audience): LazyCollection
    {
        return Admin::query()
            ->where('is_active', true)
            ->when($audience->permission, fn ($q, $permission) =>
                $q->whereHas('permissions', fn ($p) => $p->where('name', $permission)))
            ->when($audience->adminIds, fn ($q, $ids) => $q->whereIn('id', $ids))
            ->orderBy('id')
            ->lazyById();
    }
}
```

Audience rules must be resolved server-side. Never accept unrestricted admin IDs/roles from a public event request.

## 9. Dispatch flow

Recommended sequence:

```text
Domain transaction commits
    → domain event/listener creates notification command
    → audience resolver chunks admin IDs
    → one inbox row per admin, idempotently
    → one delivery job per inbox item/channel
    → header receives it through polling or broadcasting
```

Dispatch notifications only after the related business transaction commits:

```php
DB::afterCommit(fn () => $dispatcher->dispatch(
    eventId: "report.opened:{$report->uid}",
    type: AdminNotificationType::REPORT_OPENED,
    audience: AdminNotificationAudience::permission('gopanel.reports.index'),
    payload: [...],
    subject: $report,
    action: AdminNotificationAction::route('gopanel.reports.show', $report),
));
```

If using events/listeners, configure the listener/job for after-commit behavior.

## 10. Dispatcher and queue design

Do not use one job that calls `Admin::each()` for the entire audience. It creates long jobs, memory/time risks and ambiguous retries.

Recommended design:

1. `ResolveAdminNotificationAudience` obtains IDs in chunks.
2. It inserts/upserts inbox rows using `event_id` idempotency.
3. It dispatches `DeliverAdminNotification` jobs per notification/channel or in bounded batches.
4. Each delivery job has its own retry/backoff/failure state.

```php
final class DeliverAdminNotification implements ShouldQueue
{
    public int $tries = 5;

    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(
        AdminNotificationChannelRegistry $channels,
        AdminNotification $notification
    ): void {
        $delivery = $notification->deliveries()
            ->firstOrCreate(['channel' => $this->channel]);

        $channels->for($this->channel)->send($notification);
        $delivery->markSent();
    }

    public function failed(Throwable $exception): void
    {
        // Mark only this channel delivery as failed and store a redacted error.
    }
}
```

Do not catch and suppress all channel exceptions inside `handle()`. Throw retryable failures so the queue can retry. Convert permanent failures into an explicit failed state without retrying forever.

## 11. Channel contract

```php
interface AdminNotificationChannel
{
    public function send(AdminNotification $notification): DeliveryResult;
}
```

Registry/container binding is preferable to a static factory that constructs dependencies with `new`:

```php
$this->app->tag([
    DatabaseChannel::class,
    MailChannel::class,
    TelegramChannel::class,
], 'admin-notification-channels');
```

Rules:

- database is the canonical inbox channel;
- email uses a queued Mailable and verified admin email;
- SMS uses a verified admin phone and strict length/content rules;
- Telegram uses a verified linked chat ID;
- web push uses stored subscriptions and removes expired endpoints;
- unsupported/mock channels must be disabled in configuration;
- one channel failure must not delete or duplicate the inbox item;
- channel-specific payload rendering belongs to the channel/template layer.

## 12. Action URL safety

Prefer route name plus route parameters in the notification command, then generate the URL server-side. If storing a URL:

- default to internal Gopanel routes;
- validate the host/scheme for allowed external URLs;
- never use `javascript:`, `data:` or untrusted schemes;
- re-authorize the destination when the admin opens it;
- use `rel="noopener noreferrer"` for `_blank` links;
- show no action when the related record no longer exists.

A notification is not an authorization grant.

## 13. Model and query

```php
final class AdminNotification extends Model
{
    use SoftDeletes;

    protected $casts = [
        'data' => 'array',
        'read_at' => 'immutable_datetime',
        'seen_at' => 'immutable_datetime',
    ];

    public function scopeForAdmin(Builder $query, Admin $admin): Builder
    {
        return $query->where('admin_id', $admin->id);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
```

Inbox query:

```php
public function paginate(Admin $admin, array $filters): LengthAwarePaginator
{
    return AdminNotification::query()
        ->forAdmin($admin)
        ->when($filters['state'] === 'unread', fn ($q) => $q->unread())
        ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
        ->when($filters['level'] ?? null, fn ($q, $level) => $q->where('level', $level))
        ->latest('created_at')
        ->paginate(20)
        ->withQueryString();
}
```

Select only required fields in the header query. Cap `per_page` and validate filters.

## 14. Routes and permissions

```php
Route::prefix('admin-notifications')->name('admin-notifications.')->group(function () {
    Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
    Route::get('/header', [AdminNotificationController::class, 'header'])->name('header');
    Route::get('/{notification:uid}', [AdminNotificationController::class, 'show'])->name('show');
    Route::put('/{notification:uid}/read', [AdminNotificationController::class, 'markRead'])->name('read');
    Route::put('/read-all', [AdminNotificationController::class, 'markAllRead'])->name('read-all');
    Route::delete('/{notification:uid}', [AdminNotificationController::class, 'destroy'])->name('destroy');
    Route::delete('/read', [AdminNotificationController::class, 'destroyRead'])->name('destroy-read');
});
```

Permissions:

```text
gopanel.admin-notifications.index
gopanel.admin-notifications.show
gopanel.admin-notifications.read
gopanel.admin-notifications.delete
gopanel.admin-notifications.manage-preferences
gopanel.admin-notifications.delivery-monitor
```

Ownership is mandatory in addition to permission. Use scoped route binding or a policy:

```php
public function resolveRouteBindingQuery($query, $value, $field = null)
{
    return $query
        ->where($field ?? 'uid', $value)
        ->where('admin_id', auth('gopanel')->id());
}
```

This avoids accidentally loading another admin's notification before a manual ownership check.

## 15. Controller and inbox service

```php
final class AdminNotificationController extends Controller
{
    public function index(
        AdminNotificationIndexRequest $request,
        AdminNotificationInboxQuery $query
    ): View {
        $admin = $request->user('gopanel');

        return view('gopanel.admin-notifications.index', [
            'notifications' => $query->paginate($admin, $request->validated()),
            'unreadCount' => $query->unreadCount($admin),
            'filters' => $request->validated(),
        ]);
    }

    public function markAllRead(
        Request $request,
        AdminNotificationInboxService $service
    ): JsonResponse {
        $count = $service->markAllRead($request->user('gopanel'));
        return response()->json(['status' => 'success', 'data' => ['count' => $count]]);
    }
}
```

Read/delete methods must always scope updates by current `admin_id`. Make mark-read idempotent. Bulk delete should delete read notifications only unless the UI explicitly offers another confirmed policy.

## 16. Header bell and dropdown

Blade shell:

```blade
<div id="admin-notification-widget"
     data-header-url="{{ route('gopanel.admin-notifications.header') }}"
     data-read-url-template="{{ route('gopanel.admin-notifications.read', '__UID__') }}">
    <button id="admin-notification-toggle" aria-expanded="false" aria-controls="admin-notification-list">
        <i class="bx bx-bell"></i>
        <span id="admin-notification-badge" class="d-none" aria-live="polite"></span>
    </button>

    <div id="admin-notification-list" aria-busy="false">
        @include('gopanel.admin-notifications.partials.header-loader')
    </div>
</div>
```

Header endpoint response:

```json
{
  "status": "success",
  "data": {
    "html": "...",
    "unread_count": 4,
    "has_more": true,
    "next_cursor": "..."
  }
}
```

Cursor pagination is preferable to page numbers for a live descending feed because new rows can shift pages. If simple pagination is retained, de-duplicate appended notification UIDs in JavaScript.

Load when the dropdown first opens, then either:

- poll unread/header state at a controlled interval with page-visibility checks; or
- subscribe to a private authenticated broadcast channel such as `private-admin.{id}`.

Never broadcast one admin's payload on a public/global channel.

## 17. Full inbox Blade

Use a normal paginated list; DataTable is not required for a personal inbox:

```blade
@forelse ($notifications as $notification)
    <article id="notification-{{ $notification->uid }}"
             class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}"
             data-uid="{{ $notification->uid }}">
        @include($notificationView->compactTemplate(), compact('notification'))
        <div class="notification-actions">...</div>
    </article>
@empty
    @include('gopanel.admin-notifications.partials.empty')
@endforelse

{{ $notifications->links() }}
```

Filters may include unread/read, type, level and date range. Preserve them with `withQueryString()`.

List item must show icon, title, short escaped message, type/level, relative + exact time, unread indicator and allowed actions. Avoid putting CSS `<style>` blocks inside repeatedly included partials.

## 18. Detail page and templates

Opening detail may mark the item read. Do this as an explicit service operation before rendering, but ownership/policy must already be resolved.

Detail layout:

- notification header and level/type;
- full escaped message;
- safe type-specific detail partial;
- related-object summary from payload snapshots;
- action button if still valid;
- created/read timestamps;
- delete action.

Templates must treat `data` as untrusted input:

```blade
<div class="detail-field">
    <span class="detail-label">User</span>
    <span class="detail-value">{{ data_get($notification->data, 'display_name', '—') }}</span>
</div>
```

Never use `{!! $notification->data[...] !!}`. Do not expose raw internal model class names, IDs, stack traces or complete JSON metadata unless a separately authorized diagnostics screen requires them.

## 19. JavaScript/AJAX module

Move header and inbox handlers into one scoped, versioned module:

```javascript
(() => {
    const widget = document.querySelector('#admin-notification-widget');
    const inbox = document.querySelector('#admin-notification-inbox');
    if (!widget && !inbox) return;

    const state = {
        headerLoaded: false,
        headerLoading: false,
        nextCursor: null,
        hasMore: true,
        controllers: new Map(),
    };

    async function request(url, options = {}) {
        const response = await fetch(url, {
            headers: { Accept: 'application/json', ...options.headers },
            ...options,
        });
        const payload = await response.json();
        if (!response.ok) throw { response, payload };
        return payload;
    }
})();
```

Required behavior:

- initial dropdown spinner;
- incremental “load more” spinner;
- request lock to prevent duplicate pages;
- de-duplicate by notification UID;
- unread badge updates immediately but is reconciled with server response;
- mark-read optimistic UI rolls back on error;
- mark-all button shows spinner and disables itself;
- delete requires confirmation, shows pending state and restores on failure;
- 401/419 redirects or requests authentication refresh according to panel conventions;
- 403/404/422/500 have distinct messages;
- no inline `onclick` attributes;
- avoid full `location.reload()` for mark-all/delete-read when local reconciliation is possible;
- abort requests when the widget/page is destroyed or a newer refresh supersedes them.

## 20. Loader, empty and error states

Required states:

- header initial loader;
- header load-more loader;
- header empty state;
- header request error with retry;
- inbox loading/empty/error states;
- per-item mark-read/delete spinner;
- mark-all/delete-read spinner;
- detail delete spinner;
- real-time connection degraded indicator only if broadcasting is used.

```css
.notification-item {
    border-bottom: 1px solid #eff2f7;
    display: flex;
    gap: .875rem;
    padding: 1rem;
    position: relative;
}

.notification-item.is-unread {
    background: #f4f7ff;
    border-inline-start: .25rem solid #556ee6;
}

.notification-icon {
    align-items: center;
    border-radius: 50%;
    display: inline-flex;
    flex: 0 0 2.5rem;
    height: 2.5rem;
    justify-content: center;
}

.notification-loading {
    align-items: center;
    display: flex;
    justify-content: center;
    min-height: 6rem;
}
```

Use `aria-live` for unread count, `aria-busy` during requests and visible text in addition to color/dots.

## 21. Security and privacy

- scope every query/mutation to the authenticated admin;
- enforce route permissions/policies;
- use private authenticated broadcast channels;
- validate/sanitize action URLs;
- re-authorize target pages;
- escape title, message and payload fields;
- whitelist trusted template paths in code;
- prohibit secrets, tokens, OTPs, passwords and unnecessary PII in payloads/logs;
- mask sensitive identifiers in email/SMS channels;
- rate-limit mutation and header polling endpoints;
- protect CSRF on mutations;
- use ULID/UUID in browser routes;
- record delivery failure details in redacted form;
- define retention and deletion rules.

## 22. Retention and cleanup

Define product policy, for example:

- unread notifications retained for 180 days;
- read notifications retained for 60 days;
- critical audit/security events retained elsewhere and not lost with inbox cleanup;
- delivery attempts retained for 30–90 days;
- scheduled cleanup command deletes in chunks.

Soft deletion is useful for user actions but is not a permanent retention solution. Provide a scheduled force-delete process if legally and operationally appropriate.

## 23. Observability

Track:

- notifications created per type;
- audience size;
- delivery success/failure per channel;
- retry and dead-letter counts;
- queue latency;
- header endpoint latency/error rate;
- duplicate prevented count;
- unread count distribution when relevant.

Use structured logs with `event_id`, notification UID, admin ID, type and channel. Never log complete sensitive payloads.

## 24. Minimum test matrix

Feature tests:

- unauthenticated users cannot access endpoints;
- admin sees only their own notifications;
- scoped binding returns 404/403 for another admin's UID;
- header response includes only owned items and correct unread count;
- inbox filters and pagination preserve query state;
- viewing detail marks it read idempotently;
- mark-all changes only current admin rows;
- delete and delete-read affect only current admin rows;
- unsafe action URLs are rejected;
- missing type uses default template safely;
- payload output is escaped;
- private broadcast authorization permits only matching admin;
- retention command deletes only eligible rows.

Queue/service tests:

- audience permission/role resolution is correct;
- inactive admins are excluded;
- same `event_id` retry does not duplicate inbox rows;
- domain rollback dispatches no notification;
- one failed channel does not remove database inbox record;
- retryable channel failure is retried with backoff;
- permanent failure is recorded;
- unsupported/mock channels cannot be enabled accidentally;
- large audiences are chunked into bounded jobs.

Browser/JavaScript tests:

- bell loads once on first open;
- load-more sends one request and does not duplicate items;
- unread badge reconciles after read/mark-all;
- failed optimistic mark-read restores UI;
- delete confirmation and rollback work;
- loaders, empty state, error and retry are visible/accessibly announced;
- real-time event for another admin is ignored/inaccessible.

## 25. Migration order for another Gopanel

1. Separate admin inbox notifications from user notifications, campaigns and scheduled sends.
2. Inspect admin guard, roles, permissions, verified contact fields and queue configuration.
3. Add inbox, delivery and optional preference tables with indexes/idempotency keys.
4. Add trusted type/level/channel enums and payload schemas.
5. Add audience resolver and after-commit dispatcher.
6. Implement database channel first and prove idempotent delivery.
7. Add bounded jobs, retry/backoff and failure recording.
8. Add scoped routes, policy/ownership and inbox query.
9. Build header bell/dropdown with cursor pagination and loaders.
10. Build normal paginated inbox and safe detail templates.
11. Add read/delete AJAX with optimistic rollback.
12. Add only genuinely configured external channels.
13. Add private broadcasting or controlled polling.
14. Add retention command, observability and complete tests.
15. Replace direct notification calls in domain services with after-commit dispatch commands.

## 26. Acceptance criteria

The module is complete only when:

- admin notifications are clearly separate from public-user communications;
- each inbox record belongs to exactly one admin and ownership is enforced everywhere;
- recipients are selected by explicit audience rules;
- retries cannot duplicate an event for the same admin;
- large audiences are chunked and no unlimited `Admin::each()` job exists;
- database inbox works even when an external channel fails;
- channel attempts have explicit pending/sent/failed states;
- mock/unsupported channels are disabled;
- header bell has unread badge, incremental loading, loader, empty and retry states;
- inbox is paginated and supports safe filters;
- type-specific templates use validated, escaped payloads;
- action URLs are safe and destination authorization is rechecked;
- read/delete operations are idempotent, scoped and protected by CSRF/permissions;
- queue, security, browser and retention tests pass;
- another AI model can implement the feature without knowing source-project domain names.

