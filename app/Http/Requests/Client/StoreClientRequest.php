<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // L'autorisation est gérée dans le controller via les policies
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:prospect,client',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            
            // Informations légales
            'siren' => 'nullable|string|size:9|regex:/^[0-9]{9}$/',
            'siret' => 'nullable|string|size:14|regex:/^[0-9]{14}$/',
            'vat_number' => 'nullable|string|max:50',
            'legal_form' => 'nullable|string|max:100',
            'capital' => 'nullable|numeric|min:0',
            'registration_number' => 'nullable|string|max:50',
            
            // Catégorisation
            'category_id' => 'nullable|exists:client_categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            
            // Status
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string|max:2000',
            
            // Informations commerciales
            'payment_terms' => 'nullable|integer|min:0|max:365',
            'payment_method' => 'nullable|in:bank_transfer,check,cash,card,other',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            
            // Adresses
            'addresses' => 'nullable|array|max:5',
            'addresses.*.type' => 'required|in:billing,delivery,postal',
            'addresses.*.line1' => 'required|string|max:255',
            'addresses.*.line2' => 'nullable|string|max:255',
            'addresses.*.line3' => 'nullable|string|max:255',
            'addresses.*.postal_code' => 'required|string|max:10',
            'addresses.*.city' => 'required|string|max:100',
            'addresses.*.state' => 'nullable|string|max:100',
            'addresses.*.country' => 'required|string|size:2',
            'addresses.*.is_default' => 'nullable|boolean',
            
            // Contacts
            'contacts' => 'nullable|array|max:10',
            'contacts.*.first_name' => 'required|string|max:100',
            'contacts.*.last_name' => 'required|string|max:100',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.phone' => 'nullable|string|max:20',
            'contacts.*.mobile' => 'nullable|string|max:20',
            'contacts.*.position' => 'nullable|string|max:100',
            'contacts.*.department' => 'nullable|string|max:100',
            'contacts.*.is_primary' => 'nullable|boolean',
            'contacts.*.notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du client est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'siren.size' => 'Le numéro SIREN doit contenir exactement 9 chiffres.',
            'siren.regex' => 'Le numéro SIREN ne doit contenir que des chiffres.',
            'siret.size' => 'Le numéro SIRET doit contenir exactement 14 chiffres.',
            'siret.regex' => 'Le numéro SIRET ne doit contenir que des chiffres.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'addresses.max' => 'Un client ne peut avoir plus de 5 adresses.',
            'contacts.max' => 'Un client ne peut avoir plus de 10 contacts.',
            'addresses.*.line1.required' => 'La première ligne d\'adresse est obligatoire.',
            'addresses.*.postal_code.required' => 'Le code postal est obligatoire.',
            'addresses.*.city.required' => 'La ville est obligatoire.',
            'addresses.*.country.required' => 'Le pays est obligatoire.',
            'addresses.*.country.size' => 'Le code pays doit contenir exactement 2 caractères.',
            'contacts.*.first_name.required' => 'Le prénom du contact est obligatoire.',
            'contacts.*.last_name.required' => 'Le nom du contact est obligatoire.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Nettoyer les numéros SIREN/SIRET
        if ($this->has('siren')) {
            $this->merge([
                'siren' => preg_replace('/[^0-9]/', '', $this->siren)
            ]);
        }

        if ($this->has('siret')) {
            $this->merge([
                'siret' => preg_replace('/[^0-9]/', '', $this->siret)
            ]);
        }

        // S'assurer qu'il y a une adresse par défaut si des adresses sont fournies
        if ($this->has('addresses') && is_array($this->addresses)) {
            $hasDefault = collect($this->addresses)->contains('is_default', true);
            if (!$hasDefault && count($this->addresses) > 0) {
                $addresses = $this->addresses;
                $addresses[0]['is_default'] = true;
                $this->merge(['addresses' => $addresses]);
            }
        }

        // S'assurer qu'il y a un contact principal si des contacts sont fournis
        if ($this->has('contacts') && is_array($this->contacts)) {
            $hasPrimary = collect($this->contacts)->contains('is_primary', true);
            if (!$hasPrimary && count($this->contacts) > 0) {
                $contacts = $this->contacts;
                $contacts[0]['is_primary'] = true;
                $this->merge(['contacts' => $contacts]);
            }
        }
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Ajouter des valeurs par défaut
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['payment_terms'] = $validated['payment_terms'] ?? 30;
        $validated['payment_method'] = $validated['payment_method'] ?? 'bank_transfer';

        return $validated;
    }
}
