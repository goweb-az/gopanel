<?php

namespace App\Helpers\Common;

class ModelList
{
    public static function all(): array
    {
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
    }

    public static function mapByKey(): array
    {
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
    }

    public static function get(string $key): string|null
    {
        return self::mapByKey()[$key] ?? null;
    }
}
