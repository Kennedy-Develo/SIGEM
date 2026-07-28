<?php

namespace App\Http\Controllers\Manifestation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manifestation\ListTrashedManifestationsRequest;
use App\Models\Manifestation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ManifestationTrashController extends Controller
{
    /**
     * Lista as manifestações enviadas para a lixeira.
     */
    public function index(
        ListTrashedManifestationsRequest $request,
    ): JsonResponse {
        $filters = $request->validated();

        $manifestations = Manifestation::query()
            ->onlyTrashed()
            ->with([
                'subject',
                'subsubject',
                'sector',
                'currentAssignee',
                'creator',
            ])
            ->when(
                $filters['search'] ?? null,
                function (
                    Builder $query,
                    string $search,
                ): void {
                    $query->where(
                        function (Builder $query) use ($search): void {
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
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    "%{$search}%",
                                )
                                ->orWhere(
                                    'conclusion_responsible_area',
                                    'like',
                                    "%{$search}%",
                                )
                                ->orWhereHas(
                                    'subject',
                                    function (Builder $query) use ($search): void {
                                        $query->where(
                                            'name',
                                            'like',
                                            "%{$search}%",
                                        );
                                    },
                                )
                                ->orWhereHas(
                                    'subsubject',
                                    function (Builder $query) use ($search): void {
                                        $query->where(
                                            'name',
                                            'like',
                                            "%{$search}%",
                                        );
                                    },
                                )
                                ->orWhereHas(
                                    'sector',
                                    function (Builder $query) use ($search): void {
                                        $query
                                            ->where(
                                                'acronym',
                                                'like',
                                                "%{$search}%",
                                            )
                                            ->orWhere(
                                                'name',
                                                'like',
                                                "%{$search}%",
                                            );
                                    },
                                )
                                ->orWhereHas(
                                    'currentAssignee',
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
                    );
                },
            )
            ->latest('deleted_at')
            ->latest('id')
            ->paginate(
                $filters['per_page'] ?? 15,
            )
            ->withQueryString();

        return response()->json($manifestations);
    }
}
