<?php

namespace App\Http\Requests\Api\V1\Customer;

use App\Domain\Customer\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $category = $this->route('category');
        
        if (!$category || !$this->user()->company) {
            return false;
        }

        // Check if category belongs to user's company
        if ($category->company_id !== $this->user()->company->id) {
            return false;
        }

        // Check if user has permission to update categories
        return $this->user()->hasPermission('update_clients') || 
               $this->user()->role?->name === 'Directeur';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories')->where(function ($query) use ($category) {
                    return $query->where('company_id', $this->user()->company->id)
                               ->where('type', $category->type)
                               ->where('id', '!=', $category->id)
                               ->whereNull('deleted_at');
                })
            ],
            'parent_id' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id')->where(function ($query) use ($category) {
                    return $query->where('company_id', $this->user()->company->id)
                               ->where('type', $category->type)
                               ->where('id', '!=', $category->id)
                               ->whereNull('deleted_at');
                })
            ],
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
            'name.max' => 'Le nom de la catégorie ne doit pas dépasser 255 caractères.',
            'name.unique' => 'Une catégorie avec ce nom existe déjà.',
            'parent_id.exists' => 'La catégorie parente sélectionnée n\'existe pas.',
            'color.regex' => 'La couleur doit être au format hexadécimal (#000000).',
            'icon.max' => 'L\'icône ne doit pas dépasser 50 caractères.',
            'position.integer' => 'La position doit être un nombre entier.',
            'position.min' => 'La position doit être positive.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $category = $this->route('category');
            
            // Check for circular reference if parent_id is provided
            if ($this->parent_id && $category) {
                // Cannot set self as parent
                if ($this->parent_id === $category->id) {
                    $validator->errors()->add('parent_id', 'Une catégorie ne peut pas être son propre parent.');
                    return;
                }
                
                // Cannot set a child as parent
                $childIds = $category->children()->pluck('id')->toArray();
                if (in_array($this->parent_id, $childIds)) {
                    $validator->errors()->add('parent_id', 'Une catégorie ne peut pas avoir un de ses enfants comme parent.');
                    return;
                }
                
                // Check for depth limit (only 2 levels allowed)
                $parent = Category::find($this->parent_id);
                if ($parent && $parent->parent_id) {
                    $validator->errors()->add('parent_id', 'Les catégories ne peuvent avoir qu\'un seul niveau de hiérarchie.');
                }
            }
        });
    }
}
