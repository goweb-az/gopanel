<?php

namespace App\Http\Requests\Gopanel\Languages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SortLanguagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'              => ['required', 'array', 'min:1'],
            'items.*.id'         => ['required', 'integer', 'distinct', 'exists:languages,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $orders = collect($this->input('items', []))->pluck('sort_order');

            if ($orders->count() !== $orders->unique()->count()) {
                $validator->errors()->add('items', 'Sıra nömrələri təkrarlana bilməz.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'items'              => 'Sıralama siyahısı',
            'items.*.id'         => 'Dil ID',
            'items.*.sort_order' => 'Sıra nömrəsi',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'        => 'Sıralama məlumatı mütləqdir.',
            'items.*.id.exists'     => 'Göndərilən dil mövcud deyil.',
            'items.*.id.distinct'   => 'Eyni dil təkrarlana bilməz.',
        ];
    }
}
