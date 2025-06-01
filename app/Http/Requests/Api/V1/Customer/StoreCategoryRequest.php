<?php

namespace App\Http\Requests\Api\V1\Customer;

use App\Domain\Customer\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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

        // Check if user has permission to create categories
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company->id)
                               ->where('type', $this->type ?? 'client')
                               ->whereNull('deleted_at');
                })
            ],
            'parent_id' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) {
                    return $query->where('company_id', $this->user()->company->id)
                               ->where('type', $this->type ?? 'client')
                               ->whereNull('deleted_at');
                })
            ],
            'type' => ['required', 'string', Rule::in(['product', 'service', 'client', 'expense'])],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.max' => 'Le nom de la catégorie ne doit pas dépasser 255 caractères.',
            'name.unique' => 'Une catégorie avec ce nom existe déjà.',
            'parent_id.exists' => 'La catégorie parente sélectionnée n\'existe pas.',
            'type.required' => 'Le type de catégorie est obligatoire.',
            'type.in' => 'Le type de catégorie doit être product, service, client ou expense.',
            'color.regex' => 'La couleur doit être au format hexadécimal (#000000).',
            'icon.max' => 'L\'icône ne doit pas dépasser 50 caractères.',
            'position.integer' => 'La position doit être un nombre entier.',
            'position.min' => 'La position doit être positive.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default type if not provided
        if (!$this->has('type')) {
            $this->merge(['type' => 'client']);
        }

        // Set default position if not provided
        if (!$this->has('position')) {
            $lastPosition = Category::where('company_id', $this->user()->company->id)
                ->where('type', $this->type)
                ->max('position');
            
            $this->merge(['position' => ($lastPosition ?? 0) + 1]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check for circular reference if parent_id is provided
            if ($this->parent_id) {
                $parent = Category::find($this->parent_id);
                if ($parent && $parent->parent_id) {
                    // For now, we only allow 2 levels (parent -> child)
                    $validator->errors()->add('parent_id', 'Les catégories ne peuvent avoir qu\'un seul niveau de hiérarchie.');
                }
            }
        });
    }
}
