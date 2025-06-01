<?php

namespace App\Http\Requests\Api\V1\Customer;

use App\Domain\Customer\Models\Category;
use App\Domain\Invoice\Models\PaymentTerm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $company = $this->user()->company;
        
        if (!$company) {
            return false;
        }

        // Check if user has permission to create clients
        return $this->user()->hasPermission('create_clients') || 
               $this->user()->role?->name === 'Directeur';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_type' => ['required', 'string', Rule::in(['company', 'individual'])],
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'siren' => [
                'nullable',
                'string',
                'size:9',
                'regex:/^[0-9]{9}$/',
                Rule::unique('clients')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company->id)
                               ->whereNull('deleted_at');
                })
            ],
            'siret' => [
                'nullable',
                'string',
                'size:14',
                'regex:/^[0-9]{14}$/',
                Rule::unique('clients')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company->id)
                               ->whereNull('deleted_at');
                })
            ],
            'vat_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('clients')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company->id)
                               ->whereNull('deleted_at');
                })
            ],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'legal_form' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'category_id' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company->id)
                               ->where('type', 'client')
                               ->whereNull('deleted_at');
                })
            ],
            'currency_code' => ['required', 'string', 'size:3'],
            'language_code' => ['required', 'string', 'size:2'],
            'payment_terms_id' => [
                'nullable',
                'uuid',
                Rule::exists('payment_terms', 'id')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company->id)
                               ->whereNull('deleted_at');
                })
            ],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            
            // Addresses
            'addresses' => ['nullable', 'array'],
            'addresses.*.label' => ['nullable', 'string', 'max:100'],
            'addresses.*.line_1' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.line_3' => ['nullable', 'string', 'max:255'],
            'addresses.*.postal_code' => ['required_with:addresses', 'string', 'max:20'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:100'],
            'addresses.*.state_province' => ['nullable', 'string', 'max:100'],
            'addresses.*.country_code' => ['required_with:addresses', 'string', 'size:2'],
            'addresses.*.is_default' => ['nullable', 'boolean'],
            'addresses.*.is_billing' => ['nullable', 'boolean'],
            'addresses.*.is_shipping' => ['nullable', 'boolean'],
            
            // Phone numbers
            'phone_numbers' => ['nullable', 'array'],
            'phone_numbers.*.label' => ['nullable', 'string', 'max:100'],
            'phone_numbers.*.country_code' => ['required_with:phone_numbers', 'string', 'max:5'],
            'phone_numbers.*.number' => ['required_with:phone_numbers', 'string', 'max:20'],
            'phone_numbers.*.extension' => ['nullable', 'string', 'max:10'],
            'phone_numbers.*.is_default' => ['nullable', 'boolean'],
            'phone_numbers.*.is_mobile' => ['nullable', 'boolean'],
            
            // Emails
            'emails' => ['nullable', 'array'],
            'emails.*.label' => ['nullable', 'string', 'max:100'],
            'emails.*.email' => ['required_with:emails', 'email', 'max:255'],
            'emails.*.is_default' => ['nullable', 'boolean'],
            
            // Contacts
            'contacts' => ['nullable', 'array'],
            'contacts.*.first_name' => ['required_with:contacts', 'string', 'max:100'],
            'contacts.*.last_name' => ['required_with:contacts', 'string', 'max:100'],
            'contacts.*.job_title' => ['nullable', 'string', 'max:100'],
            'contacts.*.department' => ['nullable', 'string', 'max:100'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'contacts.*.notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'client_type.required' => 'Le type de client est obligatoire.',
            'client_type.in' => 'Le type de client doit être "company" ou "individual".',
            'name.required' => 'Le nom est obligatoire.',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'siren.size' => 'Le SIREN doit contenir exactement 9 chiffres.',
            'siren.regex' => 'Le SIREN doit contenir uniquement des chiffres.',
            'siren.unique' => 'Ce SIREN est déjà utilisé par un autre client.',
            'siret.size' => 'Le SIRET doit contenir exactement 14 chiffres.',
            'siret.regex' => 'Le SIRET doit contenir uniquement des chiffres.',
            'siret.unique' => 'Ce SIRET est déjà utilisé par un autre client.',
            'vat_number.unique' => 'Ce numéro de TVA est déjà utilisé par un autre client.',
            'website.url' => 'L\'URL du site web doit être valide.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'currency_code.required' => 'Le code devise est obligatoire.',
            'currency_code.size' => 'Le code devise doit contenir exactement 3 caractères.',
            'language_code.required' => 'Le code langue est obligatoire.',
            'language_code.size' => 'Le code langue doit contenir exactement 2 caractères.',
            'payment_terms_id.exists' => 'Les conditions de paiement sélectionnées n\'existent pas.',
            'credit_limit.numeric' => 'La limite de crédit doit être un nombre.',
            'credit_limit.min' => 'La limite de crédit doit être positive.',
            'tags.array' => 'Les tags doivent être un tableau.',
            'tags.*.max' => 'Chaque tag ne doit pas dépasser 50 caractères.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean SIREN/SIRET from spaces and special characters
        if ($this->has('siren')) {
            $this->merge([
                'siren' => preg_replace('/[^0-9]/', '', $this->siren),
            ]);
        }

        if ($this->has('siret')) {
            $this->merge([
                'siret' => preg_replace('/[^0-9]/', '', $this->siret),
            ]);
        }

        // Ensure default values
        $this->merge([
            'currency_code' => $this->currency_code ?? 'EUR',
            'language_code' => $this->language_code ?? 'fr',
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation for company type
            if ($this->client_type === 'company') {
                if (empty($this->siren) && empty($this->siret)) {
                    $validator->errors()->add('siren', 'Le SIREN ou SIRET est obligatoire pour une entreprise.');
                }
            }

            // Validate that only one default address/phone/email exists
            $this->validateSingleDefault($validator, 'addresses');
            $this->validateSingleDefault($validator, 'phone_numbers');
            $this->validateSingleDefault($validator, 'emails');
            
            // Validate that only one primary contact exists
            if (is_array($this->contacts)) {
                $primaryCount = collect($this->contacts)->where('is_primary', true)->count();
                if ($primaryCount > 1) {
                    $validator->errors()->add('contacts', 'Un seul contact peut être défini comme principal.');
                }
            }
        });
    }

    /**
     * Validate that only one item is marked as default.
     */
    private function validateSingleDefault($validator, string $field): void
    {
        if (is_array($this->$field)) {
            $defaultCount = collect($this->$field)->where('is_default', true)->count();
            if ($defaultCount > 1) {
                $validator->errors()->add($field, 'Un seul élément peut être défini par défaut.');
            }
        }
    }
}
