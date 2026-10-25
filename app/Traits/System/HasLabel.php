<?php

namespace App\Traits\System;

/**
 * Model label trait - provides human-readable model name
 * 
 * Usage: Add HasLabel trait to model, override $label property
 * 
 * class Device extends Model {
 *     use HasLabel;
 *     protected static string $label = 'Cihaz';
 * }
 * 
 * Then: $device->getLabel() returns 'Cihaz'
 */
trait HasLabel
{
    /**
     * Get model label (human-readable name)
     */
    public function getLabel(): string
    {
        return static::$label ?? class_basename(static::class);
    }

    /**
     * Get model label statically
     */
    public static function label(): string
    {
        return static::$label ?? class_basename(static::class);
    }
}
