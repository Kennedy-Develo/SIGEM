<?php

namespace App\Http\Requests\Manifestation;

use App\Enums\ManifestationSource;
use App\Enums\ManifestationStatus;
use App\Enums\ManifestationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListManifestationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() === true;
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
                'max:150',
            ],
            'source' => [
                'nullable',
                Rule::enum(ManifestationSource::class),
            ],
            'type' => [
                'nullable',
                Rule::enum(ManifestationType::class),
            ],
            'status' => [
                'nullable',
                Rule::enum(ManifestationStatus::class),
            ],
            'subject_id' => [
                'nullable',
                'integer',
                Rule::exists('subjects', 'id'),
            ],
            'subsubject_id' => [
                'nullable',
                'integer',
                Rule::exists('subsubjects', 'id'),
            ],
            'sector_id' => [
                'nullable',
                'integer',
                Rule::exists('sectors', 'id'),
            ],
            'current_assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'opened_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'opened_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:opened_from',
            ],
            'deadline_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'deadline_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:deadline_from',
            ],
            'deadline_status' => [
                'nullable',
                Rule::in([
                    'overdue',
                    'today',
                    'next_7_days',
                ]),
            ],
            'is_extended' => [
                'nullable',
                'boolean',
            ],
            'is_forwarded' => [
                'nullable',
                'boolean',
            ],
            'is_answered_by_ombudsman' => [
                'nullable',
                'boolean',
            ],
            'sort_by' => [
                'nullable',
                Rule::in([
                    'nup',
                    'opened_at',
                    'current_deadline_at',
                    'created_at',
                ]),
            ],
            'sort_direction' => [
                'nullable',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
            'per_page' => [
                'nullable',
                'integer',
                Rule::in([
                    10,
                    15,
                    25,
                    50,
                    100,
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
            'search.max' => 'A pesquisa não pode ultrapassar 150 caracteres.',

            'source.enum' => 'A origem selecionada é inválida.',
            'type.enum' => 'O tipo selecionado é inválido.',
            'status.enum' => 'A situação selecionada é inválida.',

            'subject_id.exists' => 'O assunto selecionado não existe.',
            'subsubject_id.exists' => 'O subassunto selecionado não existe.',
            'sector_id.exists' => 'A Tag ou o setor selecionado não existe.',
            'current_assignee_id.exists' => 'O respondente selecionado não existe.',

            'opened_from.date_format' => 'A data inicial de abertura deve estar no formato ano-mês-dia.',
            'opened_to.date_format' => 'A data final de abertura deve estar no formato ano-mês-dia.',
            'opened_to.after_or_equal' => 'A data final de abertura não pode ser anterior à data inicial.',

            'deadline_from.date_format' => 'A data inicial do prazo deve estar no formato ano-mês-dia.',
            'deadline_to.date_format' => 'A data final do prazo deve estar no formato ano-mês-dia.',
            'deadline_to.after_or_equal' => 'A data final do prazo não pode ser anterior à data inicial.',

            'deadline_status.in' => 'O filtro de prazo selecionado é inválido.',

            'is_extended.boolean' => 'O filtro de prorrogação é inválido.',
            'is_forwarded.boolean' => 'O filtro de encaminhamento é inválido.',
            'is_answered_by_ombudsman.boolean' => 'O filtro de resposta da Ouvidoria é inválido.',

            'sort_by.in' => 'O campo de ordenação selecionado é inválido.',
            'sort_direction.in' => 'A direção da ordenação é inválida.',

            'per_page.in' => 'Selecione uma quantidade válida de registros por página.',
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
