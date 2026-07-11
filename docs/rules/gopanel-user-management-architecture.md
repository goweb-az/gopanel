# Gopanel User Management: Reusable Architecture and Implementation Task

## 1. Purpose

This document tells an AI model or developer how to build or migrate a production-ready user-management section into another Laravel Gopanel.

It is intentionally domain-neutral. The target project may not have sellers, listings, tenders, companies, payments or devices. Implement only the optional tabs and filters supported by that project's database, while keeping the same architectural boundaries.

The module covers:

- server-side user list, search, filters and pagination;
- user profile/detail page;
- create/edit workflow;
- active/deactive and block/unblock workflows;
- optional incomplete registrations;
- lazy-loaded statistics, devices and large related datasets;
- Controller, FormRequest, Query, Service, Blade, JavaScript/AJAX and CSS structure;
- loaders, permissions, audit logging, privacy and tests.

## 2. Required result

An authorized administrator must be able to:

1. list users without loading the full table into memory;
2. search by safe, whitelisted identity fields;
3. filter by types and statuses available in the target project;
4. open a user detail page through a public identifier such as UUID/ULID;
5. edit approved profile fields;
6. activate/deactivate or block/unblock through explicit actions;
7. inspect paginated related records;
8. load expensive statistics and device/session information only when requested;
9. see loading, empty, validation-error and server-error states;
10. perform every sensitive action through a permission-protected and audited endpoint.

## 3. Domain rules must be decided first

Do not start with Blade. Define these concepts for the target project:

| Concept | Meaning | Recommended storage |
|---|---|---|
| Active | account can normally be used | `is_active` boolean |
| Blocked | access was administratively denied | `blocked_at`, `blocked_reason`, `blocked_by` |
| Verified | email/phone/identity was verified | separate timestamps/statuses |
| User type | application role/category | enum or lookup FK |
| Account type | individual/company/etc. | enum or lookup FK |
| Registration state | complete/incomplete/onboarding step | explicit state/step |

`is_active`, `blocked_at`, verification and deletion are not synonyms. Do not implement four UI controls that silently update the same boolean.

Recommended access rule:

```php
public function canAuthenticate(): bool
{
    return $this->is_active
        && $this->blocked_at === null
        && $this->deleted_at === null;
}
```

Block metadata should be retained:

```php
$table->timestamp('blocked_at')->nullable()->index();
$table->foreignId('blocked_by')->nullable()->constrained('admins')->nullOnDelete();
$table->string('blocked_reason', 500)->nullable();
```

Use a non-sequential public key in Gopanel URLs. Never expose sensitive authentication secrets, password hashes, reset tokens, OTPs or full access tokens.

## 4. Recommended file structure

```text
app/
  Http/Controllers/Gopanel/UserController.php
  Http/Requests/Gopanel/Users/
    UserIndexRequest.php
    StoreUserRequest.php
    UpdateUserRequest.php
    BlockUserRequest.php
    UserStatisticsRequest.php
  Models/User.php
  Policies/UserPolicy.php
  Queries/Gopanel/Users/
    UserListQuery.php
    UserDetailQuery.php
    UserStatisticsQuery.php
    UserDeviceQuery.php
  Services/Users/
    UserProfileService.php
    UserAccessService.php
    UserCreationService.php
  ViewModels/Gopanel/UserDetailViewModel.php
resources/views/gopanel/users/
  index.blade.php
  show.blade.php
  partials/filters.blade.php
  partials/table.blade.php
  partials/form.blade.php
  partials/detail-summary.blade.php
  partials/detail-table.blade.php
  modals/form.blade.php
  modals/block.blade.php
public/assets/gopanel/js/modules/
  users.js
  user-detail.js
tests/Feature/Gopanel/Users/
tests/Unit/Users/
```

Responsibilities:

- Controller: HTTP coordination and responses only;
- FormRequest: authorization, normalization and validation;
- Query: database reads, filters, pagination and aggregates;
- Service: state-changing business operations and transactions;
- Policy: per-user authorization;
- ViewModel/Resource: presentation-ready output;
- Blade: semantic markup only;
- JavaScript: filters, modal lifecycle, AJAX states and charts.

## 5. Routes

