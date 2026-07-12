# Gopanel Languages and Menus: Reusable Management Architecture

> ✅ **DONE — 2026-07-11.** Implemented in this codebase (adapted to existing conventions — Languages/Menus already used plain Blade tables, so the work was replacing the generic `general.sortable`/model-class endpoints with dedicated, validated ones and building a real menu tree): **Languages** — dedicated `languages.sort` endpoint (`SortLanguagesRequest` + `LanguageSortService`, transactional + row-locked), drag-handle Blade table wired to it via `languages.js`, `languages.sort` permission. **Menus** — real drag-and-drop tree (`menu/index` + `partials/node`, `menus.js` connected sortables with in-flight guard + DOM rollback) replacing the drill-down table, `MenuTreeService` with a transactional cross-parent `move` (self-parent/descendant-cycle/max-depth validation via `MoveMenuRequest`), `childrenAdmin` relation (shows inactive items), per-parent+position `sort_order` scoping fix, `NavigationCacheService` targeted cache invalidation, `menu.move`/`menu.sort` permissions + `can:` route middleware. Unit tests in `tests/Unit/LanguageMenuModuleTest.php`. Task description kept below for reference.

## 1. Purpose

This document is an implementation task for migrating or rebuilding the Languages and Menus sections in another Laravel Gopanel.

The target panel may currently use DataTables. Replace DataTables in these two small configuration modules with normal Blade tables/lists because administrators need direct visual ordering and drag-and-drop behavior. This rule does not mean DataTables are forbidden everywhere: large transactional datasets should still use server-side pagination.

The implementation must provide:

- a normal Blade table for languages;
- drag-and-drop language ordering;
- safe default-language and active/visible states;
- a hierarchical menu tree rendered without DataTables;
- drag-and-drop sorting and moving menu items between parents;
- multilingual menu titles and optional metadata;
- dedicated validation, controllers, services and sort endpoints;
- AJAX loaders, rollback, permissions, cache invalidation and tests.

The target project may have different menu positions, item types or roles. Use its enums/lookups instead of copying source-project values.

## 2. Why ordinary tables instead of DataTables

Languages and menus are small ordered configuration collections. DataTables can conflict with manual ordering because it applies independent sorting, filtering, pagination and DOM redraws.

Use:

- plain Blade `<table>` for a flat language list;
- a plain nested list/tree or tree-table for menus;
- server-side filtering through query parameters where useful;
- no client-side column sorting on a manually ordered list;
- no pagination while drag-and-drop ordering spans the complete active scope.

If a project has hundreds of languages or thousands of menu items, use scoped/lazy tree loading rather than restoring a client-side DataTable.

## 3. Shared architectural boundaries

```text
app/
  Http/Controllers/Gopanel/
    LanguageController.php
    MenuController.php
  Http/Requests/Gopanel/Languages/
    StoreLanguageRequest.php
    UpdateLanguageRequest.php
    SortLanguagesRequest.php
    SetDefaultLanguageRequest.php
  Http/Requests/Gopanel/Menus/
    StoreMenuRequest.php
    UpdateMenuRequest.php
    MoveMenuRequest.php
  Queries/Gopanel/
    LanguageListQuery.php
    MenuTreeQuery.php
  Services/Gopanel/
    LanguageService.php
    LanguageSortService.php
    MenuService.php
    MenuTreeService.php
    NavigationCacheService.php
resources/views/gopanel/settings/
  languages/
    index.blade.php
    partials/table.blade.php
    partials/form.blade.php
    modals/form.blade.php
  menus/
    index.blade.php
    partials/tree.blade.php
    partials/node.blade.php
    partials/form.blade.php
    modals/form.blade.php
public/assets/gopanel/js/modules/
  languages.js
  menus.js
```

Controller coordinates HTTP. FormRequest validates and authorizes. Query reads. Service performs transactional writes and invalidates cache. Blade renders. JavaScript owns drag/drop and AJAX state.

# Part I — Languages

## 4. Language data model

Recommended fields:

