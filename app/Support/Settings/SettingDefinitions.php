<?php

declare(strict_types=1);

namespace App\Support\Settings;

/**
 * Tipli key-value tənzimləmə təriflərinin ortaq bazası.
 *
 * Niyə: tənzimləmənin tipi, default-u, etiketi və validasiyası BİR yerdə -
 * config-də elan olunur. Forma, FormRequest qaydaları, DB-yə yazma və oxuma
 * eyni tərifdən qurulur; beləliklə «formada max 100, serverdə max 50» kimi
 * uyğunsuzluq yaranmır və yeni açar üçün miqrasiya lazım olmur.
 *
 * Konkret sinif yalnız config açarını verir:
 *
 *   final class ShopSettingDefinitions extends SettingDefinitions
 *   {
 *       protected static function configKey(): string { return 'custom.shop_settings'; }
 *   }
 *
 * Tərif formatı (`key => [...]`):
 *
 *   'type'    => 'bool' | 'int' | 'float' | 'string' | 'text' | 'select' | 'json'
 *   'default' => native dəyər (sətir yoxdursa qaytarılır)
 *   'label'   => formada görünən ad (Azərbaycanca)
 *   'hint'    => izah sətri (istəyə bağlı)
 *   'group'   => formada başlıq (default «Ümumi»)
 *   'rules'   => Laravel validasiya qaydaları - verilsə tipdən qurulanı ƏVƏZ edir
 *   'min' / 'max' => int/float və string uzunluğu üçün sərhəd
 *   'options' => select üçün [dəyər => etiket]
 *
 * DB-də yalnız xam mətn saxlanılır; tipə görə çevirmə burada aparılır.
 */
abstract class SettingDefinitions
{
    public const TYPES = ['bool', 'int', 'float', 'string', 'text', 'select', 'json'];

    public const DEFAULT_GROUP = 'Ümumi';

    /** Təriflərin yerləşdiyi config açarı (məs. `gopanel.admin_settings`). */
    abstract protected static function configKey(): string;

    /**
     * `setting_values.scope` dəyəri - təriflər dəstini ayırır.
     *
     * Default config açarıdır; config faylının yeri dəyişsə də köhnə sətirlər
     * itməsin deyə törəmə sinif onu sabit sətirlə üstələyə bilər.
     */
    public static function scope(): string
    {
        return static::configKey();
    }

    /** @return array<string, array<string, mixed>> Bütün təriflər */
    public static function all(): array
    {
        return array_filter((array) config(static::configKey(), []), 'is_array');
    }