```php
Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/search', [UserController::class, 'search'])->name('search');
    Route::get('/create', [UserController::class, 'create'])->name('create');
    Route::post('/', [UserController::class, 'store'])->name('store');

    Route::get('/{user:uid}', [UserController::class, 'show'])->name('show');
    Route::get('/{user:uid}/edit', [UserController::class, 'edit'])->name('edit');
    Route::patch('/{user:uid}', [UserController::class, 'update'])->name('update');

    Route::post('/{user:uid}/block', [UserController::class, 'block'])->name('block');
    Route::delete('/{user:uid}/block', [UserController::class, 'unblock'])->name('unblock');
    Route::patch('/{user:uid}/activation', [UserController::class, 'activation'])->name('activation');

    Route::get('/{user:uid}/statistics', [UserController::class, 'statistics'])->name('statistics');
    Route::get('/{user:uid}/devices', [UserController::class, 'devices'])->name('devices');
    Route::get('/{user:uid}/relations/{section}', [UserController::class, 'relation'])->name('relations');
});
```

Place fixed paths such as `/search` and `/create` before `/{user}`. Use route-model binding instead of repeating `where('uid', $uid)->firstOrFail()`.

If admin-created accounts are not a real product requirement, do not show an inactive “Add” button. Either implement the complete store workflow or omit it.

## 6. Permissions and sidebar

Use granular permissions:

```text
gopanel.users.index
gopanel.users.show
gopanel.users.create
gopanel.users.update
gopanel.users.activate
gopanel.users.block
gopanel.users.unblock
gopanel.users.statistics
gopanel.users.devices
gopanel.users.sensitive-data
gopanel.users.incomplete-registrations
```

Sidebar example:

```php
[
    'icon'  => '<i class="bx bx-group"></i>',
    'title' => 'Users',
    'can'   => 'gopanel.users.index',
    'inner' => [
        [
            'icon'  => '<i class="bx bx-user"></i>',
            'title' => 'All users',
            'route' => 'gopanel.users.index',
            'can'   => 'gopanel.users.index',
        ],
        // Include only when incomplete registration data exists.
        [
            'icon'  => '<i class="bx bx-user-x"></i>',
            'title' => 'Incomplete registrations',
            'route' => 'gopanel.incomplete-users.index',
            'can'   => 'gopanel.users.incomplete-registrations',
        ],
    ],
],
```

Blade authorization is only presentation. Apply `authorize()`, policy calls or permission middleware to every backend endpoint. A user with list permission must not automatically receive device IPs, payment information or block permission.

## 7. User list and query structure

Recommended columns:

- avatar;
- full name and public customer/reference number;
- masked email/phone when the admin lacks sensitive-data permission;
- account/user type;
- verification, active and blocked badges;
- registration platform/date;
- actions.

Use server-side pagination/DataTables. The query accepts a validated filter DTO or array:

```php
final class UserListQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->select([
                'id', 'uid', 'name', 'surname', 'email', 'phone',
                'customer_number', 'account_type', 'user_type',
                'is_active', 'blocked_at', 'email_verified_at',
                'register_platform', 'created_at',
            ])
            ->when($filters['account_type'] ?? null,
                fn ($q, $value) => $q->where('account_type', $value))
            ->when($filters['user_type'] ?? null,
                fn ($q, $value) => $q->where('user_type', $value))
            ->when(array_key_exists('is_active', $filters),
                fn ($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['blocked'] ?? null, function ($q, $value) {
                $value === 'yes' ? $q->whereNotNull('blocked_at') : $q->whereNull('blocked_at');
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $escaped = addcslashes($search, '%_\\');
                $q->where(function ($nested) use ($escaped) {
                    $nested->where('name', 'like', "%{$escaped}%")
                        ->orWhere('surname', 'like', "%{$escaped}%")
                        ->orWhere('email', 'like', "%{$escaped}%")
                        ->orWhere('phone', 'like', "%{$escaped}%")
                        ->orWhere('customer_number', 'like', "%{$escaped}%");
                });
            })
            ->latest('id')
            ->paginate($filters['per_page'] ?? 25);
    }
}
```

Validate sort column/direction against a whitelist. Do not accept a request column and pass it directly into `orderBy`. Add indexes based on real filters. For a large table, consider prefix/full-text search or a dedicated search service rather than five `%term%` expressions.

