# Laravel Application Layering: Support, Query, Traits, Helpers, Enums and Related Layers

> **Status: theory / decision reference.** This is the long-form reasoning behind
> the layer boundaries. The short, binding version for day-to-day work is
> [.claude/rules/01-umumi.md](../../.claude/rules/01-umumi.md) §1, and the
> catalogue of what already exists is [shared-layer.md](../shared-layer.md).
> Read this file when a placement decision is genuinely unclear — not before
> every change.
>
> Several recommendations here are now implemented in Gopanel: `app/Support/`
> exists with `Cache/`, `Date/`, `Url/`, `Export/`, `Gopanel/` subfolders;
> `app/Queries/{Gopanel,Site,Api}` and `app/DTOs` are scaffolded; cache-key
> construction lives in `Support/Cache/CacheKey` while cache storage stays in
> `Services/Cache/CacheService` (see §14 and migration step 9).

## 1. Purpose

This document is a reusable architecture guide for another AI model or developer working on a Laravel application. It explains what belongs in `Support`, `Queries`, `Traits`, `Helpers`, `Enums`, `Services`, `Repositories`, DTOs and Value Objects, how these layers differ, and how to decide where new code should be placed.

The source project currently contains:

- `app/Support/Url/CdnUrl.php`;
- many read-oriented classes under `app/Queries`;
- model behavior and presentation traits under `app/Traits`;
- Gopanel/common helpers under `app/Helpers`;
- backed enums under `app/Enums`;
- business workflows under `app/Services`;
- persistence classes under `app/Repositories`.

The goal is not to create a folder for every class. The goal is to make responsibility, dependency direction, testing and reuse obvious.

## 2. The main decision rule

Ask these questions in order:

1. Is it a fixed finite domain value? → `Enum`.
2. Is it an immutable typed piece of data with validation/behavior? → `Value Object`.
3. Is it data being transferred between layers? → `DTO`.
4. Is it only reading/aggregating data? → `Query`.
5. Is it saving/finding one aggregate through persistence abstraction? → `Repository` when abstraction is genuinely needed.
6. Is it a business workflow or state change? → `Service`/Action.
7. Is it reusable behavior mixed into a compatible class? → `Trait`, used sparingly.
8. Is it a small stateless transformation or lookup convenience? → narrowly named `Helper` or preferably a dedicated Support class.
9. Is it low-level reusable application/infrastructure policy that does not own a business workflow? → `Support`.

If a class matches several answers, split it. A class that queries the database, uploads files, renders HTML and sends email does not need a creative folder name; it needs separation.

## 3. Dependency direction

Recommended direction:

```text
HTTP/Console/Jobs
    ↓
Application Services / Actions
    ↓
Queries / Repositories / Domain Objects
    ↓
Models and Infrastructure Adapters

Cross-cutting, dependency-light:
Enums · DTOs · Value Objects · Support utilities
```

Avoid lower-level utilities depending on controllers, Blade or request globals. `Support` must not become a back door through which every layer depends on everything else.

# Part I — Support

## 4. What `app/Support` is for

`Support` contains small reusable building blocks that support multiple modules but are not business use cases themselves.

Good candidates:

- URL/path normalization and generation;
- string/phone/locale normalization;
- money/date formatting primitives when not domain Value Objects;
- pagination/cursor utilities;
- safe file-name/path utilities;
- cache-key builders;
- result/error primitives;
- reusable validators/rules;
- clocks, ID generators and system abstractions;
- small framework adapters used across modules.

Recommended subfolders:

```text
app/Support/
  Cache/
  Clock/
  Collections/
  Files/
  Identifiers/
  Localization/
  Pagination/
  Security/
  Strings/
  Url/
```

Do not put these in Support:

- `CreateOrder`, `ApproveListing`, `RegisterUser` workflows;
- Eloquent queries for one screen;
- controllers or request validation flows;
- Blade HTML builders;
- a miscellaneous `Utils.php` with unrelated methods;
- mutable global state;
- module-specific business decisions disguised as generic utilities.

