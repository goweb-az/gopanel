<?php

namespace App\Livewire\Forms;

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
            'form.key'      => 'required|string|max:255',
            'form.platform' => 'required|string|in:' . implode(',', array_column(TranslationPlatfroms::cases(), 'value')),
            'form.group'    => 'required|string|in:' . implode(',', array_column(TranslationGroups::cases(), 'value')),
            'form.filename' => 'nullable|string|max:255',
            'values'        => 'array',
            'values.*'      => 'nullable|string',
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

    /**
     * Persist one row per locale. Renames are handled by deleting the old
     * (key, platform) bundle first when the user changed either field.
     */
    public function save(): void
    {
        $renamed = ($this->originalKey && $this->originalKey !== $this->form['key'])
            || ($this->originalPlatform && $this->originalPlatform !== $this->form['platform']);

        if ($renamed) {
            Translation::where('key', $this->originalKey)
                ->where('platform', $this->originalPlatform)
                ->delete();
        }

        foreach ($this->values as $locale => $value) {
            Translation::updateOrCreate(
                [
                    'key'      => $this->form['key'],
                    'platform' => $this->form['platform'],
                    'locale'   => $locale,
                ],
                [
                    'value'    => $value,
                    'group'    => $this->form['group'],
                    'filename' => $this->form['filename'] ?: null,
                ]
            );
        }

        $this->originalKey      = $this->form['key'];
        $this->originalPlatform = $this->form['platform'];
    }
}
