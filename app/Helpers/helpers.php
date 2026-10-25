<?php

use Carbon\Carbon;

if (!function_exists('format_date')) {
    /**
     * Tarih formatını düzenler.
     *
     * @param  string  $date
     * @return string
     */
    function format_date($date)
    {
        return Carbon::parse($date)->format('d-m-Y');
    }
}




if (!function_exists("getLogDetails")) {
    function getLogDetails()
    {
        $request = app('request');
        return [

            // User datalari
            'user_id'       => auth()->check() ? auth()->user()->uid : null,
            'user_name'     => auth()->check() ? auth()->user()->full_info : null,

            // HTTP ve Route datalari
            'http_method'   => $request->method(),
            'route'         => $request->route() ? $request->route()->getName() : null,
            'full_url'      => $request->fullUrl() ?? null,

            'headers'       => collect($request->headers->all())
                ->except(['cookie', 'authorization', 'x-api-key', 'x-csrf-token'])
                ->toArray(),

            // User agent bilgisi (Brovser ve cihaz)
            'user_agent'    => $request->header('User-Agent') ?? null,

            // Referer bilgisi
            'referer'       => $request->header('referer') ?? null,

            // Sessia ID'si
            'session_id'    => session()->getId() ?? null,

            // O anki sehife
            'current_page'  => $request->path() ?? null,

            // Loglama timestamp
            'timestamp'     => now(),
            // Umumi gelen requestin tamami
            'request'       => $request->except(config("custom.logging.sensitiveKeys")),
        ];
    }
}



if (!function_exists("getErrorCodeException")) {
    function getErrorCodeException($e)
    {
        $code = is_numeric($e->getCode()) ? $e->getCode() : 500;
        return ($code > 600 || $code < 1) ? 500 : $code;
    }
}



if (!function_exists("get_cookie")) {
    function get_cookie($key)
    {
        if (!isset($_COOKIE[$key]))
            return null;
        return $_COOKIE[$key];
    }
}



if (!function_exists("active_menu")) {
    function active_menu($key, $value)
    {
        return $key == $value ? 'active' : '';
    }
}


if (!function_exists("convertToCollection")) {
    function convertToCollection($value)
    {
        if (is_array($value)) {
            return collect($value)->map(function ($item) {
                if (is_array($item)) {
                    return convertToCollection($item);
                }
                return $item;
            });
        }
        return $value;
    }
}


if (!function_exists("convertAzerbaijaniToEnglish")) {
    function convertAzerbaijaniToEnglish($text)
    {
        // Mapping of Azerbaijani characters to English equivalents
        $replacements = [
            'ü' => 'u',
            'ö' => 'o',
            'ğ' => 'g',
            'ç' => 'c',
            'ş' => 's',
            'ı' => 'i',
            'ə' => 'e',
            'ş' => 's',
            'ç' => 'c',
            'ñ' => 'n',
        ];

        // Replace the Azerbaijani characters with their English equivalents
        $text = strtr($text, $replacements);
        return $text; // Return the modified text
    }
}



if (!function_exists("formatPhoneNumber")) {
    function formatPhoneNumber($number)
    {
        $number = preg_replace('/[^0-9]/', '', $number);

        if (substr($number, 0, 1) == '0') {
            $number = substr($number, 1);
        }

        if (substr($number, 0, 3) != '994') {
            $number = '994' . $number;
        }

        return $number;
    }
}


if (!function_exists("full_sql")) {
    function full_sql($query)
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        $fullQuery = vsprintf(str_replace('?', '%s', $sql), $bindings);
        return $fullQuery;
    }
}


if (!function_exists("setLocaleInUrl")) {
    function setLocaleInUrl($locale, $url = null)
    {
        if (!$url) {
            $url = url()->current();
        }
        $parsedUrl = parse_url($url);
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';
        $segments = explode('/', trim($path, '/'));
        $segments[0] = $locale;
        $newPath = implode('/', $segments);
        $newUrl = url($newPath);
        return $newUrl;
    }
}



if (!function_exists("transction_id")) {
    function transction_id($prefix = 'GP', $customer_id = '', $customer_number = '')
    {
        $alphabet = range('A', 'Z');
        $random_letter = $alphabet[array_rand($alphabet)];
        $customer_id = $customer_id ?: 'G' . rand(1, 9) . 'G';
        $customer_number = $customer_number ?: 'Q' . rand(1, 9) . 'Q';

        return $prefix . time() . "U" . $customer_id . "C" . $customer_number . $random_letter;
    }
}


