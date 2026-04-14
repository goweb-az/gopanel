<?php

namespace App\Helpers\Common;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityLogHelper
{
    /**
     * Config-dən modelin mesajını oxuyub placeholder-ları əvəz edir.
     *
     * Placeholder-lar:
     *  - `:causer`  → əməliyyatı edən istifadəçinin adı
     *  - `:any_attribute` → modelin istənilən attribute-u (name, gate_number, branch_number, ...)
     */
    public static function resolveDescription(Model $model, Activity $activity, string $eventName): void
    {
        $modelName = class_basename($model);
        $messages = config("custom.activity_messages.{$modelName}", []);

        if (isset($messages[$eventName])) {
            $description = $messages[$eventName];

            // Bütün :placeholder-ları tap
            preg_match_all('/:([a-z_]+)/', $description, $matches);

            foreach ($matches[1] as $attr) {
                if ($attr === 'causer') {
                    $user = auth()->guard('gopanel')->user() ?? auth()->guard('web')->user();
                    $value = $user ? (($user->full_name ?? $user->name ?? '') . ' ' . ($user->surname ?? '')) : 'Sistem';
                    $value = trim($value);
                } else {
                    $value = $model->getAttribute($attr) ?? '';
                }
                $description = str_replace(":{$attr}", $value, $description);
            }
        } else {
            $description = "{$modelName} modelində {$eventName} əməliyyatı aparıldı.";
        }

        $activity->description = $description;
    }
}
