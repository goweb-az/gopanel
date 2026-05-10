<?php

namespace App\Livewire\Forms;

use App\Models\Geography\Language;

class LanguageForm extends BaseForm
{
    public array $form = [
        'id'         => null,
        'country_id' => null,
        'code'       => '',
        'name'       => '',
        'sort_order' => 0,
        'is_active'  => true,
        'is_show'    => true,
        'default'    => false,
    ];

    protected function rules(): array
    {
        $id = $this->form['id'] ?? 'NULL';

        return [
            'form.country_id' => 'nullable|integer|exists:countries,id',
            'form.code'       => "required|string|max:5|unique:languages,code,{$id}",
            'form.name'       => 'required|string|max:100',
            'form.sort_order' => 'integer|min:0',
            'form.is_active'  => 'boolean',
            'form.is_show'    => 'boolean',
            'form.default'    => 'boolean',
        ];
    }

    public function setItem(Language $language): void
    {
        $this->form = [
            'id'         => $language->id,
            'country_id' => $language->country_id,
            'code'       => $language->code ?? '',
            'name'       => $language->name ?? '',
            'sort_order' => (int) ($language->sort_order ?? 0),
            'is_active'  => (bool) ($language->is_active ?? true),
            'is_show'    => (bool) ($language->is_show ?? true),
            'default'    => (bool) ($language->default ?? false),
        ];
    }

    public function save(): Language
    {
        $language = Language::findOrNew($this->form['id']);

        $data = collect($this->form)->except('id')->all();

        if ($data['default']) {
            $data['is_active'] = true;
        }

        $language->fill($data);
        $language->save();

        Language::ensureSingleDefault($language);
        Language::ensureFallbackDefault();

        $this->form['id'] = $language->id;

        return $language->fresh();
    }
}
