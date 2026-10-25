<?php

declare(strict_types=1);

namespace App\Services\Gopanel\Lookup;

use Illuminate\Support\Str;

/**
 * `config/gopanel/lookups.php` reyestrinin oxunması.
 *
 * NİYƏ ayrıca sinif:
 * Eyni reyestr dörd yerdə işlədilir - route-lar, icazə siyahısı, əsas sol
 * menyu və bölmədaxili sidebar. Hər yer massivi özü gəzsəydi, icazə adının
 * formatı (`gopanel.lookups.{tip}.{əməliyyat}`) dörd yerdə təkrarlanardı və
 * biri dəyişəndə route ilə icazə ayrışardı.
 *
 * İcazə və sidebar config fayllarının İÇİNDƏN çağırılır - o anda `config()`
 * hələ tam yüklənməyib. Ona görə həmin metodlar xam massivi arqument kimi
 * qəbul edir (`require __DIR__ . '/lookups.php'`), runtime metodları isə
 * `config('gopanel.lookups')` oxuyur. Sinif vəziyyətsizdir.
 */
final class LookupRegistry
{
    /** Route/icazə prefiksi. Route adı ilə icazə adı EYNİDİR (01-umumi.md § 5). */
    public const PREFIX = 'gopanel.lookups';

    /** Hər tipin icazələri: əməliyyat => başlıq sonluğu. */
    public const ACTIONS = [
        'index'  => 'siyahısı',
        'add'    => 'əlavə etmə',
        'edit'   => 'redaktə',
        'delete' => 'silmə',
        'sort'   => 'sıralama',
    ];

    /**
     * Normallaşdırılmış tiplər: açar => ['title', 'controller', 'icon'].
     *
     * Yanlış yazılmış açar (böyük hərf, boşluq) və controller-i olmayan sətir
     * sükutla ötürülür - əks halda bir səhv sətir bütün paneli (route
     * qeydiyyatını) yıxardı.
     *
     * @param  array<string, mixed>|null  $config  xam config; null = runtime config
     * @return array<string, array{title: string, controller: string, icon: string}>
     */
    public static function types(?array $config = null): array
    {
        $config ??= (array) config('gopanel.lookups', []);
        $types = [];

        foreach ((array) ($config['types'] ?? []) as $type => $entry) {
            if (!is_string($type) || !preg_match('/^[a-z][a-z0-9-]*$/', $type) || !is_array($entry)) {
                continue;
            }

            if (empty($entry['controller']) || !is_string($entry['controller'])) {
                continue;
            }

            $types[$type] = [
                'title'      => (string) ($entry['title'] ?? Str::headline($type)),
                'controller' => $entry['controller'],
                'icon'       => (string) ($entry['icon'] ?? 'bx bx-list-ul'),
            ];
        }

        return $types;
    }

    /** @return array{title: string, controller: string, icon: string}|null */
    public static function find(string $type): ?array
    {
        return self::types()[$type] ?? null;
    }

    /** `gopanel.lookups.{tip}` - həm route adı prefiksi, həm icazə prefiksi. */
    public static function routeName(string $type): string
    {
        return self::PREFIX . '.' . $type;
    }

    public static function permission(string $type, string $action): string
    {
        return self::routeName($type) . '.' . $action;
    }

    /**
     * Bütün tiplərin `index` icazələri - bölmənin özünə giriş «hər hansı biri»dir.
     *
     * @param  array<string, mixed>|null  $config
     * @return list<string>
     */
    public static function indexPermissions(?array $config = null): array
    {
        return array_map(
            fn (string $type) => self::permission($type, 'index'),
            array_keys(self::types($config)),
        );
    }

    /**
     * `permission_list.php`-ə qoşulan qruplar - hər tip öz qrupunda.
     *
     * @param  array<string, mixed>  $config  xam `lookups.php` massivi
     * @return array<string, list<array{name: string, title: string}>>
     */
    public static function permissionGroups(array $config): array
    {
        $groups = [];
        $section = (string) ($config['menu']['title'] ?? 'Status və Tiplər');

        foreach (self::types($config) as $type => $entry) {
            $rows = [];

            foreach (self::ACTIONS as $action => $suffix) {
                $rows[] = [
                    'name'  => self::permission($type, $action),
                    'title' => $entry['title'] . ' ' . $suffix,
                ];
            }

            $groups[$section . ': ' . $entry['title']] = $rows;
        }

        return $groups;
    }

    /**
     * Əsas sol menyunun elementi. Reyestr boşdursa boş massiv - starter-də
     * «Status və Tiplər» heç nəyə aparmayan menyu kimi görünməsin.
     *
     * Hər tip alt sətirdir və öz `index` icazəsini daşıyır: GoPanelSidebar
     * qrupu yalnız ən azı bir alt sətir görünəndə açır və aktiv route-da
     * qrupu açıq göstərir (spec § 2). Tiplər arası keçid həm də bölmədaxili
     * sidebar-dadır.
     *
     * @param  array<string, mixed>  $config  xam `lookups.php` massivi
     * @return list<array<string, mixed>>
     */
    public static function sidebarItems(array $config): array
    {
        $types = self::types($config);

        if ($types === []) {
            return [];
        }

        $inner = [];

        foreach ($types as $type => $entry) {
            $inner[] = [
                'icon'  => '<i class="' . e($entry['icon']) . '"></i>',
                'title' => $entry['title'],
                'route' => self::routeName($type) . '.index',
                'can'   => self::permission($type, 'index'),
            ];
        }

        return [[
            'icon'  => (string) ($config['menu']['icon'] ?? '<i class="bx bx-category-alt"></i>'),
            'title' => (string) ($config['menu']['title'] ?? 'Status və Tiplər'),
            'can'   => '*',
            'inner' => $inner,
        ]];
    }
}
