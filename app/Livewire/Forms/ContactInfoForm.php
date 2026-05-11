<?php

namespace App\Livewire\Forms;

use App\Actions\Gopanel\ContactInfo\SaveContactInfoFormAction;
use App\Models\Contact\ContactInfo;

class ContactInfoForm extends BaseForm
{
    public array $form = [
        'id'               => null,
        'phone'            => '',
        'mobile'           => '',
        'whatsapp'         => '',
        'support_whatsapp' => '',
        'sales_whatsapp'   => '',
        'info_email'       => '',
        'support_email'    => '',
        'map'              => '',
    ];

    protected function rules(): array
    {
        return [
            'form.phone'            => ['nullable', 'string', 'max:50'],
            'form.mobile'           => ['nullable', 'string', 'max:50'],
            'form.whatsapp'         => ['nullable', 'string', 'max:50'],
            'form.support_whatsapp' => ['nullable', 'string', 'max:50'],
            'form.sales_whatsapp'   => ['nullable', 'string', 'max:50'],
            'form.info_email'       => ['nullable', 'email', 'max:120'],
            'form.support_email'    => ['nullable', 'email', 'max:120'],
            'form.map'              => ['nullable', 'string'],
        ];
    }

    public function setItem(ContactInfo $item): void
    {
        $this->form = [
            'id'               => $item->id,
            'phone'            => $item->phone ?? '',
            'mobile'           => $item->mobile ?? '',
            'whatsapp'         => $item->whatsapp ?? '',
            'support_whatsapp' => $item->support_whatsapp ?? '',
            'sales_whatsapp'   => $item->sales_whatsapp ?? '',
            'info_email'       => $item->info_email ?? '',
            'support_email'    => $item->support_email ?? '',
            'map'              => $item->map ?? '',
        ];

        $this->prepareTranslations($item);
    }

    public function save(): ContactInfo
    {
        $item = SaveContactInfoFormAction::run(
            form: $this->form,
            translations: $this->translations,
        );

        $this->form['id'] = $item->id;

        return $item;
    }
}