```php
Schema::create('languages', function (Blueprint $table) {
    $table->id();
    $table->ulid('uid')->unique();
    $table->string('name', 100);
    $table->string('native_name', 100)->nullable();
    $table->string('code', 10)->unique();
    $table->string('regional_code', 20)->nullable()->unique(); // e.g. pt-BR
    $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
    $table->boolean('is_default')->default(false)->index();
    $table->boolean('is_active')->default(true)->index();
    $table->boolean('is_visible')->default(true);
    $table->boolean('is_rtl')->default(false);
    $table->unsignedInteger('sort_order')->default(0)->index();
    $table->timestamps();
});
```

Meanings:

- `is_default`: application fallback/default locale;
- `is_active`: translations and locale are operational;
- `is_visible`: user may select it in UI;
- `is_rtl`: frontend direction hint;
- `sort_order`: selector/admin display order.

The default language must always be active. Decide whether it must also be visible. There must be exactly one default language after initial setup.

Normalize locale codes in one place. Common policy: lowercase base code (`az`, `en`) and canonical regional code (`pt-BR`). Do not allow an existing code to change casually because translation rows, files, caches, URLs and user preferences may reference it.

## 5. Language routes and permissions

```php
Route::prefix('languages')->name('languages.')->group(function () {
    Route::get('/', [LanguageController::class, 'index'])->name('index');
    Route::get('/form/{language?}', [LanguageController::class, 'form'])->name('form');
    Route::post('/', [LanguageController::class, 'store'])->name('store');
    Route::patch('/{language:uid}', [LanguageController::class, 'update'])->name('update');
    Route::patch('/{language:uid}/visibility', [LanguageController::class, 'visibility'])->name('visibility');
    Route::patch('/{language:uid}/activation', [LanguageController::class, 'activation'])->name('activation');
    Route::put('/{language:uid}/default', [LanguageController::class, 'setDefault'])->name('default');
    Route::patch('/sort', [LanguageController::class, 'sort'])->name('sort');
    Route::delete('/{language:uid}', [LanguageController::class, 'destroy'])->name('destroy');
});
```

Permissions:

```text
gopanel.settings.languages.index
gopanel.settings.languages.create
gopanel.settings.languages.update
gopanel.settings.languages.state
gopanel.settings.languages.default
gopanel.settings.languages.sort
gopanel.settings.languages.delete
```

Use dedicated endpoints. Do not send arbitrary model class names and column names to a generic `sortable`, `statusChange` or delete endpoint.

## 6. Language query and normal Blade table

Languages should be loaded in their complete sortable scope:

```php
final class LanguageListQuery
{
    public function get(?int $countryId = null): Collection
    {
        return Language::query()
            ->with('country:id,name,code')
            ->when($countryId, fn ($q, $id) => $q->where('country_id', $id))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
```

If country filtering is active, define the ordering scope. Recommended: sorting filtered results updates only that country group. Alternatively disable sorting while filtered. Never silently reorder hidden rows against visible rows.

Blade:

```blade
<table class="table align-middle" id="language-table">
    <thead>
        <tr>
            <th class="language-drag-column"></th>
            <th>Flag</th>
            <th>Name</th>
            <th>Code</th>
            <th>Country</th>
            <th>Default</th>
            <th>Visible</th>
            <th>Active</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody id="language-sortable"
           data-sort-url="{{ route('gopanel.settings.languages.sort') }}">
        @foreach ($languages as $language)
            <tr data-id="{{ $language->uid }}">
                <td>
                    @can('gopanel.settings.languages.sort')
                        <button type="button" class="drag-handle" aria-label="Move {{ $language->name }}">
                            <i class="fas fa-grip-vertical"></i>
                        </button>
                    @endcan
                </td>
                <td>@include('gopanel.components.flag', ['country' => $language->country])</td>
                <td>{{ $language->name }}</td>
                <td><code>{{ $language->code }}</code></td>
                <td>{{ $language->country?->name ?? '—' }}</td>
                <td>@include('gopanel.settings.languages.partials.default-control')</td>
                <td>@include('gopanel.settings.languages.partials.visibility-control')</td>
                <td>@include('gopanel.settings.languages.partials.activation-control')</td>
                <td>@include('gopanel.settings.languages.partials.actions')</td>
            </tr>
        @endforeach
    </tbody>
</table>
```

