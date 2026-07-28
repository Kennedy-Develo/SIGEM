<?php

namespace App\Http\Requests\Manifestation;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTrashedManifestationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->isActive()
            && in_array($user->role, [
                UserRole::Administrator,
                UserRole::Manager,
            ], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([
                    15,
                    25,
                    50,
                ]),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.string' => 'A pesquisa deve ser um texto.',
            'search.max' => 'A pesquisa não pode ultrapassar 255 caracteres.',

            'per_page.integer' => 'A quantidade por página deve ser um número inteiro.',
            'per_page.in' => 'Selecione uma quantidade válida por página.',

            'page.integer' => 'A página deve ser um número inteiro.',
            'page.min' => 'A página deve ser maior ou igual a 1.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->search)) {
            $this->merge([
                'search' => trim($this->search),
            ]);
        }
    }
}
