<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserAccessRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * List users for administrative management.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'role' => [
                'nullable',
                Rule::enum(UserRole::class),
            ],
            'status' => [
                'nullable',
                Rule::enum(UserStatus::class),
            ],
        ]);

        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'status',
                'approved_by',
                'approved_at',
                'blocked_at',
                'last_login_at',
                'created_at',
            ])
            ->with('approver:id,name')
            ->when(
                $filters['search'] ?? null,
                function (Builder $query, string $search): void {
                    $query->where(
                        function (Builder $query) use ($search): void {
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
            ->when(
                $filters['role'] ?? null,
                fn (Builder $query, string $role) => $query->where(
                    'role',
                    $role,
                ),
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status) => $query->where(
                    'status',
                    $status,
                ),
            )
            ->orderByRaw(
                "case status
                    when 'pending' then 1
                    when 'active' then 2
                    when 'blocked' then 3
                    else 4
                end",
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return response()->json($users);
    }

    /**
     * Update a user's role and access status.
     */
    public function update(
        UpdateUserAccessRequest $request,
        User $user,
    ): JsonResponse {
        /** @var User $administrator */
        $administrator = $request->user();

        if ($administrator->is($user)) {
            return response()->json([
                'message' => 'Você não pode alterar seu próprio perfil ou status.',
            ], 422);
        }

        $data = $request->validated();
        $status = UserStatus::from($data['status']);

        $oldValues = $this->accessValues($user);

        $updatedUser = DB::transaction(
            function () use (
                $administrator,
                $data,
                $status,
                $user,
                $oldValues,
            ): User {
                $attributes = [
                    'role' => UserRole::from($data['role']),
                    'status' => $status,
                ];

                if ($status === UserStatus::Active) {
                    $attributes['approved_by'] = $administrator->id;
                    $attributes['approved_at'] = $user->approved_at ?? now();
                    $attributes['blocked_at'] = null;
                }

                if ($status === UserStatus::Blocked) {
                    $attributes['blocked_at'] = now();
                }

                $user->forceFill($attributes)->save();

                if ($status === UserStatus::Blocked) {
                    $user->tokens()->delete();

                    if (config('session.driver') === 'database') {
                        DB::table(
                            config(
                                'session.table',
                                'sessions',
                            ),
                        )
                            ->where('user_id', $user->id)
                            ->delete();
                    }
                }

                /** @var User $freshUser */
                $freshUser = $user->fresh();

                $newValues = $this->accessValues($freshUser);

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

                $this->auditLogger->record(
                    action: AuditAction::UserAccessUpdated,
                    subject: $freshUser,
                    actor: $administrator,
                    oldValues: $oldValues,
                    newValues: $newValues,
                    metadata: [
                        'changed_fields' => $changedFields,
                    ],
                );

                return $freshUser;
            },
        );

        return response()->json([
            'message' => 'Acesso do usuário atualizado com sucesso.',
            'user' => $updatedUser->load('approver:id,name'),
        ]);
    }

    /**
     * Return the access data stored in the audit record.
     *
     * @return array<string, mixed>
     */
    private function accessValues(User $user): array
    {
        return [
            'role' => $user->role->value,
            'status' => $user->status->value,
            'approved_by' => $user->approved_by,
            'approved_at' => $user->approved_at?->toISOString(),
            'blocked_at' => $user->blocked_at?->toISOString(),
        ];
    }
}
