<?php

declare(strict_types=1);

namespace App\Http\Requests\Gopanel\Contact;

use App\Http\Requests\Gopanel\GopanelFormRequest;

class ContactInfoSaveRequest extends GopanelFormRequest
{
    protected string $module = 'gopanel.contact.contact-info';

    protected array $translatedFields = ['page_title', 'page_description', 'adress'];

    /** Tək sətirli səhifə - `.add` icazəsi yoxdur. */
    protected function ability(): string
    {
        return $this->module . '.edit';
    }

    public function rules(): array
    {
        return [
            'page_title'       => ['nullable', 'array'],
            'page_description' => ['nullable', 'array'],
            'adress'           => ['nullable', 'array'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'mobile'           => ['nullable', 'string', 'max:50'],
            'whatsapp'         => ['nullable', 'string', 'max:50'],
            'support_whatsapp' => ['nullable', 'string', 'max:50'],
            'sales_whatsapp'   => ['nullable', 'string', 'max:50'],
            'info_email'       => ['nullable', 'email', 'max:255'],
            'support_email'    => ['nullable', 'email', 'max:255'],
            'map'              => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'info_email'    => 'Ümumi e-poçt',
            'support_email' => 'Dəstək e-poçtu',
        ];
    }
}
