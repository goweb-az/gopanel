<?php

namespace App\Livewire\Forms;

use App\Models\Seo\LlmsTxt;
use Illuminate\Support\Facades\Cache;
use Livewire\Form;

class LlmsTxtForm extends Form
{
    public array $form = [
        'id'      => null,
        'content' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.content' => 'nullable|string',
        ];
    }

    public function setItem(LlmsTxt $item): void
    {
        $this->form = [
            'id'      => $item->id,
            'content' => $item->content ?? '',
        ];
    }

    public function save(): LlmsTxt
    {
        $item = LlmsTxt::findOrNew($this->form['id']);
        $item->fill(collect($this->form)->except('id')->all());
        $item->save();

        $this->form['id'] = $item->id;

        Cache::forget('llms_txt');

        return $item;
    }
}
