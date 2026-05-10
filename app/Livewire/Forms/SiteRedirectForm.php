<?php

namespace App\Livewire\Forms;

use App\Enums\Gopanel\Seo\RedirectMatchTypeEnum;
use App\Models\Seo\SiteRedirect;
use Livewire\Form;

class SiteRedirectForm extends Form
{
    public array $form = [
        'id'          => null,
        'locale'      => null,
        'source'      => '',
        'match_type'  => 'exact',
        'regex_flags' => '',
        'target'      => '',
        'http_code'   => 301,
        'is_active'   => true,
        'priority'    => 0,
        'starts_at'   => null,
        'ends_at'     => null,
        'notes'       => '',
    ];

    protected function rules(): array
    {
        return [
            'form.locale'     => 'nullable|string|max:5',
            'form.source'     => 'required|string|max:1000',
            'form.match_type' => 'required|string|in:' . implode(',', array_column(RedirectMatchTypeEnum::cases(), 'value')),
            'form.regex_flags'=> 'nullable|string|max:20',
            'form.target'     => 'required|string|max:1000',
            'form.http_code'  => 'required|integer|in:301,302,303,307,308',
            'form.is_active'  => 'boolean',
            'form.priority'   => 'integer|min:0',
            'form.starts_at'  => 'nullable|date',
            'form.ends_at'    => 'nullable|date|after_or_equal:form.starts_at',
            'form.notes'      => 'nullable|string|max:2000',
        ];
    }

    public function setItem(SiteRedirect $redirect): void
    {
        $this->form = [
            'id'          => $redirect->id,
            'locale'      => $redirect->locale,
            'source'      => $redirect->source ?? '',
            'match_type'  => $redirect->match_type?->value ?? $redirect->match_type ?? 'exact',
            'regex_flags' => $redirect->regex_flags ?? '',
            'target'      => $redirect->target ?? '',
            'http_code'   => (int) ($redirect->http_code ?? 301),
            'is_active'   => (bool) ($redirect->is_active ?? true),
            'priority'    => (int) ($redirect->priority ?? 0),
            'starts_at'   => $redirect->starts_at?->format('Y-m-d\TH:i'),
            'ends_at'     => $redirect->ends_at?->format('Y-m-d\TH:i'),
            'notes'       => $redirect->notes ?? '',
        ];
    }

    public function save(): SiteRedirect
    {
        $redirect = SiteRedirect::findOrNew($this->form['id']);

        $redirect->fill(collect($this->form)->except('id')->all());
        $redirect->save();

        $this->form['id'] = $redirect->id;

        return $redirect;
    }
}
