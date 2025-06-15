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

            'headers'       => $request->headers->all() ?? [],

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
    function transction_id($prefix = 'QRG', $customer_id = '', $customer_number = '')
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