Avoid model accessors that return raw flag/toggle/action HTML. Prefer escaped Blade components.

## 7. Language form and validation

Form fields:

- name and optional native name;
- base/regional locale code;
- optional country;
- RTL;
- active;
- visible;
- default.

Validation example:

```php
'name'         => ['required', 'string', 'max:100'],
'native_name'  => ['nullable', 'string', 'max:100'],
'code'         => ['required', 'regex:/^[a-z]{2,3}$/', Rule::unique('languages')->ignore($language)],
'regional_code'=> ['nullable', 'regex:/^[a-z]{2,3}-[A-Z]{2}$/', Rule::unique('languages')->ignore($language)],
'country_id'   => ['nullable', Rule::exists('countries', 'id')],
'is_rtl'       => ['required', 'boolean'],
'is_active'    => ['required', 'boolean'],
'is_visible'   => ['required', 'boolean'],
'is_default'   => ['required', 'boolean'],
```

Code immutability should be enforced after translations exist, or handled by an explicit migration service that updates all references atomically.

## 8. Default language service

Setting default must be transactional:

```php
public function setDefault(Language $language): void
{
    DB::transaction(function () use ($language) {
        Language::query()->whereKeyNot($language->id)->update(['is_default' => false]);
        $language->forceFill([
            'is_default' => true,
            'is_active'  => true,
            'is_visible' => true,
        ])->save();
    });

    $this->cache->forgetLanguages();
}
```

Do not offer a normal toggle that can leave zero defaults. Clicking another language should transfer default status. Prevent deactivating/deleting the current default until another default is chosen. Database engines that support partial unique indexes should enforce a single default; otherwise use transactions, locking and tests.

## 9. Language drag-and-drop

Request contract:

```json
{
  "items": [
    { "id": "01J...", "sort_order": 0 },
    { "id": "01K...", "sort_order": 1 }
  ],
  "scope": { "country_id": null }
}
```

The request validates every ID, uniqueness, integer positions, permission and scope. The service locks affected rows and updates them in a transaction.

JavaScript behavior:

- drag starts only from `.drag-handle`;
- save previous DOM order before dragging;
- send complete visible scope after drop;
- show a table overlay/spinner;
- disable another drag while request is pending;
- on failure restore the old order and show an error;
- on success keep the new order and announce it accessibly.

```javascript
$('#language-sortable').sortable({
    axis: 'y',
    handle: '.drag-handle',
    helper: fixTableCellWidths,
    start() { previousOrder = readLanguageOrder(); },
    update() { persistLanguageOrder(previousOrder); },
});
```

# Part II — Menus

## 10. Menu data model

Recommended core schema:

```php
Schema::create('menus', function (Blueprint $table) {
    $table->id();
    $table->ulid('uid')->unique();
    $table->foreignId('parent_id')->nullable()->constrained('menus')->cascadeOnDelete();
    $table->string('key', 100)->unique();
    $table->string('type', 30);
    $table->string('position', 30)->index();
    $table->string('target_role', 30)->default('all')->index();
    $table->string('url')->nullable();
    $table->string('route_name')->nullable();
    $table->json('route_parameters')->nullable();
    $table->string('target', 20)->default('_self');
    $table->string('icon_type', 20)->nullable();
    $table->text('icon')->nullable();
    $table->boolean('is_active')->default(true)->index();
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();

    $table->index(['position', 'parent_id', 'sort_order']);
});
```

Menu titles should use the project's existing translation layer. A menu may also have SEO metadata only if it represents a routable page. Do not require page metadata for separators, headings or external links.

Recommended menu types:

```text
route | internal_url | external_url | heading | separator
```

Validate type-dependent fields. For example, `route_name` is required for `route`, URL is required for URL types, and neither is needed for a separator.

## 11. Menu routes and permissions

