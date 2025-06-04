<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->company_id;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'reference' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company_id);
                })
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company_id)
                                 ->where('type', 'product');
                })
            ],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'vat_rate' => ['required', 'numeric', 'in:0,5.5,10,20'],
            'unit' => ['nullable', 'string', 'max:50'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'stock_alert_threshold' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['string', 'max:255'],
            'variants' => ['nullable', 'array'],
            'variants.*.name' => ['required', 'string', 'max:100'],
            'variants.*.value' => ['required', 'string', 'max:100'],
            'variants.*.price_adjustment' => ['nullable', 'numeric', 'min:-999999.99', 'max:999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du produit est obligatoire.',
            'name.max' => 'Le nom du produit ne peut pas dépasser 255 caractères.',
            'reference.unique' => 'Cette référence est déjà utilisée pour un autre produit.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'unit_price.required' => 'Le prix unitaire est obligatoire.',
            'unit_price.numeric' => 'Le prix unitaire doit être un nombre.',
            'unit_price.min' => 'Le prix unitaire ne peut pas être négatif.',
            'vat_rate.required' => 'Le taux de TVA est obligatoire.',
            'vat_rate.in' => 'Le taux de TVA doit être 0%, 5.5%, 10% ou 20%.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_id' => $this->user()->company_id,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
