<?php

namespace App\Http\Requests\Api\V1\Quote;

use Illuminate\Foundation\Http\FormRequest;

class SendQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Vérifier que l'utilisateur appartient à une entreprise
        // et que le devis appartient à cette entreprise
        if (!auth()->check() || auth()->user()->company_id === null) {
            return false;
        }

        $quote = $this->route('quote');
        return $quote && $quote->company_id === auth()->user()->company_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email:rfc,dns',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:5000',
            'copy_to_sender' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'subject.max' => 'L\'objet ne peut pas dépasser 255 caractères.',
            'message.max' => 'Le message ne peut pas dépasser 5000 caractères.',
            'copy_to_sender.boolean' => 'La copie à l\'expéditeur doit être un booléen.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => 'adresse email',
            'subject' => 'objet',
            'message' => 'message',
            'copy_to_sender' => 'copie à l\'expéditeur',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Si aucun email n'est fourni, utiliser celui du client
        if (!$this->has('email') && $this->route('quote')?->customer?->email) {
            $this->merge([
                'email' => $this->route('quote')->customer->email
            ]);
        }

        // Si aucun objet n'est fourni, générer un objet par défaut
        if (!$this->has('subject') && $this->route('quote')) {
            $this->merge([
                'subject' => "Devis {$this->route('quote')->quote_number}"
            ]);
        }
    }
}