if (!function_exists("get_today")) {
    function get_today($locale = 'az')
    {
        setlocale(LC_TIME, 'az_AZ.UTF-8');
        Carbon::setLocale($locale);
        $date = Carbon::now();
        return $date->translatedFormat('d F Y');
    }
}


if (!function_exists('markdown_url')) {
    /**
     * Cari (və ya verilmiş) səhifənin Markdown variantının tam ünvanı.
     *
     * Qayda: canonical yolun sonuna `.md` (`/az/bloqlar` → `/az/bloqlar.md`).
     * Sorğu parametri və `#fraqment` daşınmır - Markdown sənədi statik məzmundur.
     *
     * Səhifənin Markdown variantı yoxdursa (panel, texniki ünvanlar, tanınmayan
     * yol, deaktiv qeyd) `null` qaytarılır - beləliklə `<head>`-də və `Link:`
     * başlığında heç vaxt 404 verən keçid çap olunmur. Cari sorğu üçün nəticə
     * request daxilində yadda saxlanılır (middleware + head eyni yoxlamanı təkrarlamasın).
     *
     * @see \App\Services\Seo\Markdown\MarkdownPageService
     */
    function markdown_url(?string $url = null): ?string
    {
        $service = app(\App\Services\Seo\Markdown\MarkdownPageService::class);
        $path    = trim((string) (parse_url($url ?? request()->getPathInfo(), PHP_URL_PATH) ?? ''), '/');
        $split   = $service->splitPath($path);

        if ($split === null) {
            return null;
        }

        $memoKey  = 'seo.markdown_url.' . $path;
        $memo     = $url === null ? request()->attributes : null;

        if ($memo !== null && $memo->has($memoKey)) {
            return $memo->get($memoKey);
        }

        $result = $service->supports($split[0], $split[1]) ? url($path) . '.md' : null;

        $memo?->set($memoKey, $result);

        return $result;
    }
}


if (!function_exists('asset_v')) {
    /**
     * Versiyalanmış asset ünvanı - faylın dəyişmə vaxtı ilə (`?v=filemtime`).
     *
     * Niyə: `asset('...js?v=' . time())` hər səhifə açılışında yeni ünvan yaradır
     * və brauzer keşi heç vaxt işləmir; versiyasız ünvanda isə deploy-dan sonra
     * istifadəçi köhnə JS/CSS ilə qalır. Fayl dəyişəndə ünvan dəyişir, dəyişməyəndə
     * keşdən gəlir.
     *
     * @param  string  $path  public/ qovluğundan başlayan yol
     */
    function asset_v(string $path): string
    {
        $clean = ltrim(parse_url($path, PHP_URL_PATH) ?? $path, '/');
        $file  = public_path($clean);

        // Fayl tapılmasa (CDN/simvolik yol) versiyasız qaytarılır
        $version = is_file($file) ? (string) filemtime($file) : null;

        return $version === null ? asset($clean) : asset($clean) . '?v=' . $version;
    }
}


if (!function_exists('form_date')) {
    /**
     * Tarixi `<input type="date">` üçün `Y-m-d` formatına salır.
     *
     * Niyə: model `datetime` cast-ı ilə Carbon qaytarır, input isə yalnız `Y-m-d`
     * qəbul edir. `{{ $item->start_date }}` yazılanda «2026-05-01 00:00:00» düşür,
     * brauzer onu tanımır - sahə boş görünür, redaktədə tarix səssizcə itir.
     */
    function form_date(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return substr((string) $value, 0, 10);
    }
}


if (!function_exists('selected')) {
    /** `<option {{ selected($a, $b) }}>` - Blade-in `@selected` direktivi işləməyən yerlər üçün. */
    function selected($key, $value): string
    {
        return $key == $value ? 'selected' : '';
    }
}


if (!function_exists('file_url')) {
    /**
     * Yüklənmiş faylın tam URL-i - fayl hansı diskdədirsə (storage və ya public).
     *
     * Niyə: `asset($path)` faylı yalnız `public/` altında axtarır, `toStorage()`
     * ilə yüklənən fayl üçün 404 verən ünvan qurur; `toPublic()` ilə yazılmış
     * köhnə `site/...` yolları isə storage-da yoxdur. Disk bazadakı yoldan
     * bilinmir, ona görə hər ikisi yoxlanılır (`FileUploader::url(..., 'auto')`).
     *
     * Fayl tapılmasa `$fallback` (məs. placeholder şəkil) qaytarılır.
     */
    function file_url(?string $path, ?string $fallback = null): ?string
    {
        return \App\Helpers\Gopanel\FileUploader::url($path, \App\Helpers\Gopanel\FileUploader::DISK_AUTO) ?? $fallback;
    }
}