## 5. Why `CdnUrl` belongs in Support

`CdnUrl` is not a business workflow. It implements a cross-cutting URL policy used by model file access, mail logos and the file uploader:

- choose `CDN_URL` or application URL as a base;
- recognize already absolute URLs;
- distinguish public assets from stored files;
- normalize slashes;
- produce a final URL consistently.

That makes `app/Support/Url/CdnUrl.php` a reasonable location. The `Url` subnamespace communicates exactly what it supports.

It should not be in:

- `Queries`, because it does not read application data;
- `Services`, because it does not coordinate a use case or state change;
- `Helpers/Gopanel`, because mail, models and non-Gopanel code use it;
- an enum, because a URL is not a finite fixed choice;
- a trait, because URL generation does not require mixed-in class state.

## 6. Current `CdnUrl` behavior and caveats

The source implementation:

```php
final class CdnUrl
{
    public static function base(): string;
    public static function isAbsolute(?string $path): bool;
    public static function storage(?string $path): ?string;
    public static function asset(?string $path): ?string;
    public static function url(?string $path): ?string;
}
```

Current routing policy:

```text
empty value            → null
http/https URL         → unchanged
assets/...             → CDN/application asset URL
storage/...            → CDN/application storage URL
other relative path    → public storage disk URL
```

Potential issues to handle in a target implementation:

- `Storage::disk('public')->url()` follows disk configuration and may not use `app.cdn_url` unless the disk URL is configured accordingly;
- absolute detection currently accepts only `http`/`https`, not protocol-relative URLs; rejecting protocol-relative URLs may be intentional;
- query strings/fragments and encoded paths need tests;
- `../` path traversal-like segments should be normalized or rejected;
- `storage/` prefix conventions must be documented;
- using `file_exists(public_path(...))` in a model trait mixes local filesystem assumptions with URL policy and fails on remote disks/CDNs;
- signed/private storage URLs require a different API from public CDN URLs.

## 7. Recommended CDN URL design

For a small project, keep a final stateless Support class:

```php
namespace App\Support\Url;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

final readonly class PublicUrlGenerator
{
    public function __construct(
        private FilesystemFactory $filesystems,
        private string $assetBaseUrl,
    ) {}

    public function asset(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if ($this->isHttpUrl($path)) {
            return $path;
        }

        return rtrim($this->assetBaseUrl, '/') . '/' . $this->normalizeRelative($path);
    }

    public function storage(?string $path, string $disk = 'public'): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if ($this->isHttpUrl($path)) {
            return $path;
        }

        return $this->filesystems->disk($disk)->url($this->normalizeRelative($path));
    }

    private function normalizeRelative(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if (str_contains($path, '../')) {
            throw new InvalidArgumentException('Unsafe relative path.');
        }

        return $path;
    }
}
```

Bind configuration in a service provider instead of reading global config in every method. Static methods are acceptable for a truly tiny pure utility, but dependency injection is easier to test and extend.

Use separate methods/classes for:

- public assets;
- public storage files;
- temporary signed private files;
- external URLs.

Do not guess private/public behavior from a filename.

## 8. Support class quality rules

A Support class should normally be:

- `final` unless extension is explicitly designed;
- stateless or immutable;
- narrowly named;
- deterministic for the same input/configuration;
- independent of HTTP request and authenticated user when possible;
- free of direct HTML rendering;
- covered by focused unit tests;
- small enough that its public contract is obvious.

# Part II — Query layer

## 9. What belongs in `app/Queries`

Query classes own read behavior:

- filtering;
- sorting;
- eager loading;
- selecting required columns;
- pagination;
- aggregates/counts/statistics;
- read-side cache composition;
- mapping to read DTOs/resources when appropriate.

Examples from the source project include menu retrieval, dashboard statistics, user detail data and API-specific catalog queries.

Queries should not normally:

- save/update/delete models;
- dispatch events/jobs;
- upload files;
- send notifications;
- depend on Blade;
- read directly from `request()`;
- silently authorize access;
- contain controller response construction.

Authorization belongs before calling the query, while tenant/user scope may be passed explicitly into it.