    /** @return list<string> Açar adları */
    public static function keys(): array
    {
        return array_keys(static::all());
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, static::all());
    }

    /** Bir açarın default dəyəri (tərif yoxdursa null). */
    public static function default(string $key): mixed
    {
        return static::all()[$key]['default'] ?? null;
    }

    /** @return array<string, mixed> key => default */
    public static function defaults(): array
    {
        return array_map(fn (array $item) => $item['default'] ?? null, static::all());
    }

    public static function type(string $key): string
    {
        $type = (string) (static::all()[$key]['type'] ?? 'string');

        return in_array($type, self::TYPES, true) ? $type : 'string';
    }

    /**
     * DB-dəki xam dəyəri tərifdəki tipə çevirir.
     * `null` (sətir yoxdur / dəyər boşdur) → default.
     */
    public static function cast(string $key, ?string $raw): mixed
    {
        if ($raw === null) {
            return static::default($key);
        }

        return match (static::type($key)) {
            'bool'  => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'int'   => (int) $raw,
            'float' => (float) $raw,
            'json'  => json_decode($raw, true),
            default => $raw,
        };
    }

    /** Native dəyəri DB-yə yazılacaq xam mətnə çevirir. */
    public static function toStorage(string $key, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match (static::type($key)) {
            'bool'  => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'int'   => (string) (int) $value,
            'float' => (string) (float) $value,
            'json'  => json_encode($value, JSON_UNESCAPED_UNICODE) ?: null,
            default => (string) $value,
        };
    }

    /**
     * Formadan (validasiyadan sonra) gələn dəyərləri native tipə çevirir.
     * Açarlar YALNIZ tərif siyahısından götürülür - kənar açar yazılmır.
     *
     * Boş sətir `null` olur → sətir default-a qayıdır. Bool üçün göndərilməmiş
     * açar toxunulmur (forma `hidden 0 + checkbox 1` cütü göndərir).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function fromForm(array $validated): array
    {
        $data = [];

        foreach (static::keys() as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }

            $value = $validated[$key];

            $data[$key] = match (static::type($key)) {
                'bool'  => filter_var($value ?? false, FILTER_VALIDATE_BOOLEAN),
                'int'   => $value === null || $value === '' ? null : (int) $value,
                'float' => $value === null || $value === '' ? null : (float) $value,
                'json'  => is_string($value) ? ($value === '' ? null : json_decode($value, true)) : $value,
                default => $value === '' ? null : $value,
            };
        }

        return $data;
    }

    /**
     * FormRequest üçün validasiya qaydaları.
     *
     * Tərifdə `rules` varsa o götürülür, yoxsa qayda tipdən qurulur; `min`/`max`
     * da nəzərə alınır ki, formadakı sərhəd ilə serverdəki yoxlama eyni mənbədən gəlsin.
     *
     * @param  string  $prefix  forma massivinin adı (`settings` → `settings.key`)
     * @return array<string, array<int, mixed>>
     */
    public static function validationRules(string $prefix = ''): array
    {
        $rules = [];
        $prefix = $prefix === '' ? '' : rtrim($prefix, '.') . '.';

        foreach (static::all() as $key => $item) {
            $rules[$prefix . $key] = isset($item['rules']) && is_array($item['rules'])
                ? $item['rules']
                : self::rulesFor($item);
        }

        return $rules;
    }

    /** @return array<string, string> validasiya mesajlarındakı sahə adları */
    public static function attributeLabels(string $prefix = ''): array
    {
        $prefix = $prefix === '' ? '' : rtrim($prefix, '.') . '.';
        $labels = [];

        foreach (static::all() as $key => $item) {
            $labels[$prefix . $key] = (string) ($item['label'] ?? $key);
        }

        return $labels;
    }

    /**
     * Forma sətirləri - blade yalnız çap edir, heç nə hesablamır.
     *
     * @param  array<string, mixed>  $values  key => cast olunmuş dəyər
     * @return list<array<string, mixed>>
     */
    public static function formRows(array $values): array
    {
        $rows = [];

        foreach (static::all() as $name => $item) {
            $type  = static::type($name);
            $value = array_key_exists($name, $values) ? $values[$name] : ($item['default'] ?? null);

            $rows[] = [
                'name'    => $name,
                'type'    => $type,
                'input'   => self::inputFor($type),
                'label'   => (string) ($item['label'] ?? $name),
                'hint'    => (string) ($item['hint'] ?? ''),
                'group'   => (string) ($item['group'] ?? self::DEFAULT_GROUP),
                'checked' => $type === 'bool' && (bool) $value,
                'value'   => self::displayValue($type, $value),
                'options' => (array) ($item['options'] ?? []),
                'min'     => $item['min'] ?? null,
                'max'     => $item['max'] ?? null,
                'step'    => $type === 'float' ? 'any' : '1',
            ];
        }

        return $rows;
    }

    /**
     * Sətirlər `group` üzrə qruplaşdırılmış - formada başlıq altında.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, list<array<string, mixed>>>  group => rows
     */
    public static function groupedFormRows(array $values): array
    {
        $groups = [];

        foreach (static::formRows($values) as $row) {
            $groups[$row['group']][] = $row;
        }

        return $groups;
    }

    /** @return array<int, mixed> */
    private static function rulesFor(array $item): array
    {
        $type = (string) ($item['type'] ?? 'string');
        $bounds = array_merge(
            isset($item['min']) ? ['min:' . $item['min']] : [],
            isset($item['max']) ? ['max:' . $item['max']] : [],
        );

        return match ($type) {
            'bool'   => ['nullable', 'boolean'],
            'int'    => array_merge(['nullable', 'integer'], $bounds),
            'float'  => array_merge(['nullable', 'numeric'], $bounds),
            // Formada textarea-dır - JSON mətni gəlir, `fromForm()` onu açır.
            'json'   => ['nullable', 'json'],
            'select' => ['nullable', 'string', 'in:' . implode(',', array_map('strval', array_keys((array) ($item['options'] ?? []))))],
            'text'   => array_merge(['nullable', 'string'], $bounds ?: ['max:5000']),
            default  => array_merge(['nullable', 'string'], $bounds ?: ['max:255']),
        };
    }

    private static function inputFor(string $type): string
    {
        return match ($type) {
            'bool'         => 'switch',
            'int', 'float' => 'number',
            'text', 'json' => 'textarea',
            'select'       => 'select',
            default        => 'text',
        };
    }

    /** Input-un `value` atributu üçün mətn (json → oxunaqlı JSON). */
    private static function displayValue(string $type, mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($type === 'json') {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
