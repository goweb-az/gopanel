<?php

namespace App\Helpers\Gopanel;

use Illuminate\Support\Facades\Cache;

/**
 * Parses /public/assets/gopanel/css/icons.min.css and returns icon class lists
 * for the global icon picker modal (Font Awesome, Boxicons, Material Design,
 * Dripicons). Result is cached for 7 days because the CSS is static.
 */
class IconPickerHelper
{
    private const CACHE_KEY = 'gopanel.icon_picker.list.v2';
    private const CACHE_TTL = 60 * 60 * 24 * 7;

    /**
     * Provider regex patterns and the prefix prepended to each match.
     * Order is preserved in the response.
     */
    private const PROVIDERS = [
        'fa'  => null, // FA needs special handling (brand vs regular vs solid)
        'bx'  => ['pattern' => '/\.(bx-[a-z0-9-]+|bxs-[a-z0-9-]+|bxl-[a-z0-9-]+)::?before\{content:/', 'prefix' => 'bx '],
        'mdi' => ['pattern' => '/\.(mdi-[a-z0-9-]+)::?before\{content:/',                              'prefix' => 'mdi '],
        'drp' => ['pattern' => '/\.(dripicons-[a-z0-9-]+)::?before\{content:/',                        'prefix' => ''],
    ];

    /**
     * Returns a flat list of every icon class string supported by the picker,
     * grouped by provider key:
     *
     *     [
     *         'fa'  => ['fas fa-home', 'fab fa-github', ...],
     *         'bx'  => ['bx bx-home', 'bx bxs-bell', 'bx bxl-github', ...],
     *         'mdi' => ['mdi mdi-home', ...],
     *         'drp' => ['dripicons-anchor', ...],
     *     ]
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $css = self::loadCss();

            $out = ['fa' => self::extractFa($css)];

            foreach (self::PROVIDERS as $key => $config) {
                if ($config === null) {
                    continue;
                }
                $out[$key] = self::extractByPattern($css, $config['pattern'], $config['prefix']);
            }

            return $out;
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function loadCss(): string
    {
        $cssPath = public_path('assets/gopanel/css/icons.min.css');

        return is_readable($cssPath) ? file_get_contents($cssPath) : '';
    }

    /**
     * Generic regex extractor — matches the first capture group of $pattern,
     * dedupes, sorts and prepends $prefix to each result.
     */
    private static function extractByPattern(string $css, string $pattern, string $prefix): array
    {
        preg_match_all($pattern, $css, $m);

        $unique = array_values(array_unique($m[1]));
        $out    = array_map(fn ($cls) => $prefix . $cls, $unique);

        sort($out);
        return $out;
    }

    /**
     * Font Awesome needs its own pass because each base class can render with
     * fas / far / fab depending on the icon name. The brand and regular
     * subsets live in config/gopanel/font_awesome_icons.php.
     */
    private static function extractFa(string $css): array
    {
        preg_match_all('/\.(fa-[a-z0-9-]+)::?before\{content:/', $css, $m);

        $brands  = array_flip(config('gopanel.font_awesome_icons.brands', []));
        $regular = array_flip(config('gopanel.font_awesome_icons.regular', []));

        $out = [];
        foreach (array_unique($m[1]) as $cls) {
            $name = substr($cls, 3); // strip "fa-" prefix

            if (isset($brands[$name])) {
                $out[] = 'fab ' . $cls;
            } elseif (isset($regular[$name])) {
                $out[] = 'fas ' . $cls;
                $out[] = 'far ' . $cls;
            } else {
                $out[] = 'fas ' . $cls;
            }
        }

        sort($out);
        return array_values(array_unique($out));
    }
}
