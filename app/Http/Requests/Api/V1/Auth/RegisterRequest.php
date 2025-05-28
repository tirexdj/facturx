<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'company_name' => ['required', 'string', 'max:255'],
            'siren' => ['sometimes', 'nullable', 'string', 'size:9', 'regex:/^[0-9]{9}$/'],
            'siret' => ['sometimes', 'nullable', 'string', 'size:14', 'regex:/^[0-9]{14}$/'],
            'job_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'locale' => ['sometimes', 'string', 'in:fr,en'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'device_name' => ['sometimes', 'string', 'max:255'],
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
            'first_name.required' => 'Le prénom est obligatoire.',
            'first_name.max' => 'Le prénom ne peut pas dépasser 255 caractères.',
            'last_name.required' => 'Le nom est obligatoire.',
            'last_name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
            'email.max' => 'L\'adresse e-mail ne peut pas dépasser 255 caractères.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'company_name.required' => 'Le nom de l\'entreprise est obligatoire.',
            'company_name.max' => 'Le nom de l\'entreprise ne peut pas dépasser 255 caractères.',
            'siren.size' => 'Le numéro SIREN doit contenir exactement 9 chiffres.',
            'siren.regex' => 'Le numéro SIREN doit contenir uniquement des chiffres.',
            'siret.size' => 'Le numéro SIRET doit contenir exactement 14 chiffres.',
            'siret.regex' => 'Le numéro SIRET doit contenir uniquement des chiffres.',
            'job_title.max' => 'Le titre du poste ne peut pas dépasser 255 caractères.',
            'locale.in' => 'La langue doit être soit "fr" soit "en".',
            'timezone.timezone' => 'Le fuseau horaire doit être valide.',
            'device_name.max' => 'Le nom de l\'appareil ne peut pas dépasser 255 caractères.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional validation: if SIRET is provided, check if it starts with SIREN
            if ($this->siren && $this->siret) {
                if (!str_starts_with($this->siret, $this->siren)) {
                    $validator->errors()->add('siret', 'Le numéro SIRET doit commencer par le numéro SIREN.');
                }
            }
        });
    }
}
