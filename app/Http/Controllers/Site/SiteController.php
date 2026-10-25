<?php

namespace App\Http\Controllers\Site;


use App\Http\Controllers\Controller;
use App\Services\Seo\SchemaService;
use App\Services\Site\Seo\MetaService;
use Illuminate\Database\Eloquent\Model;
use App\Services\Site\SiteService;
use Illuminate\Support\Facades\View;

class SiteController extends Controller
{
    public SiteService $siteService;

    public function __construct()
    {
        parent::__construct();
        $this->siteService          = new SiteService();
        $this->response['redirect'] = false;
        $this->setSchema(false);
    }


    public function check_item($item)
    {
        if (!is_null($item?->is_active) && $item?->is_active == 0) {
            abort(404);
        }
    }



    public function meta_share($item)
    {
        $meta = $item?->meta()?->first();
        if ($meta)
            MetaService::sharePageMeta($meta->toArray());
    }


    protected function setSchema(string|false $view = false, array $data = []): void
    {
        $schemaView = false;

        if ($view && View::exists($view))
            $schemaView = $view;
        View::share(array_merge(
            ['schema_markup_source' => $schemaView],
            $data
        ));
    }


    /**
     * Səhifənin öz meta məlumatını təyin edir - menyudan gələni əvəz edir.
     *
     * `meta_share()`-dən fərqi: qeydin `page_meta_data` sətri yoxdursa meta
     * qeydin başlıq/təsvirindən qurulur (əvvəl heç nə paylaşılmırdı və bütün
     * yazılar siyahı menyusunun başlığı ilə indekslənirdi).
     *
     *     $this->setPageMeta($blog);                       // qeydin öz sətri
     *     $this->setPageMeta(['title' => ..., ...]);       // menyusuz səhifə
     *
     * `View::share()` işlədilir: `ViewServiceProvider` head kompozeri
     * `meta_title` paylaşılıbsa menyu meta-sını üstündən yazmır.
     *
     * @param Model|array<string, mixed> $source
     */
    protected function setPageMeta(Model|array $source): void
    {
        $service = app(MetaService::class);

        MetaService::share(is_array($source)
            ? $service->composeFromArray($source)
            : $service->composeForModel($source));
    }


    /**
     * `<meta name="robots">` dəyərini dəyişir. Default `config('seo.meta.robots')`;
     * texniki səhifələr axtarış nəticəsində görünməməlidir:
     * `$this->setPageRobots('noindex, follow')`.
     */
    protected function setPageRobots(string $value): void
    {
        View::share('page_robots', $value);
    }


    /**
     * Səhifənin JSON-LD qovşaqlarını təyin edir (`site.schema-markups.render`).
     *
     * Baza qovşaqları (şirkət + sayt) avtomatik əlavə olunur, ona görə yalnız
     * səhifəyə xas qovşaqlar göndərilir; `null` qovşaqlar atılır:
     *
     *     $this->setSchemaGraph([
     *         $this->schema()->blogPosting($blog),
     *         $this->schema()->breadcrumb([...]),
     *     ]);
     *
     * Çağırılmasa `ViewServiceProvider` yalnız baza qovşaqlarını verir.
     *
     * @param array<int, array<string, mixed>|null> $nodes
     */
    protected function setSchemaGraph(array $nodes = []): void
    {
        $graph = array_values(array_filter(
            array_merge($this->schema()->base(), $nodes),
            fn ($node) => is_array($node) && $node !== []
        ));

        $this->setSchema('site.schema-markups.render', ['schema_graph' => $graph]);
    }


    /** JSON-LD qovşaqlarını qurmaq üçün servis. */
    protected function schema(): SchemaService
    {
        return app(SchemaService::class);
    }
}
