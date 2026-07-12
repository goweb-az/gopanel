# Gopanel Translation Management: Migration Task and Architecture

> ✅ **DONE — 2026-07-11.** Implemented in this codebase: `page` column + composite unique index migration, `TranslationPageRegistry` (`config/gopanel/translation_pages.php`), page-aware runtime keys in `TranslationServiceProvider`, JSON/XLSX bulk import (`TranslationBulkImportService`, phpspreadsheet) and deterministic JSON export (`TranslationExportService`), `import`/`export` permissions + `can:` route middleware, page filter/column in the datatable, bulk-import modal + `translations.js`, cache invalidation service, removal of the dead `CustomTranslator`, and unit tests (`tests/Unit/TranslationModuleTest.php`). Task description kept below for reference.

## 1. Purpose

This document is an implementation task for an AI model or developer who must extend an existing Gopanel translation CRUD with the following capabilities:

- page-based translation organization and filtering;
- bulk translation import from JSON and XLSX;
- JSON export grouped by platform and page;
- database translations loaded into Laravel at runtime through a service provider;
- consistent Blade, JavaScript/AJAX, controller, service, validation, permissions, loader, error and test structure.

The target project is assumed to already have a basic translation section. Do not copy project-specific names or page lists blindly. Adapt namespaces, layouts, response helpers, permission package, DataTable implementation and supported locales to the target Gopanel.

This module manages interface text such as buttons, labels and validation messages. It is separate from polymorphic model-content translation systems such as `field_translations`.

## 2. Required outcome

The finished screen must let an authorized administrator:

1. list and search translations;
2. filter them by locale, platform and page;
3. create/edit one logical key for multiple locales;
4. bulk import flat key-value JSON or an XLSX file;
5. choose whether existing values are updated or skipped;
6. export translations into deterministic JSON files by platform/page;
7. see loading, success, validation-error and server-error states;
8. use newly saved database translations through Laravel's translator without restarting the application.

## 3. Canonical data contract

A translation is uniquely identified by:

```text
locale + platform + page + group + filename + key
```

Recommended table:

```php
Schema::create('translations', function (Blueprint $table) {
    $table->id();
    $table->string('locale', 10);
    $table->string('key', 191);
    $table->text('value')->nullable();
    $table->string('platform', 30)->default('website');
    $table->string('page', 100)->default('general');
    $table->string('group', 100)->default('general');
    $table->string('filename', 100)->default('website');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['platform', 'page', 'locale']);
    $table->unique(
        ['key', 'locale', 'platform', 'page', 'group', 'filename'],
        'translations_unique'
    );
});
```

Meaning of fields:

| Field | Meaning | Example |
|---|---|---|
| `locale` | language code | `az`, `en` |
| `platform` | consumer/application | `website`, `mobile`, `admin` |
| `page` | UI screen/domain | `web_auth_login` |
| `group` | semantic section on the page | `title`, `button`, `validation` |
| `filename` | Laravel language file namespace | `website` |
| `key` | stable developer-facing key | `submit` |
| `value` | translated text | `Daxil ol` |

Use stable machine keys. Labels may change, but `page`, `group` and `key` should not be generated from translated text.

### Runtime key format

Choose one key contract and use it in the provider, PHP files, JSON files, API output and frontend calls:

```text
filename.page.group.key
```

Laravel example:

```php
__('website.web_auth_login.button.submit')
```

If the target project has registered a real translation namespace, an equally valid contract is:

```php
__('website::web_auth_login.button.submit')
```

The important rule is consistency. The source implementation writes `page.group.key` to generated files, while its current service provider registers only `group.key`. The migrated implementation must include the page in the runtime key; otherwise identical groups/keys from different pages overwrite one another.

## 4. Page logic that must be added

Keep the page catalog in configuration, not in Blade or JavaScript:

