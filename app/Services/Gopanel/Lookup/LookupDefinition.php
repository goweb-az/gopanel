<?php

declare(strict_types=1);

namespace App\Services\Gopanel\Lookup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Bir lookup tipinin təsviri: hansı model, hansı əlavə sahələr, hansı bayraqlar.
 *
 * NİYƏ massiv deyil, sinif:
 * Eyni təsviri controller, servis, index və forma blade-i oxuyur. Massivdə
 * açar adı səhv yazılanda (`extra_fields` / `extraFields`) xəta sükutla boş
 * siyahıya çevrilirdi. Burada sahələr typed-dir və «sahə varmı», «bayrağın
 * adı nədir» kimi suallar blade-ə hazır cavab kimi gedir (blade-də məntiq
 * yoxdur - 01-umumi.md § 3).
 */
final class LookupDefinition
{
    /**
     * Tanınan bayraqların panel adları. Siyahıda olmayan bayraq
     * `Str::headline()` ilə göstərilir - yeni bayraq üçün bura yazmaq məcburi deyil.
     */
    public const BOOLEAN_LABELS = [
        'is_active'  => 'Aktiv',
        'is_default' => 'Default',
        'is_public'  => 'İctimai',
    ];

    /**
     * Yeni sətirdə hansı bayraq açıq gəlir. Yalnız `is_active` - bütün
     * checkbox-ları avtomatik açmaq yeni statusu səhvən default/ictimai edir.
     */
    public const BOOLEAN_DEFAULTS = [
        'is_active' => true,
    ];

    /** Tanınan əlavə sahələrin panel adları. */
    public const FIELD_LABELS = [
        'color'      => 'Rəng',
        'class_name' => 'CSS sinfi',
        'icon'       => 'İkon',
    ];

    /**
     * @param  class-string<Model>  $model
     * @param  list<string>  $extraFields  `color`, `class_name`, `icon`...
     * @param  list<string>  $booleans     `is_default`, `is_public`, `is_active`...
     * @param  list<string>  $translated   `name`, `description`...
     */
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $model,
        public readonly array $extraFields = [],
        public readonly array $booleans = [],
        public readonly array $translated = [],
    ) {
    }

    /** `gopanel.lookups.{tip}` */
    public function routeName(): string
    {
        return LookupRegistry::routeName($this->type);
    }

    public function route(string $action, mixed $parameters = []): string
    {
        return route($this->routeName() . '.' . $action, $parameters);
    }

    public function permission(string $action): string
    {
        return LookupRegistry::permission($this->type, $action);
    }

    public function hasField(string $field): bool
    {
        return in_array($field, $this->extraFields, true);
    }

    public function hasBoolean(string $field): bool
    {
        return in_array($field, $this->booleans, true);
    }

    public function isTranslated(string $field): bool
    {
        return in_array($field, $this->translated, true);
    }

    /** Index-də badge önizləməsi göstərilsinmi (rəng və ya hazır CSS sinfi). */
    public function hasPreview(): bool
    {
        return $this->hasField('color') || $this->hasField('class_name');
    }

    /** Index-də `is_active` keçid düyməsi göstərilsinmi. */
    public function hasActiveToggle(): bool
    {
        return $this->hasBoolean('is_active');
    }

    /**
     * `is_active`-dən başqa bayraqlar - index-də badge kimi göstərilir.
     *
     * @return array<string, string>  sahə => panel adı
     */
    public function flagColumns(): array
    {
        $flags = [];

        foreach ($this->booleans as $field) {
            if ($field !== 'is_active') {
                $flags[$field] = self::booleanLabel($field);
            }
        }

        return $flags;
    }

    /** Formadakı sütun sahələri (tərcümə olunanlar xaric). */
    public function fields(): array
    {
        return array_values(array_unique(array_merge(['key'], $this->extraFields, $this->booleans)));
    }

    /**
     * Formadakı keçid düymələri hazır şəkildə: blade yalnız çap edir.
     *
     * @return list<array{field: string, label: string, checked: bool}>
     */
    public function switches(Model $item): array
    {
        $switches = [];

        foreach ($this->booleans as $field) {
            $switches[] = [
                'field'   => $field,
                'label'   => self::booleanLabel($field),
                'checked' => $item->exists
                    ? (bool) $item->getAttribute($field)
                    : (self::BOOLEAN_DEFAULTS[$field] ?? false),
            ];
        }

        return $switches;
    }

    public static function booleanLabel(string $field): string
    {
        return self::BOOLEAN_LABELS[$field] ?? Str::headline(Str::after($field, 'is_'));
    }

    public static function fieldLabel(string $field): string
    {
        return self::FIELD_LABELS[$field] ?? Str::headline($field);
    }
}
