<?php

declare(strict_types=1);

namespace App\Traits\System;

use Illuminate\Database\QueryException;

/**
 * İnsan üçün oxunaqlı, ardıcıl artan nömrə (sifariş nömrəsi, müştəri nömrəsi...).
 *
 * `uid` maşın üçündür - bu nömrə isə müştəriyə deyilir, dəstək danışığında
 * səslənir və çapa düşür. Ona görə qısa, rəqəmli və artan olur.
 *
 * Nömrə `creating` anında verilir; paralel yazıda unikal indeks toqquşarsa
 * növbəti dəyər yenidən hesablanır.
 */
trait HasSerialNumber
{
    /** Nömrənin saxlandığı sütun. */
    abstract protected static function serialNumberColumn(): string;

    /** Cədvəl boş olanda veriləcək ilk nömrə. */
    abstract protected static function serialNumberStart(): int;

    public static function bootHasSerialNumber(): void
    {
        static::creating(function ($model): void {
            $column = static::serialNumberColumn();

            if (blank($model->{$column})) {
                $model->{$column} = static::nextSerialNumber();
            }
        });
    }

    /**
     * Növbəti nömrə: ən böyük mövcud dəyər + 1.
     *
     * Silinmiş və arxivlənmiş sətirlər də hesaba alınır (`withoutGlobalScopes`),
     * çünki nömrə təkrar istifadə edilməməlidir - köhnə sənəddə qalıb.
     */
    public static function nextSerialNumber(): int
    {
        $max = (int) static::query()
            ->withoutGlobalScopes()
            ->max(static::serialNumberColumn());

        return $max > 0 ? $max + 1 : static::serialNumberStart();
    }

    /**
     * Unikal indeks toqquşarsa nömrəni yenidən hesablayıb saxlamağa çalışır.
     * Paralel iki yazıda eyni `max()` oxunması yeganə real riskdir.
     */
    public function save(array $options = [])
    {
        $column = static::serialNumberColumn();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return parent::save($options);
            } catch (QueryException $exception) {
                // Yalnız bu sütunun unikal indeksi pozulubsa yenidən cəhd edilir;
                // başqa hər hansı SQL xətası olduğu kimi yuxarı qalxır.
                $isDuplicate = (string) $exception->getCode() === '23000'
                    && str_contains((string) $exception->getMessage(), $column);

                if (! $isDuplicate) {
                    throw $exception;
                }

                $this->{$column} = static::nextSerialNumber();
            }
        }

        return parent::save($options);
    }
}
