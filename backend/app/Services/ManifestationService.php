<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\ManifestationLifecycleAction;
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

    /**
     * Executa uma ação do ciclo de vida da manifestação.
     *
     * @param  array<string, mixed>  $data
     */
    public function transition(
        Manifestation $manifestation,
        array $data,
        User $actor,
    ): Manifestation {
        $action = ManifestationLifecycleAction::from(
            $data['action'],
        );

        $this->ensureTransitionIsAllowed(
            manifestation: $manifestation,
            action: $action,
        );

        return DB::transaction(
            function () use (
                $manifestation,
                $data,
                $actor,
                $action,
            ): Manifestation {
                $oldValues = $this->lifecycleValues(
                    $manifestation,
                );

                $this->applyLifecycleAction(
                    manifestation: $manifestation,
                    action: $action,
                    data: $data,
                );

                $manifestation->updated_by_id = $actor->id;
                $manifestation->save();
                $manifestation->refresh();

                $newValues = $this->lifecycleValues(
                    $manifestation,
                );

                $this->auditLogger->record(
                    action: AuditAction::ManifestationLifecycleChanged,
                    subject: $manifestation,
                    actor: $actor,
                    oldValues: $oldValues,
                    newValues: $newValues,
                    metadata: [
                        'lifecycle_action' => $action->value,
                        'lifecycle_action_label' => $action->label(),
                        'reason' => $data['reason'] ?? null,
                        'nup' => $manifestation->nup,
                    ],
                );

                return $this->loadRelations($manifestation);
            },
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyLifecycleAction(
        Manifestation $manifestation,
        ManifestationLifecycleAction $action,
        array $data,
    ): void {
        match ($action) {
            ManifestationLifecycleAction::Start => $this->start(
                $manifestation,
            ),

            ManifestationLifecycleAction::Extend => $this->extend(
                manifestation: $manifestation,
                newDeadline: $data['new_deadline_at'],
                reason: $data['reason'],
            ),

            ManifestationLifecycleAction::Forward => $this->forward(
                manifestation: $manifestation,
                externalAgency: $data['external_agency'],
            ),

            ManifestationLifecycleAction::Answer => $this->answer(
                $manifestation,
            ),

            ManifestationLifecycleAction::Complete => $this->complete(
                $manifestation,
            ),

            ManifestationLifecycleAction::Archive => $this->archive(
                $manifestation,
            ),

            ManifestationLifecycleAction::Reopen => $this->reopen(
                $manifestation,
            ),
        };
    }

    private function start(
        Manifestation $manifestation,
    ): void {
        $manifestation->status = ManifestationStatus::InProgress;
    }

    private function extend(
        Manifestation $manifestation,
        string $newDeadline,
        string $reason,
    ): void {
        $manifestation->current_deadline_at = $newDeadline;
        $manifestation->extended_at = now();
        $manifestation->extension_reason = $reason;

        $this->moveRegisteredToInProgress($manifestation);
    }

    private function forward(
        Manifestation $manifestation,
        string $externalAgency,
    ): void {
        $manifestation->forwarded_to_external_agency_at = now();
        $manifestation->external_agency = $externalAgency;

        $this->moveRegisteredToInProgress($manifestation);
    }

    private function answer(
        Manifestation $manifestation,
    ): void {
        $manifestation->answered_by_ombudsman_at = now();

        $this->moveRegisteredToInProgress($manifestation);
    }

    private function complete(
        Manifestation $manifestation,
    ): void {
        $manifestation->status = ManifestationStatus::Completed;
        $manifestation->completed_at = now();
        $manifestation->archived_at = null;
    }

    private function archive(
        Manifestation $manifestation,
    ): void {
        $manifestation->status = ManifestationStatus::Archived;
        $manifestation->archived_at = now();
    }

    private function reopen(
        Manifestation $manifestation,
    ): void {
        $manifestation->status = ManifestationStatus::InProgress;
        $manifestation->completed_at = null;
        $manifestation->archived_at = null;
    }

    private function moveRegisteredToInProgress(
        Manifestation $manifestation,
    ): void {
        if (
            $manifestation->status
            === ManifestationStatus::Registered
        ) {
            $manifestation->status = ManifestationStatus::InProgress;
        }
    }

    private function ensureTransitionIsAllowed(
        Manifestation $manifestation,
        ManifestationLifecycleAction $action,
    ): void {
        $status = $manifestation->status;

        $allowed = match ($action) {
            ManifestationLifecycleAction::Start => $status === ManifestationStatus::Registered,

            ManifestationLifecycleAction::Extend,
            ManifestationLifecycleAction::Forward,
            ManifestationLifecycleAction::Answer => in_array($status, [
                ManifestationStatus::Registered,
                ManifestationStatus::InProgress,
            ], true),

            ManifestationLifecycleAction::Complete => in_array($status, [
                ManifestationStatus::Registered,
                ManifestationStatus::InProgress,
            ], true),

            ManifestationLifecycleAction::Archive => $status === ManifestationStatus::Completed,

            ManifestationLifecycleAction::Reopen => in_array($status, [
                ManifestationStatus::Completed,
                ManifestationStatus::Archived,
            ], true),
        };

        if ($allowed) {
            return;
        }

        throw ValidationException::withMessages([
            'action' => 'Esta ação não é permitida para a situação atual da manifestação.',
        ]);
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
     * Retorna os valores utilizados na auditoria da edição.
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

    /**
     * Retorna os valores utilizados na auditoria do ciclo de vida.
     *
     * @return array<string, mixed>
     */
    private function lifecycleValues(
        Manifestation $manifestation,
    ): array {
        return [
            'status' => $manifestation->status->value,
            'current_deadline_at' => $manifestation
                ->current_deadline_at
                ?->toDateString(),
            'extended_at' => $manifestation
                ->extended_at
                ?->toDateTimeString(),
            'extension_reason' => $manifestation->extension_reason,
            'forwarded_to_external_agency_at' => $manifestation
                ->forwarded_to_external_agency_at
                ?->toDateTimeString(),
            'external_agency' => $manifestation->external_agency,
            'answered_by_ombudsman_at' => $manifestation
                ->answered_by_ombudsman_at
                ?->toDateTimeString(),
            'completed_at' => $manifestation
                ->completed_at
                ?->toDateTimeString(),
            'archived_at' => $manifestation
                ->archived_at
                ?->toDateTimeString(),
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
