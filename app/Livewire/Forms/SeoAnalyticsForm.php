<?php

namespace App\Livewire\Forms;

use App\Models\Seo\SeoAnalytics;
use Illuminate\Support\Facades\Cache;
use Livewire\Form;

class SeoAnalyticsForm extends Form
{
    public array $form = [
        'id'         => null,
        'head'       => '',
        'body'       => '',
        'footer'     => '',
        'robots_txt' => '',
        'ai_txt'     => '',
        'other'      => '',
    ];

    protected function rules(): array
    {
        return [
            'form.head'       => 'nullable|string',
            'form.body'       => 'nullable|string',
            'form.footer'     => 'nullable|string',
            'form.robots_txt' => 'nullable|string',
            'form.ai_txt'     => 'nullable|string',
            'form.other'      => 'nullable|string',
        ];
    }

    public function setItem(SeoAnalytics $item): void
    {
        $this->form = [
            'id'         => $item->id,
            'head'       => $item->head ?? '',
            'body'       => $item->body ?? '',
            'footer'     => $item->footer ?? '',
            'robots_txt' => $item->robots_txt ?? '',
            'ai_txt'     => $item->ai_txt ?? '',
            'other'      => $item->other ?? '',
        ];
    }

    public function save(): SeoAnalytics
    {
        $item = SeoAnalytics::findOrNew($this->form['id']);
        $item->fill(collect($this->form)->except('id')->all());
        $item->save();

        $this->form['id'] = $item->id;

        Cache::forget('seo_analytics');

        return $item;
    }
}
