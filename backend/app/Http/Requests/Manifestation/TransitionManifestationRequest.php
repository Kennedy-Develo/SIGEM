<?php

namespace App\Http\Requests\Manifestation;

use App\Enums\ManifestationLifecycleAction;
use App\Enums\UserRole;
use App\Models\Manifestation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionManifestationRequest extends FormRequest
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

        if ($user->role !== UserRole::Operator) {
            return false;
        }

        $isResponsible = (
            $manifestation->current_assignee_id === $user->id
            || $manifestation->created_by_id === $user->id
        );

        if (! $isResponsible) {
            return false;
        }

        $action = ManifestationLifecycleAction::tryFrom(
            (string) $this->input('action'),
        );

        return in_array($action, [
            ManifestationLifecycleAction::Start,
            ManifestationLifecycleAction::Extend,
            ManifestationLifecycleAction::Forward,
            ManifestationLifecycleAction::Answer,
            ManifestationLifecycleAction::Complete,
        ], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Manifestation $manifestation */
        $manifestation = $this->route('manifestation');

        $action = ManifestationLifecycleAction::tryFrom(
            (string) $this->input('action'),
        );

        $deadlineComparison = $manifestation
            ->current_deadline_at
            ?->toDateString()
            ?? $manifestation
                ->opened_at
                ?->toDateString();

        $newDeadlineRules = [
            Rule::requiredIf(
                $action === ManifestationLifecycleAction::Extend,
            ),
            'nullable',
            'date',
        ];

        if ($deadlineComparison !== null) {
            $newDeadlineRules[] = 'after:'.$deadlineComparison;
        }

        return [
            'action' => [
                'required',
                Rule::enum(ManifestationLifecycleAction::class),
            ],

            'reason' => [
                Rule::requiredIf(
                    $action?->requiresReason() === true,
                ),
                'nullable',
                'string',
                'max:2000',
            ],

            'new_deadline_at' => $newDeadlineRules,

            'external_agency' => [
                Rule::requiredIf(
                    $action === ManifestationLifecycleAction::Forward,
                ),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Informe a ação que será realizada.',
            'action.enum' => 'A ação informada é inválida.',

            'reason.required' => 'Informe o motivo desta ação.',
            'reason.string' => 'O motivo deve ser um texto.',
            'reason.max' => 'O motivo não pode ultrapassar 2.000 caracteres.',

            'new_deadline_at.required' => 'Informe o novo prazo da manifestação.',
            'new_deadline_at.date' => 'O novo prazo informado é inválido.',
            'new_deadline_at.after' => 'O novo prazo deve ser posterior ao prazo atual.',

            'external_agency.required' => 'Informe o órgão para o qual a manifestação foi encaminhada.',
            'external_agency.string' => 'O nome do órgão deve ser um texto.',
            'external_agency.max' => 'O nome do órgão não pode ultrapassar 255 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->action)) {
            $this->merge([
                'action' => strtolower(
                    trim($this->action),
                ),
            ]);
        }

        if (is_string($this->reason)) {
            $this->merge([
                'reason' => trim($this->reason),
            ]);
        }

        if (is_string($this->external_agency)) {
            $this->merge([
                'external_agency' => trim(
                    $this->external_agency,
                ),
            ]);
        }
    }
}
