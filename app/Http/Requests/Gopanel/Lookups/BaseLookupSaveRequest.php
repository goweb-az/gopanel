<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Lookups;

use App\Http\Requests\Gopanel\GopanelFormRequest;
use App\Models\Geography\Language;
use App\Rules\TranslatedRequired;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Lookup formasının ortaq validasiyası və icazəsi.
 *
 * NİYƏ belədir:
 *  - İCAZƏ route adından çıxarılır: `gopanel.lookups.{tip}.save` →
 *    `gopanel.lookups.{tip}.add|edit`. Alt sinifdə icazə adı yazılmır, ona
 *    görə aquastores-dakı «mövcud olmayan icazə adı» xətası təkrarlanmır.
 *  - `key` texniki müqavilədir (kodda/API-də ona müraciət olunur) - formatı
 *    sərtdir və unikaldır.
 *  - Tərcümə dilləri aktiv dillərlə whitelist edilir və ən azı standart dildə
 *    ad məcburidir; dil siyahısı sabit yazılmır (aquastores-da `code != 'az'`
 *    idi - başqa dildə işləyən layihədə sınırdı).
 *
 * Alt sinif yalnız `$model` elan edir; modulun əlavə qaydaları `extraRules()`
 * ilə verilir (məs. `price`, `parent_id`).
 */
abstract class BaseLookupSaveRequest extends GopanelFormRequest
{
    /** @var class-string<Model> */
    protected string $model;

    /**
     * Əl ilə yazılmır - `prepareForValidation()`-da modelin
     * `$translatedAttributes`-dən doldurulur ki, iki siyahı ayrışmasın.
     */
    protected array $translatedFields = [];

    protected ?Model $resolvedItem = null;

    protected bool $itemResolved = false;

    public function rules(): array
    {
        $table = $this->lookupModel()->getTable();

        $rules = [
            'key'        => [
                'required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique($table, 'key')->ignore($this->existingItem()?->getKey()),
            ],
            'color'      => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'class_name' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 _-]*$/'],
            'icon'       => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9 _-]*$/'],
        ];

        foreach ($this->translatedFields as $field) {
            $rules[$field] = $field === 'name'
                ? ['required', 'array', new TranslatedRequired()]
                : ['nullable', 'array'];
            $rules[$field . '.*'] = ['nullable', 'string', $field === 'name' ? 'max:255' : 'max:2000'];
        }

        return array_merge($rules, $this->extraRules());
    }

    /** Modulun əlavə qaydaları - alt sinif override edir. */
    protected function extraRules(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'key'        => 'Açar',
            'name'       => 'Ad',
            'color'      => 'Rəng',
            'class_name' => 'CSS sinfi',
            'icon'       => 'İkon',
        ];
    }

    public function messages(): array
    {
        return [
            'key.regex'   => 'Açar kiçik latın hərfi ilə başlamalı, yalnız kiçik hərf, rəqəm və «_» olmalıdır.',
            'color.regex' => 'Rəng #RRGGBB formatında olmalıdır.',
        ];
    }

    /**
     * Tərcümə massivlərində yalnız aktiv dil kodları qəbul olunur.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $codes = Language::query()->where('is_active', true)->pluck('code')->all();

            foreach ($this->translatedFields as $field) {
                $value = $this->input($field);

                if (!is_array($value)) {
                    continue;
                }

                $unknown = array_diff(array_keys($value), $codes);

                if ($unknown !== []) {
                    $validator->errors()->add($field, 'Tanınmayan dil: ' . implode(', ', $unknown) . '.');
                }
            }
        });
    }

    /**
     * Redaktə olunan sətir (yaradılmada null). Controller da eyni nüsxəni
     * işlədir - sətir iki dəfə axtarılmır.
     */
    public function existingItem(): ?Model
    {
        if (!$this->itemResolved) {
            $key = $this->route('id');
            $this->resolvedItem = filled($key) ? $this->model::resolveByKey((string) $key) : null;
            $this->itemResolved = true;
        }

        return $this->resolvedItem;
    }

    protected function prepareForValidation(): void
    {
        $this->translatedFields = array_values((array) ($this->lookupModel()->translatedAttributes ?? []));
    }

    /** `gopanel.lookups.{tip}.save` → `gopanel.lookups.{tip}.add|edit` */
    protected function ability(): string
    {
        $routeName = (string) $this->route()?->getName();
        $prefix = Str::beforeLast($routeName, '.');

        if ($prefix === '' || $prefix === $routeName) {
            return 'gopanel.lookups.__unknown__';
        }

        return $prefix . '.' . ($this->isUpdate() ? 'edit' : 'add');
    }

    /** Hər iki halda icazə yoxlanılır - `$module` boş olsa da. */
    public function authorize(): bool
    {
        $admin = auth('gopanel')->user();

        return $admin !== null && (bool) $admin->can($this->ability());
    }

    /**
     * 403 HttpException kimi atılır, `AuthorizationException` kimi yox:
     * `App\Exceptions\Handler` AJAX cavabında yalnız HttpException-un status
     * kodunu götürür, `AuthorizationException`-u isə 500 kimi qaytarır -
     * icazə xətası «server xətası» kimi görünməsin.
     */
    protected function failedAuthorization(): void
    {
        throw new AccessDeniedHttpException('Bu əməliyyat üçün icazəniz yoxdur.');
    }

    protected function isUpdate(): bool
    {
        return filled($this->route('id'));
    }

    protected function lookupModel(): Model
    {
        return new $this->model();
    }
}