```php
// config/gopanel/translation_pages.php
return [
    'website' => [
        'general'        => 'General',
        'web_home'       => 'Web - Home',
        'web_auth_login' => 'Web - Login',
    ],
    'mobile' => [
        'general'           => 'General',
        'mobile_home'       => 'Mobile - Home',
        'mobile_auth_login' => 'Mobile - Login',
    ],
    'admin' => [
        'general'            => 'General',
        'admin_dashboard'    => 'Admin - Dashboard',
        'admin_translations' => 'Admin - Translations',
    ],
];
```

Add a small helper such as `TranslationPageRegistry`:

```php
final class TranslationPageRegistry
{
    public function all(): array
    {
        return config('gopanel.translation_pages', []);
    }

    public function forPlatform(?string $platform): array
    {
        return $this->all()[$platform ?: 'website']
            ?? ['general' => 'General'];
    }

    public function exists(string $platform, string $page): bool
    {
        return array_key_exists($page, $this->forPlatform($platform));
    }
}
```

Required behavior:

- changing platform rebuilds the page select;
- an unknown platform falls back to `general`;
- requests must validate that the selected page belongs to the selected platform;
- list filters are preserved in the URL;
- bulk import and normal create/edit use the same page registry;
- export can target one platform/page or all configured pages.

Do not validate pages with only `string|max:100`; add an `after()` validation check or a custom rule for the platform/page pair.

## 5. Recommended file structure

```text
app/
  Http/Controllers/Gopanel/TranslationController.php
  Http/Requests/Gopanel/Translations/
    StoreTranslationRequest.php
    BulkTranslationImportRequest.php
    ExportTranslationsRequest.php
  Models/Translation.php
  Providers/DatabaseTranslationServiceProvider.php
  Queries/Gopanel/TranslationListQuery.php
  Services/Translations/
    TranslationBulkImportService.php
    TranslationExportService.php
    TranslationCacheService.php
  Support/Translations/TranslationPageRegistry.php
config/gopanel/translation_pages.php
resources/views/gopanel/translations/
  index.blade.php
  partials/form.blade.php
  modals/form.blade.php
  modals/bulk-import.blade.php
public/assets/gopanel/js/modules/translations.js
tests/Feature/Gopanel/Translations/
tests/Unit/Translations/
```

The controller coordinates HTTP only. Parsing/importing, exporting and cache invalidation belong to services. Filtering belongs to a query class or the project's DataTable query layer.

## 6. Routes and permissions

```php
Route::prefix('translations')->name('translations.')->group(function () {
    Route::get('/', [TranslationController::class, 'index'])->name('index');
    Route::get('/form/{translation?}', [TranslationController::class, 'form'])->name('form');
    Route::post('/save/{translation?}', [TranslationController::class, 'save'])->name('save');

    Route::post('/bulk-import', [TranslationController::class, 'bulkImport'])
        ->name('bulk-import');
    Route::get('/bulk-template/json', [TranslationController::class, 'jsonTemplate'])
        ->name('bulk-template.json');
    Route::get('/bulk-template/xlsx', [TranslationController::class, 'xlsxTemplate'])
        ->name('bulk-template.xlsx');

    Route::post('/export-json', [TranslationController::class, 'exportJson'])
        ->name('export-json');
});
```

Use separate abilities:

```text
gopanel.settings.translations.index
gopanel.settings.translations.create
gopanel.settings.translations.update
gopanel.settings.translations.delete
gopanel.settings.translations.bulk-import
gopanel.settings.translations.export
```

The source panel displays bulk import and export under the generic `add` permission. The target implementation should separate them because importing and writing export files have a larger operational impact.

Sidebar entry:

```php
[
    'icon'  => '<i class="bx bx-transfer-alt"></i>',
    'title' => 'Translations',
    'route' => 'gopanel.settings.translations.index',
    'can'   => 'gopanel.settings.translations.index',
],
```

## 7. Query/list layer

`TranslationListQuery` or the DataTable query must accept only whitelisted filters:

