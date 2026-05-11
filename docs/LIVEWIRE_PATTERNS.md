# Livewire v4 patterns — Gopanel

This is the project-local cookbook every Gopanel module follows. The patterns
were locked in during the v4 migration; new modules should mirror them
verbatim. The full historical migration plan lives at
`refactor/LIVEWIRE_V4_MIGRATION_PLAN_EN.md` (untracked, kept locally as a
reference) — this document is the merged subset that ships with the repo.

---

## 1. Component shape

**Single-file (SFC) only.** Every Livewire component is one `.blade.php`
file under `resources/views/livewire/gopanel/{module}/`, beginning with:

```php
<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new
#[Layout('gopanel.layouts.main')]
class extends Component {
    // ...
}; ?>

<div>
    {{-- view markup --}}
</div>
```

No `App\Livewire\*Component` PHP files. Form Objects under
`app/Livewire/Forms/` stay as standalone classes — they are not components.

---

## 2. Folder layout per module

```
resources/views/livewire/gopanel/{module}/
    index.blade.php          ← list (datatable or computed records)
    form.blade.php           ← shared by create + edit (or modal-pattern modal)
    create.blade.php         ← page wrapper, embeds <livewire:gopanel.{module}.form />
    edit.blade.php           ← page wrapper with route-bound model
```

Modal-pattern modules (slider, social, language, …) skip
`create.blade.php` / `edit.blade.php` — `form.blade.php` is a Livewire
modal embedded inside `index.blade.php` and opened via an event.

---

## 3. Generic naming convention inside SFC class bodies

| Identifier | Use |
|---|---|
| `RecordForm` | Form Object alias: `use App\Livewire\Forms\<Module>Form as RecordForm;` |
| `RecordModel` | Eloquent alias (form/modal SFCs only): `use App\Models\…\<Module> as RecordModel;` |
| `$form` | Form Object instance |
| `$record` | Eloquent row variable in actions and `@foreach ($this->rows as $record)` |
| `$recordId` | Edit pivot in form SFCs: `public ?int $recordId = null;` |
| `$this->records` | Computed list collection (modal-pattern indexes) |
| `$this->rows` | Datatable paginator (datatable-pattern indexes), provided by `WithDatatable` |
| `$permissionCreate` | Spatie permission key for store path (public string) |
| `$permissionEdit`   | Spatie permission key for update path (public string) |
| `$permissionDelete` | Spatie permission key for destroy path (public string) |
| `$indexRoute`       | Named route to redirect after save (full-page form SFCs) |
| `$eventSaved`       | Event the form dispatches after save (modal SFCs) |
| `save()`            | Single store/update method on the Form Object |

Page wrappers (`create.blade.php`, `edit.blade.php`) keep the real model
name (`public Blog $record;`) since route model binding reads better.

---

## 4. Form Object shape

```php
class BlogForm extends BaseForm
{
    public array $form = [
        'id'        => null,
        'title'     => '',
        'is_active' => true,
    ];

    public mixed $upload = null;

    protected function rules(): array
    {
        return [
            'form.title'     => ['required', 'string', 'max:255'],
            'form.is_active' => 'boolean',
            'upload'         => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function setItem(Blog $blog): void
    {
        $this->form = [
            'id'        => $blog->id,
            'title'     => $blog->title ?? '',
            'is_active' => (bool) ($blog->is_active ?? true),
        ];
        $this->prepareTranslations($blog); // BaseForm helper, read-only
    }

    public function save(): Blog
    {
        $blog = SaveBlogFormAction::run(
            form: $this->form,
            upload: $this->upload,
            translations: $this->translations,
        );

        $this->form['id'] = $blog->id;
        $this->upload     = null;

        return $blog;
    }
}
```

- All DB-bound fields live in `$form` array, not separate properties.
- Use `Model::findOrNew($this->form['id'])` — never the
  `$id ? findOrFail($id) : new Model()` ternary.
- Validation rules use array syntax — never pipe-strings.
- `save()` is a thin adapter that forwards to a `Save{Subject}FormAction`.
  Form Objects must NOT touch the database, the filesystem, or the cache
  directly.

---

## 5. Action layer

**Mandatory rule:** every method on a Livewire SFC that mutates persistent
state delegates to a single-purpose Action class under
`app/Actions/Gopanel/{Module}/`.

```php
use Lorisleiva\Actions\Concerns\AsAction;

class ToggleSliderActiveAction
{
    use AsAction;

    public function handle(int $id): Slider
    {
        $slider = Slider::findOrFail($id);
        $slider->is_active = ! $slider->is_active;
        $slider->save();
        return $slider;
    }
}

// SFC method
public function toggleActive(int $id): void
{
    $this->authorize($this->permissionEdit);
    ToggleSliderActiveAction::run($id);
    unset($this->records);
}
```

### Strict separation contract

