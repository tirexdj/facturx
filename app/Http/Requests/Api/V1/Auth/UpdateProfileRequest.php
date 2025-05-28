<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
        $userId = auth()->id();

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', "unique:users,email,{$userId}"],
            'job_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'locale' => ['sometimes', 'string', 'in:fr,en'],
            'timezone' => ['sometimes', 'string', 'timezone'],
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
            'first_name.max' => 'Le prénom ne peut pas dépasser 255 caractères.',
            'last_name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
            'email.max' => 'L\'adresse e-mail ne peut pas dépasser 255 caractères.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'job_title.max' => 'Le titre du poste ne peut pas dépasser 255 caractères.',
            'locale.in' => 'La langue doit être soit "fr" soit "en".',
            'timezone.timezone' => 'Le fuseau horaire doit être valide.',
        ];
    }
}
