<?php


namespace App\Helpers\Gopanel;

use App\Services\Activity\LogService;
use Illuminate\Database\Eloquent\Model;
use Exception;
use InvalidArgumentException;

class CrudHelper
{
    private LogService $logging;

    public function __construct()
    {
        $this->logging = new LogService("gopanel");
    }

    public static function save(Model $item, $data): Model
    {
        return (new static())->saveInstance($item, $data);
    }

    public function saveInstance(Model $item, $data): Model
    {
        $this->logging->info("start save model data");
        if (is_null($item))
            throw new InvalidArgumentException('Item cannot be null.');

        foreach ($data as $key => $value) {
            if (!is_array($value) && in_array($key, $item->getFillable()))
                $item->$key = $value;
        }
        $item->save();
        $this->logging->info("end save model data", ['item' => $item]);
        return $item->fresh();
    }


    public function message($item)
    {
        return !is_null($item) ? "Məlumat uğurla dəyişdirildi!" : "Məlumat uğurla yaradıldı!";
    }
}