Filter values must come from enums/lookups used by the backend; do not duplicate hardcoded values independently in Blade, Request and Query.

## 8. Controller structure

```php
final class UserController extends Controller
{
    public function index(UserIndexRequest $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('gopanel.users.index', [
            'filters'      => $request->validated(),
            'accountTypes' => AccountType::cases(),
            'userTypes'    => UserType::cases(),
        ]);
    }

    public function show(User $user, UserDetailQuery $query): View
    {
        $this->authorize('view', $user);

        return view('gopanel.users.show', [
            'user'   => $user,
            'detail' => $query->summary($user),
        ]);
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        UserProfileService $service
    ): JsonResponse {
        $service->update($user, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'User information was updated.',
        ]);
    }

    public function block(
        BlockUserRequest $request,
        User $user,
        UserAccessService $service
    ): JsonResponse {
        $service->block($user, $request->validated('reason'), $request->user('gopanel'));

        return response()->json(['status' => 'success']);
    }
}
```

Do not instantiate collaborators with `new UserSellerStatusSyncService()` inside controllers. Inject them into a service or constructor so transactions and tests can control the full workflow.

## 9. Create and edit rules

Use separate Store and Update requests. Never reuse public registration validation blindly in the admin panel.

Update example:

```php
public function rules(): array
{
    return [
        'name'         => ['nullable', 'string', 'max:100'],
        'surname'      => ['nullable', 'string', 'max:100'],
        'email'        => ['nullable', 'email:rfc', 'max:255', Rule::unique('users')->ignore($this->user)],
        'phone'        => ['nullable', 'string', 'max:30', Rule::unique('users')->ignore($this->user)],
        'account_type' => ['required', Rule::enum(AccountType::class)],
        'user_type'    => ['required', Rule::enum(UserType::class)],
        'gender'       => ['nullable', Rule::enum(Gender::class)],
    ];
}
```

Changing email/phone may require resetting verification timestamps or a separate verified-change flow. Changing seller/account type may affect companies, listings, permissions or onboarding. Put such synchronization in a transaction-backed domain service.

Admin-created users need an explicit password/invitation policy:

- generate an invitation and let the user set a password; or
- generate a temporary password and require reset.

Never email a permanent plain-text password and never return password hashes.

## 10. Access service: block, unblock and activation

```php
final class UserAccessService
{
    public function block(User $user, string $reason, Admin $admin): void
    {
        DB::transaction(function () use ($user, $reason, $admin) {
            $user->forceFill([
                'blocked_at'     => now(),
                'blocked_reason' => $reason,
                'blocked_by'     => $admin->id,
            ])->save();

            $user->tokens()->delete(); // adapt to Sanctum/JWT/session storage
            event(new UserBlocked($user, $admin, $reason));
        });
    }

    public function unblock(User $user, Admin $admin): void
    {
        // Clear current block state but keep an audit-history record.
    }
}
```

Requirements:

- block requires confirmation and normally a reason;
- endpoint is idempotent or returns a clear conflict when already blocked;
- active sessions/tokens are revoked according to the auth system;
- administrator, reason, timestamp and before/after state are audited;
- unblock does not erase historical audit data;
- the acting admin cannot block themselves through an unsafe generic endpoint;
- protected/system users need policy restrictions.

The list and detail page must use the same definition of blocked state.

## 11. Detail page architecture

The initial response should load only:

- profile summary;
- status/verification badges;
- small aggregate counters via `withCount`;
- a few cheap lookup relations;
- available tab metadata based on permissions and installed modules.

Do not eager-load every listing, tender, notification, payment, invoice, subscription, favorite and conversation into one request. That works with demo data but becomes an unbounded memory and response-time problem.

Recommended tabs:

```text
Overview | Activity | Related records | Statistics | Devices/Sessions | Audit history
```

Each large tab should call a paginated endpoint when first opened:

```http
GET /gopanel/users/{uid}/relations/listings?page=1
GET /gopanel/users/{uid}/relations/payments?page=1
```

Whitelist `section`; never convert arbitrary URL text into a relation name. Each section requires its own permission and presenter/resource. Return HTML partials or JSON consistently—do not mix contracts unpredictably.