```php
Route::prefix('menus')->name('menus.')->group(function () {
    Route::get('/', [MenuController::class, 'index'])->name('index');
    Route::get('/form/{menu?}', [MenuController::class, 'form'])->name('form');
    Route::post('/', [MenuController::class, 'store'])->name('store');
    Route::patch('/{menu:uid}', [MenuController::class, 'update'])->name('update');
    Route::patch('/{menu:uid}/activation', [MenuController::class, 'activation'])->name('activation');
    Route::patch('/tree', [MenuController::class, 'move'])->name('move');
    Route::delete('/{menu:uid}', [MenuController::class, 'destroy'])->name('destroy');
});
```

Permissions:

```text
gopanel.settings.menus.index
gopanel.settings.menus.create
gopanel.settings.menus.update
gopanel.settings.menus.state
gopanel.settings.menus.move
gopanel.settings.menus.delete
```

## 12. Tree query

The source panel browses one parent level at a time. The target implementation should display a visual tree like the category section, while keeping the query bounded.

For a small menu tree, eager-load a configured maximum depth:

```php
Menu::query()
    ->whereNull('parent_id')
    ->where('position', $position)
    ->with(['translations', 'children' => fn ($q) => $q->ordered()->with('translations')])
    ->ordered()
    ->get();
```

For deeper/larger trees, load roots initially and fetch children lazily. Never recursively eager-load unlimited depth. Enforce a maximum depth such as 3–5 levels based on frontend navigation design.

Filter the tree by `position` and optionally `target_role`. Drag-and-drop must operate only within the currently visible compatible scope.

## 13. Menu Blade tree

Use nested semantic lists rather than DataTables:

```blade
<div id="menu-tree"
     data-move-url="{{ route('gopanel.settings.menus.move') }}"
     data-max-depth="4">
    <ul class="menu-sortable" data-parent-id="" data-position="{{ $position }}">
        @foreach ($menus as $menu)
            @include('gopanel.settings.menus.partials.node', ['menu' => $menu, 'depth' => 0])
        @endforeach
    </ul>
</div>
```

Node partial:

```blade
<li class="menu-node" data-id="{{ $menu->uid }}" data-depth="{{ $depth }}">
    <div class="menu-node-card">
        @can('gopanel.settings.menus.move')
            <button class="menu-drag-handle" aria-label="Move {{ $menu->title }}">
                <i class="fas fa-grip-vertical"></i>
            </button>
        @endcan

        <span class="menu-node-icon">...</span>
        <div class="menu-node-content">
            <strong>{{ $menu->title }}</strong>
            <small>{{ $menu->type }} · {{ $menu->position }} · {{ $menu->target_role }}</small>
        </div>
        <div class="menu-node-actions">...</div>
    </div>

    <ul class="menu-sortable" data-parent-id="{{ $menu->uid }}">
        @foreach ($menu->children as $child)
            @include('gopanel.settings.menus.partials.node', ['menu' => $child, 'depth' => $depth + 1])
        @endforeach
    </ul>
</li>
```

Provide an empty drop zone inside parents with no children. Indentation, connector lines and drag handle must make hierarchy visually obvious.

## 14. Menu move/sort contract

Send the affected structure, not a serialized model-class request:

```json
{
  "moved_id": "01JMENU...",
  "new_parent_id": "01JPARENT...",
  "position": "header",
  "siblings": ["01JA...", "01JMENU...", "01JB..."],
  "source_parent_id": null,
  "source_siblings": ["01JC..."]
}
```

Backend validation must enforce:

- all IDs exist and are unique;
- administrator has move permission;
- item cannot parent itself;
- item cannot move under its descendant;
- maximum depth is not exceeded;
- source and target scopes/positions/roles are compatible;
- every provided sibling really belongs to the affected scope;
- concurrent stale moves are detected if versioning is used.

Service transaction:

1. lock moved item, target parent and affected siblings;
2. validate cycle/depth again inside the transaction;
3. update `parent_id`, position if cross-position moves are allowed, and sort orders;
4. normalize both source and destination sibling order to `0..n-1`;
5. commit;
6. invalidate navigation/API caches and dispatch a menu-updated event.

Return canonical affected tree HTML or normalized order so the browser can reconcile with server state.

## 15. Menu drag-and-drop JavaScript

Use connected sortable containers:

```javascript
function initMenuSortables(context = document) {
    $(context).find('.menu-sortable').sortable({
        connectWith: '.menu-sortable',
        handle: '.menu-drag-handle',
        items: '> .menu-node',
        placeholder: 'menu-drop-placeholder',
        tolerance: 'pointer',
        start(event, ui) {
            dragSnapshot = snapshotTree();
            ui.item.data('source-parent', ui.item.parent().data('parent-id') || null);
        },
        receive(event, ui) {
            if (wouldExceedDepth(ui.item, this)) cancelAndRestore(dragSnapshot);
        },
        update(event, ui) {
            if (this !== ui.item.parent()[0]) return;
            persistMenuMove(ui.item, dragSnapshot);
        },
    });
}
```

Avoid duplicate requests caused by both source and destination `update` events. Use one move coordinator and an in-flight guard.

Required UX:

- handle-only dragging;
- valid drop zones highlighted;
- invalid parent/depth visually rejected;
- tree overlay and `aria-busy` during save;
- disable further dragging while pending;
- success toast without full-page reload;
- exact DOM rollback on 403/409/422/500 or network failure;
- keyboard-accessible alternative: “Move up/down”, “indent/outdent” buttons or a move dialog.

## 16. Menu form

Recommended sections:

1. translated titles for enabled languages;
2. type and type-dependent destination fields;
3. position and target role/audience;
4. parent selector constrained to valid parents;
5. icon type: uploaded image, trusted font class or sanitized SVG;
6. target (`_self`, `_blank`) and active state;
7. optional page metadata for routable page items.

The parent selector must exclude the item and all its descendants. Changing position may need to reset parent if the parent belongs to another position.

Do not render arbitrary administrator-provided SVG with `{!! !!}` unless it passes a strict SVG sanitizer. Restrict uploaded icon MIME, size and dimensions. Validate route names against a controlled registry if administrators are allowed to choose routes.

## 17. Menu delete behavior

Choose and state one policy:

- prevent deletion while children exist; recommended default;
- delete the complete subtree after explicit confirmation;
- promote children to the deleted item's parent and normalize order.

Do not rely on database cascade without explaining the UI consequence. The confirmation dialog must state the number of affected descendants. Delete translation, metadata and icon files through a service, then invalidate navigation caches.

# Part III — Shared UI and operations

## 18. Controller examples

```php
public function sort(
    SortLanguagesRequest $request,
    LanguageSortService $service
): JsonResponse {
    $service->sort($request->validated('items'), $request->validated('scope'));
    return response()->json(['status' => 'success']);
}

public function move(
    MoveMenuRequest $request,
    MenuTreeService $service
): JsonResponse {
    $result = $service->move($request->validated());
    return response()->json(['status' => 'success', 'data' => $result]);
}
```

Inject services. Do not instantiate them inside methods. Do not catch every exception and return HTTP 200 with an error-like message. Use correct 403/409/422/500 responses and a stable JSON contract.

## 19. Loader and error states

Languages:

- table overlay while sorting;
- modal-body loader while form is fetched;
- save-button spinner;
- per-toggle pending state with rollback;
- empty table state.

Menus:

- initial tree loader if fetched asynchronously;
- child-loader for lazy nodes;
- complete tree overlay during move;
- modal form loader and save spinner;
- empty root/drop-zone state;
- conflict/error state with retry or server refresh.

```css
.sortable-shell {
    min-height: 8rem;
    position: relative;
}

.sortable-overlay {
    align-items: center;
    background: rgb(255 255 255 / 72%);
    display: flex;
    inset: 0;
    justify-content: center;
    position: absolute;
    z-index: 20;
}

.drag-handle,
.menu-drag-handle {
    background: transparent;
    border: 0;
    color: #74788d;
    cursor: grab;
    padding: .5rem;
}

.drag-handle:active,
.menu-drag-handle:active { cursor: grabbing; }

.menu-sortable {
    list-style: none;
    margin: 0;
    min-height: .75rem;
    padding-left: 1.5rem;
}

.menu-node-card {
    align-items: center;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: .5rem;
    display: flex;
    gap: .75rem;
    margin-bottom: .5rem;
    padding: .75rem;
}

.menu-drop-placeholder {
    background: #eef2ff;
    border: 2px dashed #556ee6;
    border-radius: .5rem;
    min-height: 3.25rem;
}
```

