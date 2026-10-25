<?php

declare(strict_types=1);

namespace App\Support\Export;

/**
 * Xam süzgəc massivini sənəd başlığındakı oxunaqlı sətrə çevirir.
 *
 * NİYƏ: ixrac faylını açan adam hansı kəsimə baxdığını görməlidir - fayl
 * mail ilə ötürüləndə «bu hansı tarixin siyahısıdır?» sualı yaranmasın.
 *
 * Burada yalnız ÜMUMİ açarlar var. Layihəyə xas sahələr (`warehouse_id`
 * və s.) `config/custom/export.php` → `filter_labels`-ə yazılır, və ya
 * çağırışda `$labels` ilə ötürülür; ikisi də daxili siyahını üstələyir.
 */
final class ExportFilterLabels
{
    /** DataTables-in, səhifələmənin və formun texniki parametrləri sənədə düşmür. */
    private const IGNORED = [
        'draw',
        'start',
        'length',
        'order',
        'columns',
        'page',
        'per_page',
        '_',
        '_token',
        'format',
        'key',
        'show_columns',
        'dumpsql',
    ];

    private const LABELS = [
        'search'      => 'Axtarış',
        'q'           => 'Axtarış',
        'status'      => 'Status',
        'status_id'   => 'Status',
        'is_active'   => 'Aktivlik',
        'type'        => 'Növ',
        'source'      => 'Mənbə',
        'category_id' => 'Kateqoriya',
        'user_id'     => 'İstifadəçi',
        'admin_id'    => 'Admin',
        'causer_id'   => 'İcra edən',
        'event'       => 'Əməliyyat',
        'log_name'    => 'Model',
        'date_from'   => 'Başlanğıc',
        'date_to'     => 'Bitmə',
        'from'        => 'Başlanğıc',
        'to'          => 'Bitmə',
        'min_amount'  => 'Minimum məbləğ',
        'max_amount'  => 'Maksimum məbləğ',
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $labels  açar → ad (config və daxili siyahını üstələyir)
     * @param  array<string, array<int|string, string>>  $values  açar → [xam dəyər → oxunaqlı dəyər]
     *                                                             (məs. `status` => [1 => 'Aktiv'])
     * @return array<string, string>
     */
    public static function describe(array $filters, array $labels = [], array $values = []): array
    {
        $map = array_merge(self::LABELS, (array) config('custom.export.filter_labels', []), $labels);
        $described = [];

        foreach ($filters as $key => $value) {
            if (in_array($key, self::IGNORED, true)) {
                continue;
            }

            if (is_array($value)) {
                $value = implode(', ', array_filter($value, 'is_scalar'));
            }

            if ($value === null || $value === '') {
                continue;
            }

            // `true`/`false` massiv açarı ola bilmir - 1/0-a çevrilir.
            $raw = is_bool($value) ? (int) $value : $value;
            $display = $values[$key][$raw] ?? (string) $raw;

            $described[$map[$key] ?? (string) $key] = (string) $display;
        }

        return $described;
    }
}
