<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\LlmsTxt\SaveLlmsTxtFormAction;
use App\Models\Seo\LlmsTxt;
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
        $item = SaveLlmsTxtFormAction::run(form: $this->form);

        $this->form['id'] = $item->id;

        return $item;
    }
}