```php
public function handle(array $filters): Builder
{
    return Translation::query()
        ->when($filters['search'] ?? null, function (Builder $query, string $search) {
            $query->where(function (Builder $nested) use ($search) {
                $nested->where('key', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%");
            });
        })
        ->when($filters['locale'] ?? null,
            fn (Builder $q, string $v) => $q->where('locale', $v))
        ->when($filters['platform'] ?? null,
            fn (Builder $q, string $v) => $q->where('platform', $v))
        ->when($filters['page'] ?? null,
            fn (Builder $q, string $v) => $q->where('page', $v))
        ->orderBy('platform')
        ->orderBy('page')
        ->orderBy('group')
        ->orderBy('key')
        ->orderBy('locale');
}
```

Recommended columns: key, value, platform, page label, group, locale, missing-language indicator and actions. Avoid querying all languages from every model constructor and avoid one query per language per row. Load languages once and calculate coverage with an aggregate query.

## 8. Controller contract

```php
final class TranslationController extends Controller
{
    public function index(Request $request, TranslationPageRegistry $pages): View
    {
        return view('gopanel.translations.index', [
            'languages' => Language::enabled()->orderBy('sort')->get(),
            'platforms' => array_keys($pages->all()),
            'allPages'  => $pages->all(),
            'filters'   => $request->only('locale', 'platform', 'page'),
        ]);
    }

    public function bulkImport(
        BulkTranslationImportRequest $request,
        TranslationBulkImportService $service
    ): JsonResponse {
        $result = $service->import(
            $request->validated(),
            $request->file('file')
        );

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    public function exportJson(
        ExportTranslationsRequest $request,
        TranslationExportService $service
    ): JsonResponse {
        $result = $service->export($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => "{$result['file_count']} JSON file(s) generated.",
            'data'    => $result,
        ]);
    }
}
```

Inject services through the container. Do not instantiate them with `new` inside controller methods. Use transactions for logical multi-row saves and clear translation caches only after a successful commit.

## 9. Bulk import specification

### Accepted JSON

Only a flat object is accepted:

```json
{
  "page_title": "Create listing",
  "clear_button": "Clear",
  "submit_button": "Save"
}
```

Nested objects and arrays must be rejected with a clear message. JSON must be UTF-8 and have a configurable size limit, for example 5 MB.

### Accepted XLSX

The first row must contain:

```text
key | value
```

`.xls` should be rejected unless a real `.xls` reader is installed. File-extension and MIME validation alone is not enough; parsing errors must be caught. Prefer a maintained spreadsheet library in the target project. A manual ZIP/XML reader is acceptable only when dependency policy requires it and it has focused tests.

### Request fields

```text
import_type: json|xlsx
locale: one of enabled database languages
platform: configured platform
page: configured page belonging to platform
group: allowed group
mode: update|skip
file: required file
```

Do not hardcode locales such as `en,ar,tr,ru` in the FormRequest. Validate against enabled language records or a locale registry.

### Processing rules

1. Parse the file before starting database writes.
2. Normalize keys and values; trim surrounding whitespace without altering meaningful internal whitespace.
3. Reject or report empty keys; decide explicitly whether empty values are allowed.
4. Detect duplicate keys inside the uploaded file and report them.
5. Build the full unique identity, including page and locale.
6. In `update` mode, update existing records; in `skip` mode, keep them unchanged.
7. Create missing records.
8. Return deterministic counters.
9. Clear affected locale caches after commit.

Response contract:

```json
{
  "status": "success",
  "data": {
    "total_rows": 20,
    "created": 12,
    "updated": 5,
    "skipped": 2,
    "failed": 1,
    "errors": ["Row 8: key is empty"]
  }
}
```

