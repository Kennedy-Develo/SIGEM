<?php

namespace App\Http\Requests\Admin;

use App\Enums\AuditAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAuditLogsRequest extends FormRequest
{
    /**
     * Authorization is handled by the authenticated administrator middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'action' => [
                'nullable',
                Rule::enum(AuditAction::class),
            ],
            'actor_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:from',
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
        ];
    }

    /**
     * Return validation messages in Portuguese.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.max' => 'A pesquisa deve possuir no máximo 100 caracteres.',
            'action.enum' => 'A ação de auditoria informada é inválida.',
            'actor_id.exists' => 'O responsável informado não foi encontrado.',
            'user_id.exists' => 'O usuário afetado não foi encontrado.',
            'from.date_format' => 'A data inicial deve estar no formato AAAA-MM-DD.',
            'to.date_format' => 'A data final deve estar no formato AAAA-MM-DD.',
            'to.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'per_page.in' => 'A quantidade por página deve ser 15, 25 ou 50.',
        ];
    }
}
