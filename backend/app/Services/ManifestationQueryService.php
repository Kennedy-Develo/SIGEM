<?php

namespace App\Services;

use App\Enums\ManifestationStatus;
use App\Enums\UserRole;
use App\Models\Manifestation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ManifestationQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     manifestations: LengthAwarePaginator,
     *     indicators: array<string, int>
     * }
     */
    public function search(
        User $user,
        array $filters,
    ): array {
        $query = Manifestation::query()
            ->with([
                'subject:id,name',
                'subsubject:id,subject_id,name',
                'sector:id,acronym,name',
                'currentAssignee:id,name,email,role',
            ]);

        $this->applyVisibility($query, $user);
        $this->applyGeneralFilters($query, $filters);

        $indicators = $this->buildIndicators(
            clone $query,
        );

        $this->applyDeadlineStatus(
            $query,
            $filters['deadline_status'] ?? null,
        );

        $this->applySorting($query, $filters);

        $perPage = (int) ($filters['per_page'] ?? 15);

        $manifestations = $query
            ->paginate($perPage)
            ->withQueryString();

        return [
            'manifestations' => $manifestations,
            'indicators' => $indicators,
        ];
    }

    private function applyVisibility(
        Builder $query,
        User $user,
    ): void {
        if ($user->role !== UserRole::Operator) {
            return;
        }

        $query->where(function (Builder $visibility) use ($user): void {
            $visibility
                ->where(
                    'current_assignee_id',
                    $user->id,
                )
                ->orWhere(
                    'created_by_id',
                    $user->id,
                );
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyGeneralFilters(
        Builder $query,
        array $filters,
    ): void {
        $search = $filters['search'] ?? null;

        if (is_string($search) && $search !== '') {
            $query->where(
                function (Builder $searchQuery) use ($search): void {
                    $term = "%{$search}%";

                    $searchQuery
                        ->where('nup', 'like', $term)
                        ->orWhere('summary', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere(
                            'external_agency',
                            'like',
                            $term,
                        )
                        ->orWhere(
                            'conclusion_responsible_area',
                            'like',
                            $term,
                        )
                        ->orWhereHas(
                            'subject',
                            fn (Builder $subjectQuery) => $subjectQuery
                                ->where('name', 'like', $term),
                        )
                        ->orWhereHas(
                            'subsubject',
                            fn (Builder $subsubjectQuery) => $subsubjectQuery
                                ->where('name', 'like', $term),
                        )
                        ->orWhereHas(
                            'sector',
                            fn (Builder $sectorQuery) => $sectorQuery
                                ->where('acronym', 'like', $term)
                                ->orWhere('name', 'like', $term),
                        )
                        ->orWhereHas(
                            'currentAssignee',
                            fn (Builder $userQuery) => $userQuery
                                ->where('name', 'like', $term)
                                ->orWhere('email', 'like', $term),
                        );
                },
            );
        }

        $simpleFilters = [
            'source',
            'type',
            'status',
            'subject_id',
            'subsubject_id',
            'sector_id',
            'current_assignee_id',
        ];

        foreach ($simpleFilters as $field) {
            if (isset($filters[$field])) {
                $query->where(
                    $field,
                    $filters[$field],
                );
            }
        }

        if (isset($filters['opened_from'])) {
            $query->whereDate(
                'opened_at',
                '>=',
                $filters['opened_from'],
            );
        }

        if (isset($filters['opened_to'])) {
            $query->whereDate(
                'opened_at',
                '<=',
                $filters['opened_to'],
            );
        }

        if (isset($filters['deadline_from'])) {
            $query->whereDate(
                'current_deadline_at',
                '>=',
                $filters['deadline_from'],
            );
        }

        if (isset($filters['deadline_to'])) {
            $query->whereDate(
                'current_deadline_at',
                '<=',
                $filters['deadline_to'],
            );
        }

        $this->applyNullableDateFilter(
            $query,
            $filters,
            'is_extended',
            'extended_at',
        );

        $this->applyNullableDateFilter(
            $query,
            $filters,
            'is_forwarded',
            'forwarded_to_external_agency_at',
        );

        $this->applyNullableDateFilter(
            $query,
            $filters,
            'is_answered_by_ombudsman',
            'answered_by_ombudsman_at',
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyNullableDateFilter(
        Builder $query,
        array $filters,
        string $filter,
        string $column,
    ): void {
        if (! array_key_exists($filter, $filters)) {
            return;
        }

        if ((bool) $filters[$filter]) {
            $query->whereNotNull($column);

            return;
        }

        $query->whereNull($column);
    }

    private function applyDeadlineStatus(
        Builder $query,
        mixed $deadlineStatus,
    ): void {
        if (! is_string($deadlineStatus)) {
            return;
        }

        $this->onlyOpenManifestations($query);

        match ($deadlineStatus) {
            'overdue' => $query->whereDate(
                'current_deadline_at',
                '<',
                today()->toDateString(),
            ),
            'today' => $query->whereDate(
                'current_deadline_at',
                today()->toDateString(),
            ),
            'next_7_days' => $query->whereBetween(
                'current_deadline_at',
                [
                    today()->addDay()->startOfDay(),
                    today()->addDays(7)->endOfDay(),
                ],
            ),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applySorting(
        Builder $query,
        array $filters,
    ): void {
        $sortBy = (string) (
            $filters['sort_by']
            ?? 'current_deadline_at'
        );

        $sortDirection = (string) (
            $filters['sort_direction']
            ?? 'asc'
        );

        if ($sortBy === 'current_deadline_at') {
            $query->orderByRaw(
                'current_deadline_at IS NULL',
            );
        }

        $query
            ->orderBy($sortBy, $sortDirection)
            ->orderByDesc('id');
    }

    /**
     * @return array<string, int>
     */
    private function buildIndicators(
        Builder $query,
    ): array {
        $overdue = clone $query;
        $this->onlyOpenManifestations($overdue);

        $dueToday = clone $query;
        $this->onlyOpenManifestations($dueToday);

        $dueNextSevenDays = clone $query;
        $this->onlyOpenManifestations($dueNextSevenDays);

        return [
            'overdue' => $overdue
                ->whereDate(
                    'current_deadline_at',
                    '<',
                    today()->toDateString(),
                )
                ->count(),

            'due_today' => $dueToday
                ->whereDate(
                    'current_deadline_at',
                    today()->toDateString(),
                )
                ->count(),

            'due_next_7_days' => $dueNextSevenDays
                ->whereBetween(
                    'current_deadline_at',
                    [
                        today()->addDay()->startOfDay(),
                        today()->addDays(7)->endOfDay(),
                    ],
                )
                ->count(),

            'extended' => (clone $query)
                ->whereNotNull('extended_at')
                ->count(),

            'completed' => (clone $query)
                ->where(
                    'status',
                    ManifestationStatus::Completed->value,
                )
                ->count(),
        ];
    }

    private function onlyOpenManifestations(
        Builder $query,
    ): void {
        $query->whereNotIn('status', [
            ManifestationStatus::Completed->value,
            ManifestationStatus::Archived->value,
        ]);
    }
}