For large files, avoid saving Eloquent models one by one because model events may regenerate entire language files for every row. Use chunked `upsert`, then regenerate affected outputs once per locale/filename after commit. If the import must be all-or-nothing, throw on any write failure and roll back; if partial success is desired, document that policy and do not imply that a transaction guarantees it while exceptions are swallowed inside the loop.

## 10. JSON export specification

The source behavior exports database rows into:

```text
database/seeders/json-data/translations/
  translations-website-general.json
  translations-website-web_home.json
  translations-mobile-mobile_home.json
```

Each export file may contain portable seeder rows:

```json
[
  {
    "key": "submit",
    "locale": "az",
    "value": "Yadda saxla",
    "platform": "website",
    "page": "web_auth_login",
    "group": "button",
    "filename": "website"
  }
]
```

Required export rules:

- optional platform/page filters must use the same page registry;
- rows with `null` values are excluded unless the product explicitly needs placeholders;
- order by locale, group and key before encoding so Git diffs remain stable;
- use `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR`;
- sanitize platform/page before using them in a filename;
- write to a temporary file and atomically rename it;
- remove or explicitly report stale files that no longer correspond to database rows;
- return file count, row count and generated relative paths;
- restrict the output directory to a configured application-owned path;
- log who performed the export.

If the target server is read-only or horizontally scaled, do not write deployable files from a web request. Instead generate a ZIP/download or dispatch a queue job and store the artifact. The button label and response must make it clear whether files were created on the server or downloaded to the administrator.

## 11. Model events and generated files

The source model regenerates both files after every save:

```text
resources/lang/{locale}/{filename}.php
resources/lang-json/{locale}/{filename}.json
```

PHP shape:

```php
return [
    'web_auth_login' => [
        'button' => [
            'submit' => 'Sign in',
        ],
    ],
];
```

JSON shape:

```json
{
  "web_auth_login.button.submit": "Sign in"
}
```

For a target implementation, prefer a dedicated `TranslationFileWriter` called once after a transaction instead of filesystem work in `saved`/`deleted` model events. Model events make bulk import expensive, hide side effects and can leave DB/files inconsistent. File output should be deterministic, locked during writes and atomically replaced.

Deleting one locale row must not accidentally recursively delete every locale through repeated model events. If “delete logical key in all languages” is required, expose it as an explicit service operation and perform one controlled query.

## 12. Translation Service Provider

Register the provider according to the Laravel version:

```php
// Laravel versions using config/app.php
App\Providers\DatabaseTranslationServiceProvider::class,
```

or in `bootstrap/providers.php` for newer Laravel applications.

Responsibilities:

1. exit safely during installation/migration when required tables do not exist;
2. obtain enabled locales from the language repository/cache;
3. load translations from cache or DB;
4. build the canonical page-aware keys;
5. add lines to Laravel's existing translator;
6. never fail the whole request because translation storage is temporarily unavailable.

Recommended implementation:

```php
final class DatabaseTranslationServiceProvider extends ServiceProvider
{
    public function boot(Translator $translator): void
    {
        if (!Schema::hasTable('languages') || !Schema::hasTable('translations')) {
            return;
        }

        try {
            foreach (Language::enabled()->pluck('code') as $locale) {
                $lines = Cache::remember(
                    "site_translations:v2:{$locale}",
                    now()->addDay(),
                    fn () => Translation::query()
                        ->where('locale', $locale)
                        ->where('platform', 'website')
                        ->whereNotNull('key')
                        ->whereNotNull('value')
                        ->get()
                        ->mapWithKeys(fn (Translation $row) => [
                            "{$row->filename}.{$row->page}.{$row->group}.{$row->key}"
                                => $row->value,
                        ])
                        ->all()
                );

                $translator->addLines($lines, $locale);
            }
        } catch (Throwable $exception) {
            Log::warning('Database translations could not be loaded.', [
                'exception' => $exception::class,
            ]);
        }
    }
}
```

If the chosen Laravel loader interprets namespaces/groups differently, adapt `addLines()` input to that framework version and prove it with a feature test using `__()`.

