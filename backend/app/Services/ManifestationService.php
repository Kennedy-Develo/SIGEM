<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\ManifestationStatus;
use App\Models\Manifestation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManifestationService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Cadastra uma manifestação e registra sua primeira atribuição.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Manifestation
    {
        return DB::transaction(function () use ($data, $actor) {
            $assigneeId = $data['current_assignee_id'] ?? null;

            $currentDeadline = $data['current_deadline_at']
                ?? $data['original_deadline_at']
                ?? null;

            $manifestation = Manifestation::create([
                ...$data,
                'status' => ManifestationStatus::Registered,
                'current_assignee_id' => $assigneeId,
                'current_deadline_at' => $currentDeadline,
                'created_by_id' => $actor->id,
                'updated_by_id' => $actor->id,
            ]);

            if ($assigneeId !== null) {
                $manifestation->assignments()->create([
                    'assignee_id' => $assigneeId,
                    'assigned_by_id' => $actor->id,
                    'assigned_at' => now(),
                    'assignment_reason' => 'Distribuição inicial.',
                ]);
            }

            return $this->loadRelations($manifestation);
        });
    }

    /**
     * Atualiza os dados gerais de uma manifestação.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(
        Manifestation $manifestation,
        array $data,
        User $actor,
    ): Manifestation {
        if ($manifestation->status === ManifestationStatus::Archived) {
            throw ValidationException::withMessages([
                'manifestation' => 'Uma manifestação arquivada não pode ser editada.',
            ]);
        }

        return DB::transaction(
            function () use (
                $manifestation,
                $data,
                $actor,
            ): Manifestation {
                $oldValues = $this->editableValues($manifestation);

                $oldAssigneeId = $manifestation->current_assignee_id;

                $newAssigneeId = array_key_exists(
                    'current_assignee_id',
                    $data,
                )
                    ? $data['current_assignee_id']
                    : $oldAssigneeId;

                $assigneeChanged = $oldAssigneeId !== $newAssigneeId;

                $manifestation->fill([
                    ...$data,
                    'updated_by_id' => $actor->id,
                ]);

                $manifestation->save();

                if ($assigneeChanged) {
                    $this->replaceAssignment(
                        manifestation: $manifestation,
                        oldAssigneeId: $oldAssigneeId,
                        newAssigneeId: $newAssigneeId,
                        actor: $actor,
                    );
                }

                $manifestation->refresh();

                $newValues = $this->editableValues($manifestation);

                $changedFields = array_keys(
                    array_filter(
                        $newValues,
                        static fn (
                            mixed $value,
                            string $field,
                        ): bool => $oldValues[$field] !== $value,
                        ARRAY_FILTER_USE_BOTH,
                    ),
                );

                if ($changedFields !== []) {
                    $this->auditLogger->record(
                        action: AuditAction::ManifestationUpdated,
                        subject: $manifestation,
                        actor: $actor,
                        oldValues: $oldValues,
                        newValues: $newValues,
                        metadata: [
                            'changed_fields' => $changedFields,
                            'nup' => $manifestation->nup,
                        ],
                    );
                }

                return $this->loadRelations($manifestation);
            },
        );
    }

    private function replaceAssignment(
        Manifestation $manifestation,
        ?int $oldAssigneeId,
        ?int $newAssigneeId,
        User $actor,
    ): void {
        if ($oldAssigneeId !== null) {
            $currentAssignment = $manifestation
                ->assignments()
                ->current()
                ->latest('assigned_at')
                ->first();

            if ($currentAssignment !== null) {
                $currentAssignment->update([
                    'ended_by_id' => $actor->id,
                    'ended_at' => now(),
                    'ending_reason' => 'Responsável alterado durante a edição.',
                ]);
            }
        }

        if ($newAssigneeId !== null) {
            $manifestation->assignments()->create([
                'assignee_id' => $newAssigneeId,
                'assigned_by_id' => $actor->id,
                'assigned_at' => now(),
                'assignment_reason' => 'Responsável alterado durante a edição.',
            ]);
        }
    }

    /**
     * Retorna os valores utilizados na auditoria.
     *
     * @return array<string, mixed>
     */
    private function editableValues(
        Manifestation $manifestation,
    ): array {
        return [
            'nup' => $manifestation->nup,
            'source' => $manifestation->source->value,
            'type' => $manifestation->type->value,
            'subject_id' => $manifestation->subject_id,
            'subsubject_id' => $manifestation->subsubject_id,
            'sector_id' => $manifestation->sector_id,
            'conclusion_responsible_area' => $manifestation->conclusion_responsible_area,
            'current_assignee_id' => $manifestation->current_assignee_id,
            'summary' => $manifestation->summary,
            'description' => $manifestation->description,
            'opened_at' => $manifestation->opened_at?->toDateString(),
            'original_deadline_at' => $manifestation->original_deadline_at?->toDateString(),
            'current_deadline_at' => $manifestation->current_deadline_at?->toDateString(),
        ];
    }

    private function loadRelations(
        Manifestation $manifestation,
    ): Manifestation {
        return $manifestation->load([
            'subject',
            'subsubject',
            'sector',
            'currentAssignee',
            'creator',
            'updater',
            'assignments.assignee',
            'assignments.assignedBy',
            'assignments.endedBy',
        ]);
    }
}
