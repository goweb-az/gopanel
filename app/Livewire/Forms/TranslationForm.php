<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\Translation\SaveTranslationFormAction;
use App\Enums\Gopanel\TranslationGroups;
use App\Enums\Gopanel\TranslationPlatfroms;
use App\Models\Geography\Language;
use App\Models\Translations\Translation;
use Livewire\Form;

class TranslationForm extends Form
{
    public array $form = [
        'key'      => '',
        'platform' => 'website',
        'group'    => 'title',
        'filename' => '',
    ];

    /** @var array<string, string> locale => value */
    public array $values = [];

    /** Original key + platform — needed when the user renames the bundle. */
    public ?string $originalKey = null;

    public ?string $originalPlatform = null;

    protected function rules(): array
    {
        return [
            'form.key'      => ['required', 'string', 'max:255'],
            'form.platform' => ['required', 'string', 'in:' . implode(',', array_column(TranslationPlatfroms::cases(), 'value'))],
            'form.group'    => ['required', 'string', 'in:' . implode(',', array_column(TranslationGroups::cases(), 'value'))],
            'form.filename' => ['nullable', 'string', 'max:255'],
            'values'        => 'array',
            'values.*'      => ['nullable', 'string'],
        ];
    }

    public function setBundle(?string $key, ?string $platform): void
    {
        $rows = $key && $platform
            ? Translation::where('key', $key)->where('platform', $platform)->get()
            : collect();

        $first = $rows->first();

        $this->form = [
            'key'      => $first?->key ?? '',
            'platform' => $first?->platform ?? 'website',
            'group'    => $first?->group ?? 'title',
            'filename' => $first?->filename ?? '',
        ];

        $this->originalKey      = $first?->key;
        $this->originalPlatform = $first?->platform;

        $this->values = [];
        foreach (Language::getCachedAll() as $lang) {
            $this->values[$lang->code] = $rows->firstWhere('locale', $lang->code)?->value ?? '';
        }
    }

    public function save(): void
    {
        SaveTranslationFormAction::run(
            form: $this->form,
            values: $this->values,
            originalKey: $this->originalKey,
            originalPlatform: $this->originalPlatform,
        );

        $this->originalKey      = $this->form['key'];
        $this->originalPlatform = $this->form['platform'];
    }
}