Do not clear translation caches on every request in debug mode. Provide an explicit invalidation method:

```php
final class TranslationCacheService
{
    public function forgetLocales(iterable $locales): void
    {
        foreach (array_unique([...$locales]) as $locale) {
            Cache::forget("site_translations:v2:{$locale}");
        }
    }
}
```

Call it after create, update, delete and bulk import commits. Include a cache-key version when changing key structure.

The source repository also contains a `CustomTranslator` that reads a different cache key (`translations_{locale}`), while the provider writes `site_translations_{locale}`. Do not carry both mechanisms into the target project unless they are deliberately unified. Prefer one provider and one cache contract.

## 13. Blade structure

`index.blade.php` should contain only page composition:

```blade
<div id="translation-page"
     data-pages='@json($allPages)'
     data-export-url="{{ route('gopanel.settings.translations.export-json') }}"
     data-import-url="{{ route('gopanel.settings.translations.bulk-import') }}">

    <header class="translation-toolbar">
        @can('gopanel.settings.translations.create')
            <button id="translation-create" class="btn btn-success">Add</button>
        @endcan
        @can('gopanel.settings.translations.bulk-import')
            <button id="translation-bulk-open" class="btn btn-primary">Bulk import</button>
        @endcan
        @can('gopanel.settings.translations.export')
            <button id="translation-export" class="btn btn-outline-secondary">
                <span class="button-label">Export JSON</span>
                <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
            </button>
        @endcan
    </header>

    @include('gopanel.translations.partials.filters')
    @include('gopanel.translations.partials.table')
    @include('gopanel.translations.modals.form')
    @include('gopanel.translations.modals.bulk-import')
</div>
```

The bulk modal must contain import type, locale, group, platform, page, mode, file input, format help, template links, result area and submit button. IDs must be unique and JavaScript selectors must be scoped under `#translation-page`.

All button visibility is permission-controlled in Blade, but routes must also enforce authorization server-side.

## 14. JavaScript and AJAX structure

Use a module/IIFE instead of unrelated global handlers:

```javascript
(() => {
    const root = document.querySelector('#translation-page');
    if (!root) return;

    const state = {
        pages: JSON.parse(root.dataset.pages || '{}'),
        importRequest: null,
        exportRequest: null,
    };

    function setButtonLoading(button, loading) {
        button.disabled = loading;
        button.querySelector('.button-label')?.classList.toggle('d-none', loading);
        button.querySelector('.spinner-border')?.classList.toggle('d-none', !loading);
        button.setAttribute('aria-busy', String(loading));
    }

    function pagesFor(platform) {
        return state.pages[platform] || { general: 'General' };
    }

    function rebuildPages(select, platform, selected = 'general') {
        select.replaceChildren();
        Object.entries(pagesFor(platform)).forEach(([value, label]) => {
            select.add(new Option(label, value, false, value === selected));
        });
    }
})();
```

AJAX rules:

- CSRF is sent through the standard meta/header setup;
- bulk upload uses `FormData`, `processData: false`, `contentType: false` when jQuery is used;
- disable only the active action button and show its spinner;
- abort a previous same-action request before starting another when appropriate;
- render 422 field errors next to fields and also provide an accessible summary;
- escape server-provided error text before inserting it into HTML;
- use `complete`/`finally` to restore buttons;
- reload the DataTable only after a successful import;
- preserve locale/platform/page filters in `history.replaceState`;
- do not append `?v={{ time() }}` in production; use the asset build/version manifest.

Bulk flow:

```javascript
async function submitBulkImport(form, button, resultBox) {
    setButtonLoading(button, true);
    resultBox.replaceChildren();

    try {
        const response = await fetch(root.dataset.importUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: new FormData(form),
        });
        const payload = await response.json();
        if (!response.ok) throw { response, payload };

        renderImportResult(resultBox, payload.data);
        reloadTranslationTable();
    } catch (error) {
        renderRequestError(resultBox, error);
    } finally {
        setButtonLoading(button, false);
    }
}
```