## 20. Cache and downstream synchronization

Language changes affect:

- cached active language lists;
- translation service-provider caches;
- locale selectors;
- API config endpoints;
- frontend static/cache output.

Menu changes affect:

- web/mobile navigation API caches;
- rendered menu fragments;
- sitemap only if menus participate in it;
- frontend revalidation.

Invalidate targeted keys/tags after successful commit. Do not call a global `Cache::flush()` for a single row change. Dispatch events such as `LanguagesChanged` and `NavigationChanged` so consumers remain decoupled.

## 21. Security and integrity

- apply permissions to every route and service operation;
- use dedicated endpoints instead of accepting model class/column from the browser;
- validate all IDs and scopes;
- use transactions and row locks for ordering;
- prevent menu cycles and excessive depth;
- sanitize SVG and validate icon uploads;
- whitelist menu types, positions, roles, route names and targets;
- prevent default language deletion/deactivation;
- audit default changes, language-code changes, menu moves and deletions;
- invalidate only relevant cache;
- never trust the DOM order as proof of authorization.

## 22. Test matrix

Language feature tests:

- ordinary table renders ordered languages without DataTable initialization;
- country filter has explicit sort behavior;
- create/update validation normalizes locale code;
- duplicate locale code is rejected;
- setting default activates and shows the language;
- concurrent/default changes leave exactly one default;
- default cannot be deleted/deactivated;
- sort rejects missing, duplicate or out-of-scope IDs;
- failed sort rolls back all positions;
- language and translation caches are invalidated after commit.

Menu feature tests:

- tree is ordered by parent and `sort_order`;
- create/update validates fields by menu type;
- title translations are saved;
- move within one parent normalizes order;
- cross-parent move updates both sibling groups;
- self-parent and descendant-parent moves are rejected;
- maximum depth is enforced;
- incompatible position/role moves are rejected;
- unauthorized move returns 403;
- failed move rolls back parent and ordering;
- delete policy behaves exactly as documented;
- navigation caches are invalidated after commit.

JavaScript/browser tests:

- drag sends one request only;
- loader prevents a second drag;
- network/422/409 errors restore the original DOM;
- modal loaders and validation errors render correctly;
- keyboard move alternative works;
- no DataTable is initialized for either module.

## 23. Migration plan from the old Gopanel

1. Inspect the existing `languages` and `menus` schemas, translations, APIs and cache keys.
2. Remove DataTable markup/configuration only from these two screens.
3. Add/normalize `sort_order`, default/active/visible language fields and constraints.
4. Add/normalize menu `parent_id`, position, role, type and sort fields.
5. Add dedicated permissions and routes.
6. Add FormRequests, query classes and transaction-backed services.
7. Build the ordinary language table and drag handle.
8. Build the nested menu tree and reusable node partial.
9. Implement language sort and menu move endpoints with scope/cycle/depth validation.
10. Add modal forms, loaders, error states and DOM rollback.
11. Connect translation/meta/icon workflows supported by the target project.
12. Add targeted cache invalidation and downstream events.
13. Remove obsolete DataTable JavaScript and generic model/column mutation calls.
14. Run feature, unit and browser tests with realistic tree structures.

## 24. Acceptance criteria

The work is complete only when:

- Languages and Menus use ordinary Blade UI, not DataTables;
- language rows can be reordered through a dedicated secure endpoint;
- exactly one active default language is always maintained;
- filtered sorting has an explicit, tested scope;
- menus render as a clear hierarchical tree;
- menu items can be reordered and moved between valid parents;
- cycle, depth and incompatible-scope moves are rejected;
- drag failures restore the exact previous UI order;
- all async actions have loader, success, empty and error states;
- forms use target-project enums/lookups and translation system;
- icon/SVG handling is safe;
- permissions exist in Blade and backend;
- cache invalidation happens after commit and does not globally flush unrelated data;
- another AI model can implement both modules without access to the source project's DataTable conventions.

