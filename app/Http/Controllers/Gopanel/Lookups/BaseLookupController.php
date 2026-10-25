<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gopanel\Lookups;

use App\Http\Controllers\GoPanelController;
use App\Http\Requests\Gopanel\Lookups\BaseLookupSaveRequest;
use App\Http\Requests\Gopanel\Lookups\LookupSortRequest;
use App\Http\Requests\Gopanel\Lookups\LookupToggleRequest;
use App\Services\Gopanel\Lookup\LookupDefinition;
use App\Services\Gopanel\Lookup\LookupRegistry;
use App\Services\Gopanel\Lookup\LookupService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Bütün status/tip (lookup) bölmələrinin ortaq controller-i.
 *
 * NİYƏ abstrakt baza: lookup-lar bir-birinə 90% oxşardır (siyahı, modal
 * forma, saxla, sil, sürüklə-burax). Hər tip üçün controller/blade/JS-i
 * sıfırdan yazmaq əvəzinə alt sinif yalnız KONFİQURASİYA verir:
 *
 *     class OrderStatusController extends BaseLookupController
 *     {
 *         protected string $model       = OrderStatus::class;
 *         protected string $request     = OrderStatusSaveRequest::class;
 *         protected array  $extraFields = ['color', 'class_name'];
 *         protected array  $booleans    = ['is_default', 'is_public', 'is_active'];
 *         protected array  $usedBy      = ['orders' => 'order_status_id'];
 *     }
 *
 * Tip açarı, başlıq, route və icazə adları cari route-dan və
 * `config/gopanel/lookups.php` reyestrindən çıxarılır - alt sinifdə
 * təkrarlanmır (aquastores-da `$routeName`/`$title` əl ilə yazılırdı və
 * icazə adları route ilə ayrışmışdı).
 *
 * Controller nazikdir: yazma qaydaları `LookupService`-də, validasiya
 * FormRequest-də, icazə route middleware-i (`gopanel.can`) + FormRequest-dədir.
 *
 * Genişləndirmə nöqtələri (override edilə bilər):
 *   - `inUseCount()`   - sətir başqa cədvəllərdə neçə yerdə istifadə olunur;
 *   - `formData()`     - formaya əlavə dəyişənlər (məs. select siyahısı);
 *   - `$viewPath`      - modulun öz blade-ləri (yoxdursa ortaq səhifə/forma).
 */
abstract class BaseLookupController extends GoPanelController
{
    /** @var class-string<Model> */
    protected string $model;

    /** @var class-string<BaseLookupSaveRequest> */
    protected string $request;

    /** Əlavə sütunlar: `color`, `class_name`, `icon`... */
    protected array $extraFields = [];

    /** Bayraqlar: `is_default`, `is_public`, `is_active`... */
    protected array $booleans = ['is_active'];

    /**
     * Silmə qadağası üçün istinadlar: `cədvəl => xarici açar sütunu`.
     * Daha mürəkkəb hal üçün `inUseCount()` override edilir.
     *
     * @var array<string, string>
     */
    protected array $usedBy = [];

    /** Boş qalsa reyestrdəki başlıq işlədilir. */
    protected ?string $title = null;

    /**
     * Modulun blade qovluğu (məs. `gopanel.pages.lookups.order-statuses`).
     * Boş qalsa həmin standart yol yoxlanılır, o da yoxdursa ortaq partial-lar.
     */
    protected ?string $viewPath = null;

    private ?LookupDefinition $definitionCache = null;

    public function __construct(protected LookupService $lookups)
    {
        parent::__construct();
    }

    public function index(): ViewContract
    {
        $definition = $this->definition();

        $items = $this->model::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view($this->resolveView('index'), [
            'definition'  => $definition,
            'items'       => $items,
            'lookupMenu'  => $this->lookupMenu(),
            'activeType'  => $definition->type,
            'needsIcons'  => $definition->hasField('icon'),
        ]);
    }

    public function getForm(?string $id = null): JsonResponse
    {
        $definition = $this->definition();
        $this->authorizeAbility($definition->permission($id === null ? 'add' : 'edit'));

        $item = $id === null ? new $this->model() : $this->findOrFail($id);

        $this->response['html'] = View::make($this->resolveView('form'), array_merge([
            'definition' => $definition,
            'item'       => $item,
            'route'      => $definition->route('save', $item->exists ? $item->identifier_id : null),
            'switches'   => $definition->switches($item),
        ], $this->formData($item)))->render();

        $this->success_response([], 'Forma hazırdır');

        return $this->response_json();
    }