Export flow must send current platform/page filters, show a spinner, display the returned file count/path summary and always restore the button. If export returns a downloadable artifact, use a normal navigation/download response or fetch a Blob; do not claim a download occurred when files were only written on the server.

## 15. Loader and CSS requirements

The source screen already uses button spinners for export and bulk import. Preserve these states and add a modal form-loading state while the create/edit Blade partial is fetched.

```css
.translation-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: .5rem;
}

.translation-form-shell {
    position: relative;
    min-height: 10rem;
}

.translation-form-loader {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    background: rgb(255 255 255 / 75%);
    z-index: 2;
}

.translation-result {
    max-height: 16rem;
    overflow: auto;
}

@media (max-width: 767.98px) {
    .translation-toolbar > .btn {
        flex: 1 1 auto;
    }
}
```

Required UI states:

- initial/list DataTable processing indicator;
- create/edit modal form loader;
- bulk submit spinner and disabled button;
- export spinner and disabled button;
- success summary with counters;
- validation summary;
- fatal error message;
- empty table state.

Spinners need accessible text or `aria-busy`; color must not be the only status indicator.

## 16. Validation and security

- enforce route middleware and policies/permissions;
- validate enabled locale, configured platform and platform/page pair;
- whitelist groups or validate against a group registry;
- validate actual file contents and cap file size/row count;
- prevent path traversal in `filename`, platform and page;
- escape values rendered into HTML;
- use mass-assignment allowlists;
- rate-limit or queue expensive imports/exports if necessary;
- write audit logs for bulk import/export including admin, filters and counters;
- never return raw exception messages in production responses.

## 17. Minimum test matrix

Feature tests:

- unauthorized users cannot list/import/export;
- page filter returns only its page;
- page not belonging to platform returns 422;
- valid JSON creates rows with the full unique identity;
- update mode changes existing values;
- skip mode preserves existing values;
- nested/invalid JSON is rejected;
- XLSX without `key`/`value` headers is rejected;
- export creates deterministic platform/page files;
- Unicode is not escaped incorrectly;
- cache is invalidated after successful writes, not after rollback;
- `__('...')` resolves a DB translation through the provider;
- same `group.key` on two pages does not collide.

Unit tests:

- page registry lookup/fallback;
- JSON and XLSX normalization;
- duplicate and empty-row handling;
- export filename sanitization;
- canonical runtime key builder;
- cache-key generation.

## 18. Migration order for the other Gopanel

1. Inspect the existing translation table, model, route names, language source and translator usage.
2. Add `page`, `platform`, `group` and `filename` columns/defaults only if missing.
3. Backfill existing records with `general` and add the composite unique index after duplicate cleanup.
4. Add the page configuration and registry.
5. Add separate import/export permissions and sidebar/button authorization.
6. Add request validators.
7. Add query/list filters.
8. Add bulk import and export services.
9. Keep the controller thin and connect routes.
10. Add Blade partials/modals, CSS and all loader/error states.
11. Add the scoped JavaScript module and AJAX contracts.
12. Install/register one database translation provider and one cache convention.
13. Add cache invalidation after commits.
14. Run tests, import a sample file, export it, and compare round-trip values.

## 19. Acceptance criteria

The task is complete only when:

- page logic exists in DB, config, filters, forms, import, export and runtime keys;
- switching platform shows only that platform's pages;
- JSON and XLSX bulk import both work with update/skip behavior;
- response counters match actual database changes;
- JSON export is stable, Unicode-safe and page-separated;
- every async operation has loading, success and failure states;
- permissions are checked in both UI and backend;
- caches are refreshed after changes;
- runtime translation lookup includes the page and has no cross-page collision;
- another AI model can implement the module using this document without relying on the source project's listing, category or product domain.
