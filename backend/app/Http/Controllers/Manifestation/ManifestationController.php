<?php

namespace App\Http\Controllers\Manifestation;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manifestation\ListManifestationsRequest;
use App\Http\Requests\Manifestation\RestoreManifestationRequest;
use App\Http\Requests\Manifestation\StoreManifestationRequest;
use App\Http\Requests\Manifestation\TransitionManifestationRequest;
use App\Http\Requests\Manifestation\TrashManifestationRequest;
use App\Http\Requests\Manifestation\UpdateManifestationRequest;
use App\Models\Manifestation;
use App\Models\User;
use App\Services\ManifestationQueryService;
use App\Services\ManifestationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManifestationController extends Controller
{
    public function __construct(
        private readonly ManifestationService $manifestationService,
        private readonly ManifestationQueryService $manifestationQueryService,
    ) {}

    public function index(
        ListManifestationsRequest $request,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401, 'Usuário não autenticado.');
        }

        $result = $this->manifestationQueryService->search(
            $actor,
            $request->validated(),
        );

        return response()->json($result);
    }

    public function show(
        Request $request,
        Manifestation $manifestation,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401, 'Usuário não autenticado.');
        }

        $this->ensureUserCanView(
            $actor,
            $manifestation,
        );

        return response()->json([
            'manifestation' => $manifestation->load([
                'subject',
                'subsubject',
                'sector',
                'currentAssignee:id,name,email,role',
                'creator:id,name,email',
                'updater:id,name,email',
                'assignments' => fn ($query) => $query
                    ->with([
                        'assignee:id,name,email',
                        'assignedBy:id,name,email',
                        'endedBy:id,name,email',
                    ])
                    ->orderByDesc('assigned_at'),
            ]),
        ]);
    }

    public function store(
        StoreManifestationRequest $request,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401, 'Usuário não autenticado.');
        }

        $manifestation = $this->manifestationService->create(
            $request->validated(),
            $actor,
        );

        return response()->json([
            'message' => 'Manifestação cadastrada com sucesso.',
            'manifestation' => $manifestation,
        ], 201);
    }

    public function update(
        UpdateManifestationRequest $request,
        Manifestation $manifestation,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401, 'Usuário não autenticado.');
        }

        $updatedManifestation = $this->manifestationService->update(
            manifestation: $manifestation,
            data: $request->validated(),
            actor: $actor,
        );

        return response()->json([
            'message' => 'Manifestação atualizada com sucesso.',
            'manifestation' => $updatedManifestation,
        ]);
    }

    private function ensureUserCanView(
        User $user,
        Manifestation $manifestation,
    ): void {
        if ($user->role !== UserRole::Operator) {
            return;
        }

        $canView = $manifestation->current_assignee_id === $user->id
            || $manifestation->created_by_id === $user->id;

        abort_unless(
            $canView,
            403,
            'Você não possui permissão para visualizar esta manifestação.',
        );
    }

    public function transition(
        TransitionManifestationRequest $request,
        Manifestation $manifestation,
    ): JsonResponse {
        $manifestation = $this->manifestationService->transition(
            manifestation: $manifestation,
            actor: $request->user(),
            data: $request->validated(),
        );

        return response()->json([
            'message' => 'Ciclo de vida da manifestação atualizado com sucesso.',
            'manifestation' => $manifestation,
        ]);
    }

    public function trash(
        TrashManifestationRequest $request,
        Manifestation $manifestation,
    ): JsonResponse {
        $manifestation = $this->manifestationService->trash(
            manifestation: $manifestation,
            actor: $request->user(),
            reason: $request->validated('reason'),
        );

        return response()->json([
            'message' => 'Manifestação enviada para a lixeira com sucesso.',
            'manifestation' => $manifestation,
        ]);
    }

    public function restore(
        RestoreManifestationRequest $request,
        int $manifestation,
    ): JsonResponse {
        $manifestation = Manifestation::withTrashed()->findOrFail($manifestation);

        $manifestation = $this->manifestationService->restore(
            manifestation: $manifestation,
            actor: $request->user(),
            reason: $request->validated('reason'),
        );

        return response()->json([
            'message' => 'Manifestação restaurada com sucesso.',
            'manifestation' => $manifestation,
        ]);
    }
}
