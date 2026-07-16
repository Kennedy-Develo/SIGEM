<?php

namespace App\Services;

use App\Enums\ManifestationStatus;
use App\Models\Manifestation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ManifestationService
{
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

            return $manifestation->load([
                'subject',
                'subsubject',
                'sector',
                'currentAssignee',
                'creator',
                'assignments.assignee',
            ]);
        });
    }
}