Statistics should accept a validated date range with a maximum interval. Put database-specific date grouping behind the query layer; `DATE_FORMAT` is MySQL-specific.

Devices may expose IP addresses and user agents, so require a separate permission. Paginate them and omit tokens, fingerprints or secrets not needed by admins.

## 12. Blade structure

Index composition:

```blade
<div id="users-page"
     data-list-url="{{ route('gopanel.users.index') }}">
    @include('gopanel.users.partials.toolbar')
    @include('gopanel.users.partials.filters')

    <section class="card" aria-busy="false">
        @include('gopanel.users.partials.table')
    </section>

    @include('gopanel.users.modals.form')
</div>
```

Detail header:

```blade
<header class="user-detail-header">
    <div class="user-identity">...</div>
    <div class="user-actions">
        @can('update', $user)
            <button data-action="edit">Edit</button>
        @endcan
        @can('block', $user)
            <button data-action="block" data-url="...">Block</button>
        @endcan
    </div>
</header>
```

Avoid a 400-line `show.blade.php`. Split hero, overview, tabs, loader, empty and error states into partials/components. Move CSS and JavaScript to versioned assets.

Output escaped values with `{{ }}`. Only render prebuilt HTML from trusted presenters, and prefer Blade components for badges/actions instead of model accessors returning raw HTML.

## 13. JavaScript and AJAX

Use separate scoped modules for list and detail pages. Do not make one `users.js` infer whether it is controlling users or incomplete registrations from whichever table happens to exist.

List module responsibilities:

- initialize filters from URL;
- apply/reset filters;
- update DataTable AJAX URL without full reload;
- update browser URL with `history.replaceState`;
- open edit form with a modal-body loader;
- submit and render 422 errors;
- reload the current table page after success.

Detail module responsibilities:

- confirm and submit block/unblock;
- disable action and show spinner while pending;
- lazy-load each tab once;
- abort stale duplicate requests;
- render empty/error/retry states;
- create/destroy chart instances safely.

```javascript
function setLoading(button, loading) {
    button.disabled = loading;
    button.setAttribute('aria-busy', String(loading));
    button.querySelector('[data-label]')?.classList.toggle('d-none', loading);
    button.querySelector('[data-spinner]')?.classList.toggle('d-none', !loading);
}

async function loadTab(panel, url) {
    if (panel.dataset.loaded === 'true') return;

    panel.dataset.state = 'loading';
    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        renderTab(panel, await response.json());
        panel.dataset.loaded = 'true';
        panel.dataset.state = 'success';
    } catch (error) {
        panel.dataset.state = 'error';
        renderRetry(panel, error);
    }
}
```

Every AJAX action must:

- send CSRF for mutations;
- request JSON explicitly;
- distinguish 403, 404, 409, 422 and 500 responses;
- escape server messages before inserting them into HTML;
- restore buttons in `finally`/`complete`;
- prevent double submission;
- not use `?v={{ time() }}` in production—use build/version manifests.

## 14. Loaders, empty states and CSS

Required states:

- DataTable processing loader;
- edit modal skeleton/spinner while form HTML loads;
- save-button spinner;
- block/unblock button spinner;
- per-tab initial loader;
- per-tab empty state;
- per-tab error with retry;
- chart no-data state.

```css
.user-identity {
    align-items: center;
    display: flex;
    gap: 1rem;
    min-width: 0;
}

.user-avatar {
    aspect-ratio: 1;
    border-radius: 50%;
    height: 5.5rem;
    object-fit: cover;
    width: 5.5rem;
}

.user-tab-panel {
    min-height: 14rem;
    position: relative;
}

.user-panel-loader {
    align-items: center;
    display: flex;
    inset: 0;
    justify-content: center;
    min-height: 14rem;
}

.user-detail-table {
    overflow-x: auto;
}

@media (max-width: 767.98px) {
    .user-detail-header,
    .user-actions {
        align-items: stretch;
        display: flex;
        flex-direction: column;
    }
}
```

Use `aria-busy`, visible status text and focus management when a modal opens or an error occurs.

## 15. Search endpoint

For Select2/autocomplete consumers:

```json
{
  "results": [
    { "id": "01J...", "text": "Jane Doe (j***@example.com)" }
  ],
  "pagination": { "more": false }
}
```

Requirements:

- require at least 2–3 search characters;
- limit results and support pagination;
- authorize the consuming feature;
- return public UID instead of internal ID when possible;
- mask email/phone without sensitive-data permission;
- rate-limit the endpoint;
- select only needed columns.

## 16. Incomplete registrations

Treat incomplete registrations as optional and distinct from full users.

The screen may show provider, platform, current onboarding step, last activity and created date. It should support search/filter/detail and a controlled cleanup policy. Do not expose OTP codes or temporary auth payloads.

Deletion should be permission-protected, audited and preferably based on age/expiration rules. If the target project stores partial registrations in the main users table, implement it as a validated state filter rather than a second model solely to imitate the source project.

## 17. Security and privacy

- authorize every route and every target user;
- mask PII by default and reveal it only with permission;
- never show password/token/OTP fields;
- validate unique email and phone correctly on update;
- normalize email/phone before uniqueness checks;
- protect CSV/formula injection if exports are later added;
- use audit logs for profile changes and access-state changes;
- record before/after values but redact secrets;
- revoke sessions when security-sensitive identity/access state changes;
- add rate limits to search and mutations;
- prevent mass assignment of balance, score, verification or privilege fields;
- define retention rules for devices, IP addresses and incomplete registrations.

## 18. Performance rules

- use server-side pagination for users and all large relations;
- select only required columns;
- use `withCount` for counters;
- eager-load only bounded lookup relations;
- cache stable lookup enums, not live security state;
- cap statistics date range;
- add indexes after examining actual filters/query plans;
- avoid model accessors that execute queries per DataTable row;
- load statistics, devices and large tabs lazily;
- queue expensive exports or bulk operations if added later.

## 19. Minimum test matrix

Feature tests:

- list permission is required;
- filters and search return correct users;
- inactive filter correctly accepts both `0` and `1`;
- invalid sort/filter values return 422;
- detail permission is enforced;
- related tabs are paginated and independently authorized;
- edit validates unique normalized email/phone;
- forbidden fields cannot be mass-assigned;
- block requires reason and permission;
- block records actor/time/reason and revokes sessions;
- repeated block/unblock behavior is deterministic;
- a protected admin/system user cannot be blocked incorrectly;
- statistics rejects invalid or excessive date ranges;
- devices require their own permission;
- search endpoint limits and masks results;
- incomplete registration data never exposes OTP/secrets.

Unit tests:

- access-state rules;
- filter DTO normalization;
- badge/view-model mapping;
- profile synchronization service;
- statistics interval/axis generation;
- PII masking.

## 20. Migration order for another Gopanel

1. Inspect its user schema, authentication guards, public identifier and existing permissions.
2. Define active, blocked, verified and incomplete-registration semantics.
3. Add missing block metadata and indexes through migrations.
4. add granular permissions, policy and sidebar entries.
5. Add validated list filters and server-side query/pagination.
6. Build the index Blade, DataTable, URL filters and loader states.
7. Build a lightweight detail summary with aggregate counts.
8. Add paginated lazy endpoints for optional relations, statistics and devices.
9. Add update requests and transaction-backed profile service.
10. Add audited block/unblock/activation service and revoke sessions appropriately.
11. Split Blade, CSS and JavaScript into maintainable files.
12. Add privacy masking, tests and query/performance checks.
13. Only then add optional incomplete registrations or project-specific tabs.

## 21. Acceptance criteria

The module is complete only when:

- another AI can identify required and optional features without knowing the source business domain;
- list and all related datasets use bounded server-side pagination;
- URL filters, DataTable AJAX and backend filters share one contract;
- the detail page initially loads only summary data;
- expensive tabs show loaders and load lazily;
- create is fully implemented or absent—there is no dead button;
- update rules protect identity verification and dependent domain state;
- active and blocked states are separate and consistently enforced;
- block/unblock is confirmed, authorized, audited and session-aware;
- sensitive data has independent permissions and masking;
- Blade, JavaScript, Controller, Query and Service responsibilities are separated;
- all async actions handle success, empty, validation, forbidden and server-error states;
- security and performance tests pass with realistic data volume.