## 10. Query organization

Organize by consumer and domain only when behavior truly differs:

```text
app/Queries/
  Gopanel/
    DashboardQuery.php
    Users/UserListQuery.php
  Web/
    Menu/MenuQuery.php
    Catalog/ProductQuery.php
  Mobile/
    Menu/MenuQuery.php
    Catalog/ProductQuery.php
  Shared/
    ActiveLanguagesQuery.php
```

Do not duplicate Web/Mobile query classes when the SQL is identical. Use one shared query and separate Resources/presenters. Split only when filters, relations, cache keys or output requirements differ materially.

## 11. Query input

Do not access request globals inside the query:

```php
// Avoid
if (request('status')) {
    $query->where('status', request('status'));
}
```

Pass a typed filter DTO:

```php
final readonly class UserListFilters
{
    public function __construct(
        public ?string $search,
        public ?UserType $type,
        public ?bool $active,
        public string $sort = 'newest',
        public int $perPage = 25,
    ) {}
}
```

Then:

```php
final class UserListQuery
{
    public function paginate(UserListFilters $filters): LengthAwarePaginator
    {
        return User::query()
            ->select(['id', 'uid', 'name', 'email', 'user_type', 'is_active', 'created_at'])
            ->when($filters->type, fn ($q, UserType $type) => $q->where('user_type', $type->value))
            ->when($filters->active !== null, fn ($q) => $q->where('is_active', $filters->active))
            ->when($filters->search, fn ($q, string $search) => $this->search($q, $search))
            ->latest('id')
            ->paginate($filters->perPage);
    }
}
```

The FormRequest creates/validates the DTO. Query classes should not reimplement HTTP validation.

## 12. Query output

Choose output based on use:

- `Builder` for further composition;
- `Collection` for a bounded complete list;
- `Paginator` for large lists;
- scalar for counts/existence;
- typed read DTO for complex aggregate results.

Avoid returning an unstructured array with dozens of undocumented keys. Dashboard/chart responses benefit from DTOs or at least explicit array-shape PHPDoc.

## 13. Static versus instance queries

Static query methods are acceptable for very small parameterless reads, but instance queries are preferable when they need:

- injected cache or clock;
- filters/value objects;
- database portability strategy;
- mocking/fakes in tests;
- multiple related methods sharing state such as date ranges.

The source `DashboardQuery` correctly holds a date range in its constructor, but its SQL grouping uses MySQL-specific `DATE_FORMAT`. A portable query layer should isolate driver-specific expressions or aggregate in a database-compatible way.

## 14. Query caching

Cache can be coordinated in a query when it is read-specific, but cache-key generation and invalidation policy should live in dedicated Support/Cache classes.

Good:

```php
return $cache->remember(
    MenuCacheKey::for($platform, $position, $locale),
    fn () => $this->uncachedMenu($filters)
);
```

Rules:

- include every output-affecting filter, locale, tenant and permission scope;
- normalize/sort filter arrays before hashing;
- use targeted invalidation/versioning;
- do not cache live authorization/security state carelessly;
- do not use `Cache::flush()` for one module change;
- test key collisions and invalidation.

## 15. Query performance checklist

- select only required columns;
- eager-load bounded relations;
- use `withCount`/aggregates instead of loading all children;
- paginate unbounded datasets;
- whitelist sort columns;
- index real filters and foreign keys;
- avoid N+1 accessors/traits;
- cap date ranges;
- inspect query plans for hot endpoints;
- avoid unlimited recursive trees;
- do not call the same query repeatedly from Blade loops.

# Part III — Traits

## 16. What a Trait is for

A trait is code reuse for classes that share the same behavior and assumptions. Good examples:

- generating a public identifier on model creation;
- adding a known relationship and its small helper methods;
- reusable Eloquent scope/boot behavior;
- standardized audit hooks;
- a small presentation concern used by a defined model family.

A trait is not an architectural layer and should not be used to hide arbitrary dependencies.

## 17. Trait rules

Every trait should document:

- which class type may use it;
- required properties/columns/relations;
- methods it adds/overrides;
- model events/global scopes it registers;
- external dependencies;
- conflicts/composition concerns.

Example:

```php
/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * Requires a unique nullable `uid` column.
 */
trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function (Model $model): void {
            $model->uid ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }
}
```

Use Laravel's `bootTraitName`/`initializeTraitName` conventions. Prefer `protected` helper methods. Avoid generic method names likely to collide.

## 18. Trait warning signs

Refactor a trait when it:

- overrides foundational methods such as `getAttribute`, `__get` or `toArray` without strict tests;
- performs database queries on property access;
- returns raw HTML;
- depends on undeclared model properties;
- registers broad global scopes on every query;
- contains more business workflow logic than the model itself;
- injects state that cannot be constructed/tested normally;
- causes unexpected file/cache/network side effects during model events.

The source `Translation` and `HasFiles` traits are powerful but high-risk because they override Eloquent attribute behavior. For a new project, prefer explicit accessors/casts/relations:

```php
protected function imageUrl(): Attribute
{
    return Attribute::get(fn () => app(PublicUrlGenerator::class)->storage($this->image));
}
```

Also keep HTML out of model traits. A `_view` accessor returning `<a><img>` belongs in a Blade component/ViewModel.

## 19. Trait categories

Recommended structure:

```text
app/Traits/
  Eloquent/
    HasPublicId.php
    HasSortOrder.php
    HasTranslations.php
  Auditing/
    LogsActivity.php
  Presentation/        # use carefully; prefer ViewModels/components
```

Avoid `CommonTrait`, `BaseTrait` or a trait used only once without a strong semantic reason.

# Part IV — Helpers

## 20. What Helpers are for

Helpers are small convenience functions/classes for simple transformations or framework glue. Good helper examples:

- map a configured translation page key to a label;
- format a controlled icon list;
- measure elapsed time;
- normalize a simple string;
- provide a tiny view-facing mapping.

Helpers should be:

- narrow;
- stateless or immutable;
- easy to unit test;
- free of business transactions;
- not a dumping ground.

## 21. Helper versus Support

The distinction is convention, not a framework rule:

- `Support`: reusable technical primitive/policy with a clear domain such as URLs, cache keys, paths or clocks;
- `Helper`: small convenience near a consumer, often presentation/config mapping.

Examples:

```text
CdnUrl/PublicUrlGenerator     → Support/Url
CacheKey builder              → Support/Cache (better than Services/Cache)
TranslationPage label lookup  → Helper or Support/Localization registry
Gopanel sidebar rendering     → Gopanel Helper/View component
File upload workflow          → Service, not a giant Helper
CRUD save/update workflow     → Service/Action, not CrudHelper
```

If a Helper uses transactions, storage, multiple models, event dispatch or authorization, it has probably become a Service.

## 22. Global helper functions

Use global functions only for universally clear, collision-safe conveniences. Prefer namespaced classes or Laravel macros/components.

Avoid:

```php
function data($value) { ... }
function url2($path) { ... }
```

Global helpers are hard to discover, override and test. If used, load one focused file through Composer and add tests/PHPDoc.

## 23. Helper anti-patterns

- arbitrary model class names from request data;
- returning raw HTML strings;
- swallowing exceptions and returning false/null without a result contract;
- calling `request()`, `auth()` and `session()` deep inside generic helpers;
- a 500-line File/Crud/General helper;
- hidden writes in methods that look like formatters;
- static state across requests/workers.

# Part V — Enums

## 24. What belongs in Enums

Use PHP backed enums for a small, closed set of stable values controlled by code:

- status/state machine values;
- channel names;
- content/menu types;
- platform identifiers;
- registration steps;
- payment categories;
- roles only if truly code-fixed;
- sort modes;
- severity/level values.

Example:

```php
enum MenuType: string
{
    case ROUTE = 'route';
    case INTERNAL_URL = 'internal_url';
    case EXTERNAL_URL = 'external_url';
    case HEADING = 'heading';
    case SEPARATOR = 'separator';

    public function labelKey(): string
    {
        return match ($this) {
            self::ROUTE => 'admin.menu.types.route',
            self::INTERNAL_URL => 'admin.menu.types.internal_url',
            self::EXTERNAL_URL => 'admin.menu.types.external_url',
            self::HEADING => 'admin.menu.types.heading',
            self::SEPARATOR => 'admin.menu.types.separator',
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
```

Use consistent uppercase case names. Backed values are database/API contracts and should be stable, lowercase snake case.

## 25. What must not be an Enum

Do not use enums for administrator-managed or frequently changing data:

- countries, currencies or languages;
- categories/brands;
- configurable listing statuses/types when admins can CRUD/sort them;
- permission records;
- database-driven plans;
- hundreds of external provider IDs;
- translated marketing labels.

Use a lookup table/model when values require:

- admin CRUD;
- sorting;
- activation/deactivation;
- translations;
- metadata/icons/colors editable at runtime;
- relationships/foreign keys;
- deployment-independent changes.

Rule:

```text
Code controls lifecycle → Enum
Administrator/data controls lifecycle → Lookup table
```

## 26. Enum responsibilities

Good enum methods:

- `labelKey()`;
- small semantic checks like `isTerminal()`;
- allowed transition definitions;
- presentation token such as `colorToken()` when centrally standardized;
- `values()`;
- mapping to strategy/service key.

Avoid putting these in enums:

- database queries;
- service-container access;
- sending notifications;
- rendering Blade/HTML;
- request/auth logic;
- mutable configuration.

Prefer translation keys over calling `__()` directly when domain code should remain presentation-independent:

```php
__('enums.' . $status->labelKey())
```

## 27. Enum casting and validation

Model:

```php
protected $casts = [
    'type' => MenuType::class,
    'status' => OrderStatus::class,
];
```

Request:

```php
'type' => ['required', Rule::enum(MenuType::class)],
```

Database:

- usually store as `string`, not database-native ENUM, for portable migrations;
- size the column for stable backed values;
- add an index when filtered frequently;
- plan safe rollout: deploy code accepting a new value before writing it.

# Part VI — DTOs and Value Objects

## 28. DTOs

A DTO transfers validated, typed data between boundaries without owning persistence:

```php
final readonly class DateRangeData
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public string $timezone,
    ) {}
}
```

Use DTOs for:

- validated filter input;
- service commands;
- API/client payloads;
- complex query output;
- queue-safe scalar event data.

DTOs should not call Eloquent `save()`, read request globals or contain service workflows.

## 29. Value Objects

A Value Object represents a meaningful immutable value and protects its invariants:

```php
final readonly class CdnBaseUrl
{
    public string $value;

    public function __construct(string $value)
    {
        $url = rtrim($value, '/');
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid CDN base URL.');
        }
        $this->value = $url;
    }
}
```

Typical Value Objects:

- Money;
- EmailAddress;
- PhoneNumber;
- DateRange;
- LocaleCode;
- public/storage path;
- coordinates;
- percentage.

Identity is determined by value, not database ID. Make them immutable and validate construction.

# Part VII — Services, Actions and Repositories

## 30. Services/Actions

Services coordinate business behavior and state changes:

- transactions;
- multiple repositories/models;
- domain validation beyond FormRequest;
- file/storage operations;
- event/job dispatch after commit;
- cache invalidation;
- external API adapters.

```php
final class ApproveListing
{
    public function handle(Listing $listing, Admin $admin): Listing
    {
        return DB::transaction(function () use ($listing, $admin) {
            $listing->approveBy($admin);
            $listing->save();
            DB::afterCommit(fn () => event(new ListingApproved($listing->uid)));
            return $listing->fresh();
        });
    }
}
```

Use verb/use-case names for Actions and domain capability names for broader Services. Avoid `CommonService`/`GeneralService` growth.

## 31. Repositories

Laravel Eloquent already implements persistence patterns. Add a Repository when it provides real value:

- aggregate persistence spans several models;
- storage implementation might change;
- write logic is repeated and complex;
- external persistence needs an interface;
- a domain layer should not depend on Eloquent.

