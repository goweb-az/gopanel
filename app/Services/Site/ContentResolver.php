<?php

namespace App\Services\Site;

class ContentResolver
{
    public function handle($model)
    {
        $controller = $model->controller;

        if (!class_exists($controller)) {
            abort(500, "Controller sinfi tapılmadı: {$controller}");
        }

        $controllerInstance = app($controller);

        if (!method_exists($controllerInstance, 'single')) {
            abort(500, "{$controller} sinifində single() metodu tapılmadı.");
        }

        return $controllerInstance->single($model);
    }
}
