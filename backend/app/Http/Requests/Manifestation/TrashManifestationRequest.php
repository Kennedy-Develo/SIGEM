<?php

namespace App\Http\Requests\Manifestation;

use App\Enums\UserRole;
use App\Models\Manifestation;
use Illuminate\Foundation\Http\FormRequest;

class TrashManifestationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $manifestation = $this->route('manifestation');

        if (
            $user === null
            || ! $user->isActive()
            || ! $manifestation instanceof Manifestation
        ) {
            return false;
        }

        return in_array($user->role, [
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
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Informe o motivo do envio para a lixeira.',
            'reason.string' => 'O motivo deve ser um texto.',
            'reason.min' => 'O motivo deve possuir pelo menos 10 caracteres.',
            'reason.max' => 'O motivo não pode ultrapassar 2.000 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->reason)) {
            $this->merge([
                'reason' => trim($this->reason),
            ]);
        }
    }
}
