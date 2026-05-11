<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\SeoAnalytics\SaveSeoAnalyticsFormAction;
use App\Models\Seo\SeoAnalytics;
use Livewire\Form;

class SeoAnalyticsForm extends Form
{
    public array $form = [
        'id' => null,
        'head' => '',
        'body' => '',
        'footer' => '',
        'robots_txt' => '',
        'ai_txt' => '',
        'other' => '',
    ];

    protected function rules(): array
    {
        return [
            'form.head' => ['nullable', 'string'],
            'form.body' => ['nullable', 'string'],
            'form.footer' => ['nullable', 'string'],
            'form.robots_txt' => ['nullable', 'string'],
            'form.ai_txt' => ['nullable', 'string'],
            'form.other' => ['nullable', 'string'],
        ];
    }

    public function setItem(SeoAnalytics $item): void
    {
        $this->form = [
            'id' => $item->id,
            'head' => $item->head ?? '',
            'body' => $item->body ?? '',
            'footer' => $item->footer ?? '',
            'robots_txt' => $item->robots_txt ?? '',
            'ai_txt' => $item->ai_txt ?? '',
            'other' => $item->other ?? '',
        ];
    }

    public function save(): SeoAnalytics
    {
        $item = SaveSeoAnalyticsFormAction::run(form: $this->form);

        $this->form['id'] = $item->id;

        return $item;
    }
}
