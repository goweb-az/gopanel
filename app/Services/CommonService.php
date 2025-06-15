<?php

namespace App\Services;

use App\Services\Activity\LogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class CommonService
{

    public $logging;

    public function __construct()
    {
        $this->logging = new LogService();
    }


    public function saveModel(Model $item, $data): Model
    {
        if (is_null($item))
            throw new InvalidArgumentException('Item cannot be null.');

        foreach ($data as $key => $value) {
            if (!empty($value))
                $item->$key = $value;
        }
        $item->save();
        return $item->fresh();
    }

    public function share(array $data)
    {
        return view()->share($data);
    }
}
