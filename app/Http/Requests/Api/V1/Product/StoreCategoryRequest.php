<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->company_id;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company_id)
                                 ->where('parent_id', $this->input('parent_id'));
                })
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'string', 'in:product,service,both'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company_id);
                })
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-F]{6}$/i'],
            'icon' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.max' => 'Le nom de la catégorie ne peut pas dépasser 255 caractères.',
            'name.unique' => 'Cette catégorie existe déjà dans ce niveau.',
            'type.required' => 'Le type de catégorie est obligatoire.',
            'type.in' => 'Le type doit être : produit, service ou les deux.',
            'parent_id.exists' => 'La catégorie parent sélectionnée n\'existe pas.',
            'color.regex' => 'La couleur doit être au format hexadécimal (#RRGGBB).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_id' => $this->user()->company_id,
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }
}
