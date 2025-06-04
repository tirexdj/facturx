<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && 
               $this->user()->company_id && 
               $this->route('service')->company_id === $this->user()->company_id;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company_id)
                                 ->where('type', 'service');
                })
            ],
            'unit_price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999.99'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'vat_rate' => ['sometimes', 'required', 'numeric', 'in:0,5.5,10,20'],
            'unit' => ['sometimes', 'required', 'string', 'in:hour,day,month,fixed,piece'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'is_recurring' => ['boolean'],
            'recurring_period' => [
                'nullable',
                'string',
                'in:week,month,quarter,year',
                'required_if:is_recurring,true'
            ],
            'setup_fee' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['boolean'],
            'options' => ['nullable', 'array'],
            'options.*.name' => ['required', 'string', 'max:100'],
            'options.*.price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'options.*.vat_rate' => ['required', 'numeric', 'in:0,5.5,10,20'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du service est obligatoire.',
            'name.max' => 'Le nom du service ne peut pas dépasser 255 caractères.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'unit_price.required' => 'Le prix unitaire est obligatoire.',
            'unit_price.numeric' => 'Le prix unitaire doit être un nombre.',
            'unit_price.min' => 'Le prix unitaire ne peut pas être négatif.',
            'vat_rate.required' => 'Le taux de TVA est obligatoire.',
            'vat_rate.in' => 'Le taux de TVA doit être 0%, 5.5%, 10% ou 20%.',
            'unit.required' => 'L\'unité est obligatoire.',
            'unit.in' => 'L\'unité doit être : heure, jour, mois, forfait ou pièce.',
            'recurring_period.required_if' => 'La période de récurrence est obligatoire pour un service récurrent.',
        ];
    }
}
