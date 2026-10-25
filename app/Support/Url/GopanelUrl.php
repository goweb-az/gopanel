<?php

declare(strict_types=1);

namespace App\Support\Url;

use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Facades\URL;

/**
 * İdarə panelinin (GoPanel) ünvanı.
 *
 * Niyə lazımdır: canlıda panel öz subdomenindədir (`GOPANEL_URL`, məs.
 * `https://go.example.dev`), `APP_URL` isə sayt domenidir. Laravel `route()`
 * ünvanı **cari sorğunun host-undan** qurur; növbədə (queue) sorğu yoxdur və
 * `APP_URL` işlədilir.
 *
 * Nəticədə növbədən gedən admin bildirişindəki/emailindəki keçid sayt
 * domeninə gedirdi: orada panel sessiyası yoxdur, ona görə admin ya giriş
 * səhifəsinə düşürdü, ya da `GopanelHostMiddleware` 404 verirdi.
 *
 * Növbədən/emaildən gedən BÜTÜN panel ünvanları buradan qurulur:
 *
 *     GopanelUrl::route('gopanel.blogs.index')
 *
 * `GOPANEL_URL` boşdursa (lokal tək domenli quraşdırma) `APP_URL` işlədilir -
 * davranış dəyişmir.
 *
 * @see \App\Http\Middleware\Gopanel\GopanelHostMiddleware
 */
final class GopanelUrl
{
    /** Panelin kök ünvanı - sondakı `/` olmadan. */
    public static function base(): string
    {
        $configured = trim((string) config('app.gopanel_url'));

        return rtrim($configured !== '' ? $configured : (string) config('app.url'), '/');
    }

    /** Panelin daxili ünvanı: `GopanelUrl::to('gopanel/sales/orders')`. */
    public static function to(?string $path = null): string
    {
        $path = trim((string) $path, '/');

        return $path === '' ? static::base() : static::base() . '/' . $path;
    }

    /**
     * Route adından panel ünvanı.
     *
     * Yol **nisbi** qurulur (`absolute: false`), sonra panelin kökü qabağına
     * qoyulur - beləliklə nəticə nə cari sorğunun host-undan, nə də `APP_URL`-dən
     * asılı olur.
     *
     * Route yoxdursa `null` qaytarılır: bildiriş ünvansız da göndərilməlidir,
     * yazılış xətası bütün bildirişi uduzmamalıdır.
     *
     * @param  mixed  $parameters
     */
    public static function route(string $name, $parameters = []): ?string
    {
        if (! RouteFacade::has($name)) {
            return null;
        }

        return static::to(URL::route($name, $parameters, false));
    }
}
