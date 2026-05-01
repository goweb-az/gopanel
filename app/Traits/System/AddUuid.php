<?php

namespace App\Traits\System;

use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait AddUuid
{
    protected static function bootAddUuid()
    {
        static::creating(function ($model) {
            if (empty($model->uid) && in_array('uid', $model->getFillable())) {
                $model->uid = (string) Str::uuid()->toString();
            }
        });
    }


    public static function findByUid($uid)
    {
        $item = self::where("uid", $uid)->first();
        if (!$item)
            throw new NotFoundHttpException("Məlumat tapılmadı");
        return $item;
    }
}
