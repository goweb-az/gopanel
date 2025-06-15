<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class GeneralService
{
    public function __construct()
    {
        // 
    }

    public function class_exists($class)
    {
        return class_exists($class);
    }


    public function modelInstance($model)
    {
        return app($model);
    }


    public function parse_str($rows)
    {
        return parse_str(urldecode($rows), $rows);;
    }

    public function getMorphClass($class, $hash = 0)
    {
        if ($hash)
            $class = $this->morphDecode($class);
        return $this->controlEnum($class);
    }


    private function controlEnum($class)
    {
        if (defined('App\Http\Enums\Componenets\GenericMorphEnum::' . $class)) {
            return constant('App\Http\Enums\Compnenets\GenericMorphEnum::' . $class)->value;
        }
        return $class;
    }


    private function morphDecode($morph)
    {
        return base64_decode($morph);
    }

    public function controlItem($class, $uuid)
    {
        $modelInstance  = app($class);
        $item           = $modelInstance::where('uid', $uuid)->first();
        if (isset($item->id))
            return $item;
        return false;
    }


    public function getSharedRoutes()
    {
        return Cache::remember("shareable_routes", 1, function () {
            $list = config('shareable_routes');
            foreach ($list as $key => $value) {
                $list[$key]['url'] = route($value['name']);
            }
            return $list;
        });
    }
}