**Actions MAY contain:**
- database writes, transactions, model creation/update/delete
- relation synchronization and cleanup
- domain rules and guards
- cache invalidation
- file/storage operations (`FileUploader`, `Storage::put`)
- event/job dispatching
- orchestration of services (calling other actions)

**Actions MUST NOT contain:**
- redirects (`redirect()`, `redirectRoute()`)
- session/flash messages
- browser events (`Livewire::dispatch`)
- response formatting (`response()->json`, `view()`)
- Livewire/UI state (no `unset($this->records)`, no `$this->dispatch('notify', …)`)
- request-specific presentation logic

**Controllers / Livewire components MAY:**
- validate input
- authorize requests
- call actions
- return / emit UI responses
- manage UI state (modal open, computed cache bust, navigation)

### Action splitting

When a `Save{Subject}FormAction` does more than ~50 lines or mixes
concerns, extract every distinct concern into its own Action and let the
Save action orchestrate them by name:

- Avatar fetch → `GenerateAdminAvatarAction`
- Role attachment → `SyncAdminRoleAction`
- Permission diff + audit log → `SyncRolePermissionsAction`

Trivial single-statement work (one `FileUploader::toPublic` call, one
`Hash::make`) can stay inline; do not invent an Action just to wrap a
one-liner.

---

## 6. Datatable

Use the `WithDatatable` trait + `<x-gopanel.datatable>` component:

```php
new
#[Layout('gopanel.layouts.main')]
class extends Component {
    use AuthorizesGopanel, WithDatatable;

    public string $permissionEdit   = 'gopanel.blog.edit';
    public string $permissionDelete = 'gopanel.blog.delete';

    protected function datatableDefaultSort(): array
    {
        return ['id', 'desc'];
    }

    protected function datatableQuery(): Builder
    {
        return Blog::query()->with('translations');
    }

    protected function datatableColumns(): array
    {
        return [
            ['key' => 'id',         'label' => '#',         'sortable' => true,  'width' => '60px'],
            ['key' => 'title',      'label' => 'Başlıq',    'sortable' => true,  'searchable' => true],
            ['key' => 'is_active',  'label' => 'Status',    'sortable' => true,  'width' => '90px',  'align' => 'center'],
            ['key' => 'actions',    'label' => 'Əməliyyat', 'width' => '120px', 'align' => 'center'],
        ];
    }
};
```

Override `datatableDefaultSort()` instead of redeclaring `$sortField` /
`$sortDirection` properties — PHP forbids redeclaring trait properties.

---

## 7. Loading states

Livewire v4 toggles a `data-loading` attribute on the element that
triggered a network request. Pair it with the utility classes shipped in
`resources/css/app.css`:

```blade
<button type="submit" class="btn btn-primary">
    <span class="lw-not-loading"><i class="fas fa-save me-1"></i> {{ __('Yadda saxla') }}</span>
    <span class="lw-loading"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Saxlanır...') }}</span>
</button>
```

Do NOT use `wire:loading`, `wire:loading.attr="disabled"`,
`wire:loading.remove`, or `wire:target` on new code.

---

## 8. Authorization on Livewire actions

Use the `AuthorizesGopanel` trait so `$this->authorize()` resolves
against the `gopanel` guard (Livewire's update endpoint runs under the
default `web` guard otherwise):

```php
use App\Livewire\Concerns\AuthorizesGopanel;

new class extends Component {
    use AuthorizesGopanel;

    public function delete(int $id): void
    {
        $this->authorize($this->permissionDelete);
        DeleteBlogAction::run($id);
    }
};
```

A small global helper enforces the gopanel guard on every Livewire
update request — see `App\Http\Middleware\UseGopanelGuardIfAuthenticated`
wired in `AppServiceProvider`.

---

## 9. Storage disk

Never hard-code `Storage::disk('public')`. Use the `gopanel_disk()`
helper:

```php
Storage::disk(gopanel_disk())->put($path, $contents);
$file->storeAs($folder, $name, gopanel_disk());
```

Resolves `config('gopanel.configs.storage.disk')` (env:
`GOPANEL_STORAGE_DISK`) with sensible fallbacks.

---

## 10. Routes

Use `Route::livewire('/path', 'gopanel.module.view-name')`:

```php
Route::prefix('blog')->name('blog.')->group(function () {
    Route::livewire('/',                'gopanel.blog.index')->name('index');
    Route::livewire('/create',          'gopanel.blog.create')->name('create');
    Route::livewire('/{blog}/edit',     'gopanel.blog.edit')->name('edit');
});
```

Restful prefixes only. No `/get/form/{item?}`-style endpoints.

---

## 11. Navigation

Add `wire:navigate` to every internal `<a href="{{ route('gopanel.…') }}">`.
External links and `target="_blank"` anchors stay normal.

`document.write` is forbidden anywhere in the layout — it wipes the page
on every wire:navigate swap. Use server-side `{{ date('Y') }}` for
dynamic content in the footer.
