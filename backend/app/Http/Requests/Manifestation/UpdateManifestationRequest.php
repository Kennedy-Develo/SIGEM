<?php

namespace App\Http\Requests\Manifestation;

use App\Enums\ManifestationSource;
use App\Enums\ManifestationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Manifestation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManifestationRequest extends FormRequest
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

        if (in_array($user->role, [
            UserRole::Administrator,
            UserRole::Manager,
        ], true)) {
            return true;
        }

        return $user->role === UserRole::Operator
            && (
                $manifestation->current_assignee_id === $user->id
                || $manifestation->created_by_id === $user->id
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Manifestation $manifestation */
        $manifestation = $this->route('manifestation');

        $subjectId = $this->has('subject_id')
            ? $this->integer('subject_id')
            : $manifestation->subject_id;

        $openedAt = $this->input(
            'opened_at',
            $manifestation->opened_at?->format('Y-m-d'),
        );

        return [
            'nup' => [
                'sometimes',
                'required',
                'string',
                'size:17',
                'regex:/^\d{17}$/',
                Rule::unique('manifestations', 'nup')
                    ->ignore($manifestation->id),
            ],
            'source' => [
                'sometimes',
                'required',
                Rule::enum(ManifestationSource::class),
            ],
            'type' => [
                'sometimes',
                'required',
                Rule::enum(ManifestationType::class),
            ],
            'subject_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('subjects', 'id')
                    ->where('active', true),
            ],
            'subsubject_id' => [
                Rule::requiredIf(
                    $this->has('subject_id')
                    || $this->has('subsubject_id'),
                ),
                'integer',
                Rule::exists('subsubjects', 'id')
                    ->where('active', true)
                    ->where('subject_id', $subjectId),
            ],
            'sector_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('sectors', 'id')
                    ->where('active', true),
            ],
            'conclusion_responsible_area' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'current_assignee_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where(
                        'status',
                        UserStatus::Active->value,
                    ),
            ],
            'summary' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:10000',
            ],
            'opened_at' => [
                'sometimes',
                'required',
                'date',
            ],
            'original_deadline_at' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:'.$openedAt,
            ],
            'current_deadline_at' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:'.$openedAt,
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
            'subsubject_id.exists' => 'O subassunto não pertence ao assunto informado ou está inativo.',

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
