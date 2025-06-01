<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class ImportClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // L'autorisation est gérée dans le controller via les policies
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx,xls',
                'max:5120' // 5MB max
            ],
            'skip_header' => 'nullable|boolean',
            'delimiter' => 'nullable|string|in:;,||\t',
            'encoding' => 'nullable|string|in:UTF-8,ISO-8859-1,Windows-1252',
            'update_existing' => 'nullable|boolean',
            'mapping' => 'nullable|array',
            'mapping.name' => 'nullable|integer|min:0',
            'mapping.email' => 'nullable|integer|min:0',
            'mapping.phone' => 'nullable|integer|min:0',
            'mapping.siren' => 'nullable|integer|min:0',
            'mapping.siret' => 'nullable|integer|min:0',
            'mapping.address_line1' => 'nullable|integer|min:0',
            'mapping.address_postal_code' => 'nullable|integer|min:0',
            'mapping.address_city' => 'nullable|integer|min:0',
            'mapping.address_country' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Le fichier est obligatoire.',
            'file.mimes' => 'Le fichier doit être au format CSV, TXT, XLS ou XLSX.',
            'file.max' => 'Le fichier ne doit pas dépasser 5 MB.',
            'delimiter.in' => 'Le délimiteur doit être ; , | ou une tabulation.',
            'encoding.in' => 'L\'encodage doit être UTF-8, ISO-8859-1 ou Windows-1252.',
            'mapping.*.min' => 'L\'index de colonne doit être supérieur ou égal à 0.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Valeurs par défaut
        $validated['skip_header'] = $validated['skip_header'] ?? true;
        $validated['delimiter'] = $validated['delimiter'] ?? ';';
        $validated['encoding'] = $validated['encoding'] ?? 'UTF-8';
        $validated['update_existing'] = $validated['update_existing'] ?? false;

        return $validated;
    }
}