    public function save(?string $id = null): JsonResponse
    {
        /** @var BaseLookupSaveRequest $request */
        $request = app($this->request);

        $item = $id === null ? new $this->model() : ($request->existingItem() ?? $this->findOrFail($id));
        $creating = !$item->exists;

        $this->lookups->save($item, $request->payload(), $this->definition());

        $this->response['redirect'] = $this->definition()->route('index');
        $this->success_response([], $creating ? 'Məlumat uğurla yaradıldı' : 'Məlumat uğurla dəyişdirildi');

        if ($creating) {
            $this->response_code = 201;
        }

        return $this->response_json();
    }

    public function destroy(string $id): JsonResponse
    {
        $item = $this->findOrFail($id);

        $this->lookups->delete($item, $this->inUseCount($item));
        $this->success_response([], 'Məlumat uğurla silindi');

        return $this->response_json();
    }

    public function sort(LookupSortRequest $request): JsonResponse
    {
        $this->lookups->sort($this->model, $request->keys());
        $this->success_response([], 'Sıralama yeniləndi');

        return $this->response_json();
    }

    public function toggle(LookupToggleRequest $request): JsonResponse
    {
        $item = $this->findOrFail((string) $request->input('id'));

        $this->lookups->toggle($item, (string) $request->input('row'), $request->value(), $this->definition());
        $this->success_response([], 'Məlumat uğurla dəyişdirildi');

        return $this->response_json();
    }

    /**
     * Sətrin başqa cədvəllərdə istifadə sayı - silmə qadağası üçün.
     *
     * Standart: `$usedBy` xəritəsi üzrə sayılır. Əlaqə polimorfdursa və ya
     * JSON sütunundadırsa alt sinif bu metodu override edir.
     */
    protected function inUseCount(Model $item): int
    {
        return $this->usedBy === [] ? 0 : $this->lookups->usageCount($item, $this->usedBy);
    }

    /** Formaya əlavə dəyişənlər (məs. valideyn siyahısı) - alt sinif üçün. */
    protected function formData(Model $item): array
    {
        return [];
    }

    protected function definition(): LookupDefinition
    {
        if ($this->definitionCache !== null) {
            return $this->definitionCache;
        }

        $type = $this->currentType();
        $instance = new $this->model();

        return $this->definitionCache = new LookupDefinition(
            type: $type,
            title: $this->title ?? (LookupRegistry::find($type)['title'] ?? Str::headline($type)),
            model: $this->model,
            extraFields: array_values($this->extraFields),
            booleans: array_values($this->booleans),
            translated: array_values((array) ($instance->translatedAttributes ?? [])),
        );
    }

    /**
     * Bölmədaxili sidebar: yalnız adminin `index` icazəsi olan tiplər.
     * Siyahı reyestrdən gəlir - blade-də əl ilə yazılmır.
     *
     * @return list<array{type: string, title: string, icon: string, url: string}>
     */
    protected function lookupMenu(): array
    {
        $admin = auth('gopanel')->user();
        $menu = [];

        foreach (LookupRegistry::types() as $type => $entry) {
            if ($admin === null || !$admin->can(LookupRegistry::permission($type, 'index'))) {
                continue;
            }

            $menu[] = [
                'type'  => $type,
                'title' => $entry['title'],
                'icon'  => $entry['icon'],
                'url'   => route(LookupRegistry::routeName($type) . '.index'),
            ];
        }

        return $menu;
    }

    /**
     * Cari route `gopanel.lookups.{tip}.{əməliyyat}` formatındadır.
     */
    protected function currentType(): string
    {
        $name = (string) request()->route()?->getName();
        $prefix = LookupRegistry::PREFIX . '.';

        if (!str_starts_with($name, $prefix)) {
            throw new NotFoundHttpException();
        }

        return Str::before(Str::after($name, $prefix), '.');
    }

    /**
     * Tipin öz wrapper-i varsa o (fərdiləşdirmə üçün), yoxdursa ortaq
     * `gopanel.pages.lookups.{index|form}` - yeni tip üçün blade məcburi deyil.
     */
    protected function resolveView(string $name): string
    {
        $path = $this->viewPath ?? 'gopanel.pages.lookups.' . $this->definition()->type;

        return View::exists($path . '.' . $name) ? $path . '.' . $name : 'gopanel.pages.lookups.' . $name;
    }

    protected function findOrFail(string $id): Model
    {
        $item = $this->model::resolveByKey($id);

        if ($item === null) {
            throw new NotFoundHttpException('Məlumat tapılmadı');
        }

        return $item;
    }

    /**
     * Forma endpoint-i route-da `add|edit` hər hansı biri ilə açılır;
     * burada konkret əməliyyat (yeni → add, mövcud → edit) yoxlanılır.
     */
    protected function authorizeAbility(string $ability): void
    {
        $admin = auth('gopanel')->user();

        if ($admin === null || !$admin->can($ability)) {
            throw new AccessDeniedHttpException('Bu əməliyyat üçün icazəniz yoxdur.');
        }
    }
}
