<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAuditLogsRequest;
use App\Models\AuditLog;
use App\Models\Manifestation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    /**
     * Lista os registros de auditoria para acompanhamento administrativo.
     */
    public function index(
        ListAuditLogsRequest $request,
    ): JsonResponse {
        $filters = $request->validated();

        $userMorphType = (new User)->getMorphClass();
        $manifestationMorphType = (new Manifestation)->getMorphClass();

        $auditLogs = AuditLog::query()
            ->with([
                'actor:id,name,email',
                'subject',
            ])
            ->when(
                $filters['search'] ?? null,
                function (
                    Builder $query,
                    string $search,
                ) use (
                    $userMorphType,
                    $manifestationMorphType,
                ): void {
                    $query->where(
                        function (Builder $query) use (
                            $search,
                            $userMorphType,
                            $manifestationMorphType,
                        ): void {
                            $query
                                ->whereHas(
                                    'actor',
                                    function (Builder $query) use (
                                        $search,
                                    ): void {
                                        $query
                                            ->where(
                                                'name',
                                                'like',
                                                "%{$search}%",
                                            )
                                            ->orWhere(
                                                'email',
                                                'like',
                                                "%{$search}%",
                                            );
                                    },
                                )
                                ->orWhere(
                                    function (Builder $query) use (
                                        $search,
                                        $userMorphType,
                                    ): void {
                                        $query
                                            ->where(
                                                'subject_type',
                                                $userMorphType,
                                            )
                                            ->whereHasMorph(
                                                'subject',
                                                [User::class],
                                                function (
                                                    Builder $query,
                                                ) use ($search): void {
                                                    $query
                                                        ->where(
                                                            'name',
                                                            'like',
                                                            "%{$search}%",
                                                        )
                                                        ->orWhere(
                                                            'email',
                                                            'like',
                                                            "%{$search}%",
                                                        );
                                                },
                                            );
                                    },
                                )
                                ->orWhere(
                                    function (Builder $query) use (
                                        $search,
                                        $manifestationMorphType,
                                    ): void {
                                        $query
                                            ->where(
                                                'subject_type',
                                                $manifestationMorphType,
                                            )
                                            ->whereHasMorph(
                                                'subject',
                                                [Manifestation::class],
                                                function (
                                                    Builder $query,
                                                ) use ($search): void {
                                                    $query
                                                        ->where(
                                                            'nup',
                                                            'like',
                                                            "%{$search}%",
                                                        )
                                                        ->orWhere(
                                                            'summary',
                                                            'like',
                                                            "%{$search}%",
                                                        );
                                                },
                                            );
                                    },
                                );
                        },
                    );
                },
            )
            ->when(
                $filters['action'] ?? null,
                fn (
                    Builder $query,
                    string $action,
                ) => $query->where(
                    'action',
                    $action,
                ),
            )
            ->when(
                $filters['actor_id'] ?? null,
                fn (
                    Builder $query,
                    int $actorId,
                ) => $query->where(
                    'actor_id',
                    $actorId,
                ),
            )
            ->when(
                $filters['user_id'] ?? null,
                fn (
                    Builder $query,
                    int $userId,
                ) => $query
                    ->where(
                        'subject_type',
                        $userMorphType,
                    )
                    ->where(
                        'subject_id',
                        $userId,
                    ),
            )
            ->when(
                $filters['from'] ?? null,
                fn (
                    Builder $query,
                    string $from,
                ) => $query->whereDate(
                    'created_at',
                    '>=',
                    $from,
                ),
            )
            ->when(
                $filters['to'] ?? null,
                fn (
                    Builder $query,
                    string $to,
                ) => $query->whereDate(
                    'created_at',
                    '<=',
                    $to,
                ),
            )
            ->latest('created_at')
            ->latest('id')
            ->paginate(
                $filters['per_page'] ?? 15,
            )
            ->withQueryString();

        $auditLogs->through(
            function (AuditLog $auditLog): array {
                return [
                    'id' => $auditLog->id,
                    'action' => $auditLog->action->value,
                    'action_label' => $auditLog->action->label(),
                    'actor' => $this->formatActor($auditLog),
                    'subject' => $this->formatSubject($auditLog),
                    'old_values' => $auditLog->old_values,
                    'new_values' => $auditLog->new_values,
                    'metadata' => $auditLog->metadata,
                    'ip_address' => $auditLog->ip_address,
                    'user_agent' => $auditLog->user_agent,
                    'created_at' => $auditLog
                        ->created_at
                        ?->toISOString(),
                ];
            },
        );

        return response()->json($auditLogs);
    }

    /**
     * Formata o responsável pela ação auditada.
     *
     * @return array{id: int, name: string, email: string}|null
     */
    private function formatActor(
        AuditLog $auditLog,
    ): ?array {
        if (! $auditLog->actor) {
            return null;
        }

        return [
            'id' => $auditLog->actor->id,
            'name' => $auditLog->actor->name,
            'email' => $auditLog->actor->email,
        ];
    }

    /**
     * Formata o registro afetado pela ação auditada.
     *
     * @return array{
     *     type: string,
     *     id: int|string|null,
     *     name: string|null,
     *     email: string|null,
     *     nup: string|null
     * }
     */
    private function formatSubject(
        AuditLog $auditLog,
    ): array {
        $subject = $auditLog->subject;

        if ($subject instanceof User) {
            return [
                'type' => 'user',
                'id' => $subject->id,
                'name' => $subject->name,
                'email' => $subject->email,
                'nup' => null,
            ];
        }

        if ($subject instanceof Manifestation) {
            return [
                'type' => 'manifestation',
                'id' => $subject->id,
                'name' => "Manifestação {$subject->nup}",
                'email' => null,
                'nup' => $subject->nup,
            ];
        }

        return [
            'type' => $this->resolveSubjectType(
                $auditLog->subject_type,
            ),
            'id' => $auditLog->subject_id,
            'name' => null,
            'email' => null,
            'nup' => null,
        ];
    }

    /**
     * Converte o nome interno do modelo em um identificador para o frontend.
     */
    private function resolveSubjectType(
        ?string $subjectType,
    ): string {
        return match ($subjectType) {
            User::class,
            (new User)->getMorphClass() => 'user',

            Manifestation::class,
            (new Manifestation)->getMorphClass() => 'manifestation',

            default => $subjectType ?? 'unknown',
        };
    }
}
