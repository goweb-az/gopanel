<?php
// app/Traits/HasUuids.php

namespace App\Traits;

use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait AddUuid
{
    protected static function bootAddUuid()
    {
        // static::addGlobalScope(new UuidScope);
        static::creating(function ($model) {
            if (empty($model->uid) && in_array('uid', $model->getFillable())) {
                $model->uid = (string) Str::uuid()->toString();
            }
        });
    }


    public static function findByUid($uid)
    {
        $item = self::where("uid", $uid)->first();
        // \DB::enableQueryLog();
        // $item = self::where("uid", $uid)->first();
        // dd(\DB::getQueryLog(), $item);
        if (!$item)
            throw new NotFoundHttpException("Məlumat tapılmadı");
        return $item;
    }
}
