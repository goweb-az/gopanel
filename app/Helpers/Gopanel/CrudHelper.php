<?php


namespace App\Helpers\Gopanel;

use App\Repositories\BaseRepository;
use App\Services\Activity\LogService;
use Illuminate\Database\Eloquent\Model;

/**
 * Panelin köhnə CRUD giriş nöqtəsi.
 *
 * Yazma məntiqi artıq burada DEYİL - `App\Repositories\BaseRepository`-dədir
 * (bax: .claude/rules/01-umumi.md § 1). Bu sinif silinmir, çünki starter
 * üzərində qurulmuş layihələrdə `$this->crudHelper->saveInstance(...)`
 * çağırışları var; imza olduğu kimi qalır, iş repozitoriyaya ötürülür.
 *
 * Yeni kodda birbaşa `BaseRepository` (və ya domen servisi) işlədilir.
 */
class CrudHelper
{
    private LogService $logging;

    private BaseRepository $repository;

    public function __construct(?BaseRepository $repository = null)
    {
        $this->logging    = new LogService("gopanel");
        $this->repository = $repository ?? new BaseRepository();
    }

    public static function save(Model $item, $data): Model
    {
        return (new static())->saveInstance($item, $data);
    }

    public function saveInstance(Model $item, $data): Model
    {
        $this->logging->info("start save model data");

        $item = $this->repository->save($item, (array) $data);

        $this->logging->info("end save model data", ['item' => $item]);

        return $item;
    }


    public function message($item)
    {
        return !is_null($item) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
    }
}
