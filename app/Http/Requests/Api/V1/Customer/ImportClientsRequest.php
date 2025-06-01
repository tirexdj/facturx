<?php

namespace App\Http\Requests\Api\V1\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportClientsRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx,xls',
                'max:10240' // 10MB max
            ],
            'mapping' => ['required', 'array'],
            'mapping.name' => ['required', 'string'],
            'mapping.client_type' => ['nullable', 'string'],
            'mapping.legal_name' => ['nullable', 'string'],
            'mapping.trading_name' => ['nullable', 'string'],
            'mapping.siren' => ['nullable', 'string'],
            'mapping.siret' => ['nullable', 'string'],
            'mapping.vat_number' => ['nullable', 'string'],
            'mapping.registration_number' => ['nullable', 'string'],
            'mapping.legal_form' => ['nullable', 'string'],
            'mapping.website' => ['nullable', 'string'],
            'mapping.currency_code' => ['nullable', 'string'],
            'mapping.language_code' => ['nullable', 'string'],
            'mapping.credit_limit' => ['nullable', 'string'],
            'mapping.notes' => ['nullable', 'string'],
            'mapping.tags' => ['nullable', 'string'],
            'mapping.address_line_1' => ['nullable', 'string'],
            'mapping.address_line_2' => ['nullable', 'string'],
            'mapping.address_line_3' => ['nullable', 'string'],
            'mapping.address_postal_code' => ['nullable', 'string'],
            'mapping.address_city' => ['nullable', 'string'],
            'mapping.address_state_province' => ['nullable', 'string'],
            'mapping.address_country_code' => ['nullable', 'string'],
            'mapping.phone_number' => ['nullable', 'string'],
            'mapping.phone_country_code' => ['nullable', 'string'],
            'mapping.email' => ['nullable', 'string'],
            'mapping.contact_first_name' => ['nullable', 'string'],
            'mapping.contact_last_name' => ['nullable', 'string'],
            'mapping.contact_job_title' => ['nullable', 'string'],
            'mapping.contact_department' => ['nullable', 'string'],
            'skip_first_row' => ['boolean'],
            'update_existing' => ['boolean'],
            'category_id' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company->id)
                               ->where('type', 'client')
                               ->whereNull('deleted_at');
                })
            ],
            'default_currency_code' => ['required', 'string', 'size:3'],
            'default_language_code' => ['required', 'string', 'size:2'],
            'default_client_type' => ['required', 'string', Rule::in(['company', 'individual'])],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Le fichier d\'import est obligatoire.',
            'file.file' => 'Le fichier téléchargé n\'est pas valide.',
            'file.mimes' => 'Le fichier doit être au format CSV, TXT, XLSX ou XLS.',
            'file.max' => 'Le fichier ne doit pas dépasser 10 MB.',
            'mapping.required' => 'Le mapping des colonnes est obligatoire.',
            'mapping.name.required' => 'Le champ nom doit être mappé.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'default_currency_code.required' => 'Le code devise par défaut est obligatoire.',
            'default_currency_code.size' => 'Le code devise doit contenir exactement 3 caractères.',
            'default_language_code.required' => 'Le code langue par défaut est obligatoire.',
            'default_language_code.size' => 'Le code langue doit contenir exactement 2 caractères.',
            'default_client_type.required' => 'Le type de client par défaut est obligatoire.',
            'default_client_type.in' => 'Le type de client par défaut doit être "company" ou "individual".',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'skip_first_row' => $this->skip_first_row ?? true,
            'update_existing' => $this->update_existing ?? false,
            'default_currency_code' => $this->default_currency_code ?? 'EUR',
            'default_language_code' => $this->default_language_code ?? 'fr',
            'default_client_type' => $this->default_client_type ?? 'company',
        ]);
    }
}
