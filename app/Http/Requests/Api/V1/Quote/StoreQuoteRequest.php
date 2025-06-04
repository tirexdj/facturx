<?php

namespace App\Http\Requests\Api\V1\Quote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Vérifier que l'utilisateur appartient à une entreprise
        return auth()->check() && auth()->user()->company_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                })
            ],
            'quote_date' => 'nullable|date',
            'valid_until' => 'nullable|date|after:quote_date',
            'subject' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'terms' => 'nullable|string|max:2000',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'shipping_amount' => 'nullable|numeric|min:0',
            
            // Validation des lignes de devis
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                })
            ],
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Le client est obligatoire.',
            'customer_id.exists' => 'Le client sélectionné n\'existe pas.',
            'quote_date.date' => 'La date du devis doit être une date valide.',
            'valid_until.date' => 'La date de validité doit être une date valide.',
            'valid_until.after' => 'La date de validité doit être postérieure à la date du devis.',
            'subject.max' => 'L\'objet ne peut pas dépasser 255 caractères.',
            'notes.max' => 'Les notes ne peuvent pas dépasser 2000 caractères.',
            'terms.max' => 'Les conditions ne peuvent pas dépasser 2000 caractères.',
            'discount_type.in' => 'Le type de remise doit être "percentage" ou "fixed".',
            'discount_value.numeric' => 'La valeur de remise doit être un nombre.',
            'discount_value.min' => 'La valeur de remise ne peut pas être négative.',
            'shipping_amount.numeric' => 'Les frais de port doivent être un nombre.',
            'shipping_amount.min' => 'Les frais de port ne peuvent pas être négatifs.',
            
            // Messages pour les lignes
            'items.required' => 'Au moins une ligne de devis est obligatoire.',
            'items.array' => 'Les lignes de devis doivent être un tableau.',
            'items.min' => 'Au moins une ligne de devis est obligatoire.',
            'items.*.product_id.exists' => 'Le produit sélectionné n\'existe pas.',
            'items.*.description.required' => 'La description de la ligne est obligatoire.',
            'items.*.description.max' => 'La description ne peut pas dépasser 500 caractères.',
            'items.*.quantity.required' => 'La quantité est obligatoire.',
            'items.*.quantity.numeric' => 'La quantité doit être un nombre.',
            'items.*.quantity.min' => 'La quantité doit être supérieure à 0.',
            'items.*.unit_price.required' => 'Le prix unitaire est obligatoire.',
            'items.*.unit_price.numeric' => 'Le prix unitaire doit être un nombre.',
            'items.*.unit_price.min' => 'Le prix unitaire ne peut pas être négatif.',
            'items.*.tax_rate.numeric' => 'Le taux de TVA doit être un nombre.',
            'items.*.tax_rate.min' => 'Le taux de TVA ne peut pas être négatif.',
            'items.*.tax_rate.max' => 'Le taux de TVA ne peut pas dépasser 100%.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'client',
            'quote_date' => 'date du devis',
            'valid_until' => 'date de validité',
            'subject' => 'objet',
            'notes' => 'notes',
            'terms' => 'conditions',
            'discount_type' => 'type de remise',
            'discount_value' => 'valeur de remise',
            'shipping_amount' => 'frais de port',
            'items' => 'lignes de devis',
            'items.*.product_id' => 'produit',
            'items.*.description' => 'description',
            'items.*.quantity' => 'quantité',
            'items.*.unit_price' => 'prix unitaire',
            'items.*.tax_rate' => 'taux de TVA',
        ];
    }
}