Do not create a repository that merely wraps every Eloquent one-liner:

```php
public function find(int $id) { return User::find($id); }
```

That adds indirection without abstraction. Simple reads belong in Queries or directly in a narrowly scoped controller/service when trivial.

Repositories should primarily express persistence operations, not UI searches, HTML or request access. Sensitive token fields must be encrypted or stored according to security policy; a repository is not itself a security boundary.

## 32. Service versus Repository versus Query

```text
Get filtered users page     → Query
Find aggregate for update   → Repository or route binding
Update user and sync roles  → Service/Action
Create CDN URL              → Support
Display status options      → Enum/lookup + Blade
Format a label              → presentation/helper
```

# Part VIII — Other commonly forgotten layers

## 33. Policies and FormRequests

Policies answer “may this actor perform this action on this resource?”. FormRequests validate/normalize HTTP input and call authorization.

They do not replace domain invariants in Services/Models. A service may also be called from jobs/console where FormRequest does not exist.

## 34. Events, Listeners and Jobs

- Event: immutable fact that occurred;
- Listener: reaction to that fact;
- Job: unit of queued work with retry/idempotency;
- Service/Action: performs the primary use case.

Dispatch external side effects after transaction commit. Jobs should carry stable scalar IDs/DTO data, not huge model graphs.

## 35. Resources, ViewModels and Blade components

- API Resource: transforms models/read DTOs to API contract;
- ViewModel/Presenter: prepares data for Blade;
- Blade component: renders reusable HTML;
- Model: should not return buttons, badges and `<img>` HTML.

Presentation traits in the source project can be migrated gradually to ViewModels/components, especially when they contain authorization or raw HTML.

## 36. Casts and custom validation rules

Use custom casts for field-level serialization/value conversion. Use Validation Rules for reusable input constraints.

Examples:

```text
MoneyCast
LocaleCodeCast
SafeRelativePath rule
ValidPlatformPage rule
NoMenuCycle rule
```

Do not use a trait when a custom cast models one attribute more accurately.

## 37. Contracts and infrastructure adapters

External systems need interfaces/adapters:

```text
Contracts/SmsSender.php
Infrastructure/Sms/TwilioSmsSender.php
Infrastructure/Storage/S3PublicUrlGenerator.php
```

This is more testable than static helpers that instantiate SDK clients internally.

# Part IX — Naming and folder rules

## 38. Naming

Use names that state responsibility:

```text
Good                              Avoid
PublicUrlGenerator                Utils
UserListQuery                     UserData
ApproveListing                    ListingManager
TranslationPageRegistry          TranslationHelper (if it grows)
HasPublicId                       CommonTrait
MenuType                          MenuEnumEnum
DateRangeData                     DataDtoObject
```

Do not suffix everything with `Service`. A query is a Query; a registry is a Registry; a formatter is a Formatter.

## 39. Namespace by domain versus technical type

Small/modular Laravel project:

```text
app/Queries/User/...
app/Services/User/...
app/Enums/User/...
```

Larger domain-oriented project:

```text
app/Domain/User/Enums/...
app/Application/User/Commands/...
app/Application/User/Queries/...
app/Infrastructure/...
```

Choose one coherent convention. Do not partially adopt “clean architecture” vocabulary while all dependencies still point arbitrarily in both directions.

# Part X — Practical classification examples

## 40. Where should this code go?

| Requirement | Recommended location |
|---|---|
| Build CDN asset URL | `Support/Url/PublicUrlGenerator` |
| Generate signed private download | storage adapter/service |
| List active translated menus | `Queries/.../MenuQuery` |
| Approve a menu change and invalidate cache | Service/Action |
| Reorder menu nodes | Service with transaction |
| Menu type values | Enum if code-fixed, lookup if admin-managed |
| Add ULID on model creation | Eloquent Trait |
| Render an image link | Blade component |
| Translate a model field | relation + accessor/service; Trait only with strict contract |
| Normalize locale code | Value Object or Support/Localization |
| Carry dashboard date range | DTO/Value Object |
| Calculate dashboard aggregates | Query |
| Save user aggregate | Repository if nontrivial |
| Send email/SMS | infrastructure adapter called by Service/Job |
| Build cache key | Support/Cache |
| Authorize edit | Policy/FormRequest |

