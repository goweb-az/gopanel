<?php

namespace App\Helpers\Common;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ModelList
{
    public static function all(): array
    {
        return Cache::remember('model_list_all', Carbon::now()->addDays(30), function () {
            $path = app_path('Models');
            $models = [];

            $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            foreach ($rii as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $relativePath = str_replace($path . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $class = str_replace(['/', '.php'], ['\\', ''], $relativePath);
                $fullClass = 'App\\Models\\' . $class;

                if (class_exists($fullClass)) {
                    $models[] = $fullClass;
                }
            }

            return $models;
        });
    }


    public static function mapByKey(): array
    {
        return Cache::remember('model_list_map_by_key', Carbon::now()->addDays(30), function () {
            $list = [];

            foreach (self::all() as $class) {
                $instance = new $class;

                if (property_exists($instance, 'model_key') && !empty($instance->model_key)) {
                    $key = $instance->model_key;
                } elseif (method_exists($instance, 'getTable')) {
                    $key = $instance->getTable();
                } else {
                    continue;
                }

                $list[$key] = $class;
            }

            return $list;
        });
    }


    public static function get(string $key): string|null
    {
        return self::mapByKey()[$key] ?? null;
    }
}
