<?php

namespace App\Traits\System;

trait HasRouteKey
{
    /**
     * Modelin identifikator açarını qaytarır.
     * uid fillable-da varsa uid, yoxdursa id istifadə edir.
     */
    public function getIdentifierIdAttribute()
    {
        if (in_array('uid', $this->getFillable()) && !empty($this->uid)) {
            return $this->uid;
        }
        return $this->id;
    }

    /**
     * Gələn dəyərin uid və ya id olduğunu müəyyən edib modeli tapır.
     */
    public static function resolveByKey($value)
    {
        $instance = new static();
        $column = (in_array('uid', $instance->getFillable()) && !is_numeric($value)) ? 'uid' : 'id';
        return static::where($column, $value)->first();
    }
}