## 41. Quick placement checklist

Before creating a class, answer:

- Does it read or write?
- Is it domain-specific or cross-cutting?
- Does it need Eloquent?
- Does it need the current request/user?
- Is its value set fixed or administrator-managed?
- Does it have side effects?
- Is it presentation logic?
- Does it need a transaction?
- Can it be unit tested without booting Laravel?
- Could its name explain its responsibility without opening the file?

If the answers are unclear, the responsibility is probably too broad.

# Part XI — Testing requirements

## 42. Support tests

For CDN/public URLs test:

- null/blank;
- base URL trailing slash;
- asset path with leading slash;
- public storage path;
- existing HTTP/HTTPS URL;
- unsafe relative path;
- Unicode/space encoding policy;
- query string/fragment behavior;
- configured local/CDN/S3 disk URL;
- private file is not exposed as public.

## 43. Query tests

- every filter;
- `false`/`0` values are not mistaken for missing;
- sort whitelist;
- pagination bounds;
- tenant/user scope;
- eager-load/query-count expectations;
- cache key includes locale/filters;
- cache invalidation;
- database portability where required.

## 44. Trait tests

Create a minimal test model proving:

- required column/property behavior;
- boot event runs once;
- no method collision;
- serialization/accessor behavior;
- no unexpected queries;
- delete/restore behavior;
- composition with other used traits.

## 45. Enum, DTO and Value Object tests

- stable backed values;
- every case has label/transition mapping;
- invalid values fail validation/casting;
- serialization round trip;
- Value Object invariants and equality;
- DTO construction from validated input.

# Part XII — Migration plan for this project style

## 46. Recommended refactoring order

1. Keep `CdnUrl` under `Support/Url`; document asset versus storage policy.
2. Add focused tests before changing its behavior.
3. Decide whether to retain static API or inject `PublicUrlGenerator`.
4. Configure the public disk URL consistently with `CDN_URL`.
5. Remove local `file_exists()` guessing from model file URL access.
6. Move raw HTML from `HasFiles` and presentation traits to Blade/ViewModels.
7. Add typed filter DTOs for complex Queries.
8. Remove `request()` access from Query/Data access classes.
9. Move cache-key construction from `Services/Cache` to `Support/Cache` if following this convention; keep cache storage/invalidation orchestration as a Service.
10. Audit Helpers: promote workflows such as file upload/CRUD orchestration into Services/Actions.
11. Audit Enums: convert admin-managed sets into lookup tables and keep only code-owned finite sets.
12. Document every Trait contract and replace foundational Eloquent overrides where practical.
13. Introduce Value Objects for locale, date range, money and paths where invariants matter.
14. Remove repositories that only wrap one-line Eloquent calls unless an interface boundary is required.
15. Add architecture tests/static analysis rules for forbidden dependencies and layer conventions.

## 47. Acceptance criteria

The architecture is clear only when:

- `Support` contains focused cross-cutting primitives, not business workflows;
- `CdnUrl`/URL generation has one documented policy for assets, public storage and private files;
- Queries perform reads only and accept explicit typed input;
- unbounded query results are paginated or explicitly bounded;
- Services/Actions own workflows, transactions and state changes;
- Repositories exist only where they provide a real persistence boundary;
- Traits declare requirements and do not hide uncontrolled queries/HTML/side effects;
- Helpers remain small and are promoted when they become workflows;
- Enums contain only code-owned closed sets with stable backed values;
- admin-managed values use lookup tables;
- DTOs and Value Objects are used for typed transfer and invariants;
- Policies, FormRequests, Jobs, Events, Resources and ViewModels have explicit roles;
- class names reveal responsibility;
- another AI model can place new code correctly without treating `Support`, `Helpers` or `Services` as miscellaneous folders.
