<?php

namespace App\Http\Requests\Api\V1\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Pour la création d'une company, on peut avoir des règles particulières
        // Par exemple, permettre seulement aux super admin ou lors de l'inscription
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'siren' => ['nullable', 'string', 'size:9', 'regex:/^[0-9]{9}$/', 'unique:companies,siren'],
            'siret' => ['nullable', 'string', 'size:14', 'regex:/^[0-9]{14}$/', 'unique:companies,siret'],
            'vat_number' => ['nullable', 'string', 'max:20'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'legal_form' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'plan_id' => ['required', 'uuid', 'exists:plans,id'],
            'pdp_id' => ['nullable', 'string', 'max:50'],
            'vat_regime' => ['nullable', 'string', Rule::in(['normal', 'micro', 'franchise_base'])],
            'fiscal_year_start' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'size:3', Rule::in(['EUR', 'USD', 'GBP'])],
            'language_code' => ['nullable', 'string', 'size:2', Rule::in(['fr', 'en', 'es', 'de'])],
            'is_active' => ['nullable', 'boolean'],
            'trial_ends_at' => ['nullable', 'date', 'after:today'],
            
            // Adresse optionnelle
            'address' => ['nullable', 'array'],
            'address.type' => ['nullable', 'string', Rule::in(['main', 'billing', 'shipping'])],
            'address.line_1' => ['required_with:address', 'string', 'max:255'],
            'address.line_2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'string', 'max:100'],
            'address.postal_code' => ['required_with:address', 'string', 'max:20'],
            'address.state' => ['nullable', 'string', 'max:100'],
            'address.country_code' => ['nullable', 'string', 'size:2'],
            'address.is_primary' => ['nullable', 'boolean'],
            
            // Email optionnel
            'email' => ['nullable', 'array'],
            'email.email' => ['required_with:email', 'email', 'max:255'],
            'email.type' => ['nullable', 'string', Rule::in(['main', 'billing', 'support'])],
            'email.is_primary' => ['nullable', 'boolean'],
            
            // Téléphone optionnel
            'phone' => ['nullable', 'array'],
            'phone.number' => ['required_with:phone', 'string', 'max:20'],
            'phone.type' => ['nullable', 'string', Rule::in(['main', 'fax', 'mobile'])],
            'phone.country_code' => ['nullable', 'string', 'size:2'],
            'phone.is_primary' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de l\'entreprise est obligatoire.',
            'name.max' => 'Le nom de l\'entreprise ne peut pas dépasser 255 caractères.',
            'siren.size' => 'Le SIREN doit contenir exactement 9 chiffres.',
            'siren.regex' => 'Le SIREN doit contenir uniquement des chiffres.',
            'siren.unique' => 'Une entreprise avec ce SIREN existe déjà.',
            'siret.size' => 'Le SIRET doit contenir exactement 14 chiffres.',
            'siret.regex' => 'Le SIRET doit contenir uniquement des chiffres.',
            'siret.unique' => 'Une entreprise avec ce SIRET existe déjà.',
            'plan_id.required' => 'Un plan est obligatoire.',
            'plan_id.exists' => 'Le plan sélectionné n\'existe pas.',
            'website.url' => 'L\'URL du site web n\'est pas valide.',
            'vat_regime.in' => 'Le régime de TVA doit être : normal, micro ou franchise_base.',
            'currency_code.in' => 'La devise doit être : EUR, USD ou GBP.',
            'language_code.in' => 'La langue doit être : fr, en, es ou de.',
            'trial_ends_at.after' => 'La date de fin d\'essai doit être dans le futur.',
            'address.line_1.required_with' => 'L\'adresse ligne 1 est obligatoire quand une adresse est fournie.',
            'address.city.required_with' => 'La ville est obligatoire quand une adresse est fournie.',
            'address.postal_code.required_with' => 'Le code postal est obligatoire quand une adresse est fournie.',
            'email.email.required_with' => 'L\'email est obligatoire quand les informations email sont fournies.',
            'email.email.email' => 'L\'email doit être une adresse email valide.',
            'phone.number.required_with' => 'Le numéro de téléphone est obligatoire quand les informations téléphone sont fournies.',
        ];
    }
}
