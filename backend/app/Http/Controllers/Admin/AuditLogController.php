<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAuditLogsRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    /**
     * List audit records for administrative monitoring.
     */
    public function index(
        ListAuditLogsRequest $request,
    ): JsonResponse {
        $filters = $request->validated();
        $userMorphType = (new User)->getMorphClass();

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
                ) use ($userMorphType): void {
                    $query->where(
                        function (Builder $query) use (
                            $search,
                            $userMorphType,
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
                                                [
                                                    User::class,
                                                ],
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
                $subject = $auditLog->subject;

                return [
                    'id' => $auditLog->id,
                    'action' => $auditLog->action->value,
                    'action_label' => $auditLog->action->label(),
                    'actor' => $auditLog->actor
                        ? [
                            'id' => $auditLog->actor->id,
                            'name' => $auditLog->actor->name,
                            'email' => $auditLog->actor->email,
                        ]
                        : null,
                    'subject' => $subject instanceof User
                        ? [
                            'type' => 'user',
                            'id' => $subject->id,
                            'name' => $subject->name,
                            'email' => $subject->email,
                        ]
                        : [
                            'type' => $auditLog->subject_type,
                            'id' => $auditLog->subject_id,
                            'name' => null,
                            'email' => null,
                        ],
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
}
