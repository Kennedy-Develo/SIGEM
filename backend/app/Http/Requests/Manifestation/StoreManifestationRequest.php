<?php

namespace App\Http\Requests\Manifestation;

use App\Enums\ManifestationSource;
use App\Enums\ManifestationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManifestationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->isActive()) {
            return false;
        }

        return in_array($user->role, [
            UserRole::Administrator,
            UserRole::Manager,
            UserRole::Operator,
        ], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nup' => [
                'required',
                'string',
                'size:17',
                'regex:/^\d{17}$/',
                Rule::unique('manifestations', 'nup'),
            ],
            'source' => [
                'required',
                Rule::enum(ManifestationSource::class),
            ],
            'type' => [
                'required',
                Rule::enum(ManifestationType::class),
            ],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')
                    ->where('active', true),
            ],
            'subsubject_id' => [
                'required',
                'integer',
                Rule::exists('subsubjects', 'id')
                    ->where('active', true)
                    ->where(
                        'subject_id',
                        $this->integer('subject_id'),
                    ),
            ],
            'sector_id' => [
                'required',
                'integer',
                Rule::exists('sectors', 'id')
                    ->where('active', true),
            ],
            'conclusion_responsible_area' => [
                'nullable',
                'string',
                'max:255',
            ],
            'current_assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where(
                        'status',
                        UserStatus::Active->value,
                    ),
            ],
            'summary' => [
                'nullable',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'opened_at' => [
                'required',
                'date',
            ],
            'original_deadline_at' => [
                'nullable',
                'date',
                'after_or_equal:opened_at',
            ],
            'current_deadline_at' => [
                'nullable',
                'date',
                'after_or_equal:opened_at',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nup.required' => 'Informe o número NUP.',
            'nup.size' => 'O NUP deve possuir exatamente 17 dígitos.',
            'nup.regex' => 'O NUP deve conter somente números.',
            'nup.unique' => 'Já existe uma manifestação cadastrada com este NUP.',

            'source.required' => 'Informe a origem da manifestação.',
            'source.enum' => 'A origem informada é inválida.',

            'type.required' => 'Informe o tipo da manifestação.',
            'type.enum' => 'O tipo informado é inválido.',

            'subject_id.required' => 'Selecione o assunto.',
            'subject_id.exists' => 'O assunto selecionado é inválido ou está inativo.',

            'subsubject_id.required' => 'Selecione o subassunto.',
            'subsubject_id.exists' => 'O subassunto selecionado não pertence ao assunto informado ou está inativo.',

            'sector_id.required' => 'Selecione a Tag ou o setor.',
            'sector_id.exists' => 'A Tag ou o setor selecionado é inválido ou está inativo.',

            'conclusion_responsible_area.string' => 'A área responsável pela resposta conclusiva deve ser um texto.',
            'conclusion_responsible_area.max' => 'A área responsável pela resposta conclusiva não pode ultrapassar 255 caracteres.',

            'current_assignee_id.exists' => 'O respondente selecionado é inválido ou não está ativo.',

            'summary.max' => 'O resumo não pode ultrapassar 255 caracteres.',
            'description.max' => 'A descrição não pode ultrapassar 10.000 caracteres.',

            'opened_at.required' => 'Informe a data de abertura.',
            'opened_at.date' => 'A data de abertura é inválida.',

            'original_deadline_at.date' => 'O prazo original é inválido.',
            'original_deadline_at.after_or_equal' => 'O prazo original não pode ser anterior à data de abertura.',

            'current_deadline_at.date' => 'O prazo atual é inválido.',
            'current_deadline_at.after_or_equal' => 'O prazo atual não pode ser anterior à data de abertura.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->nup)) {
            $this->merge([
                'nup' => preg_replace(
                    '/\D/',
                    '',
                    $this->nup,
                ),
            ]);
        }
    }
}
