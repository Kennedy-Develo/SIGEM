<?php

namespace App\Http\Controllers\Manifestation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manifestation\ListManifestationsRequest;
use App\Http\Requests\Manifestation\StoreManifestationRequest;
use App\Models\User;
use App\Services\ManifestationQueryService;
use App\Services\ManifestationService;
use Illuminate\Http\JsonResponse;

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
}
