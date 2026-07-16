<?php

namespace App\Http\Controllers\Manifestation;

use App\Enums\ManifestationSource;
use App\Enums\ManifestationStatus;
use App\Enums\ManifestationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ManifestationCatalogController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $sources = collect(ManifestationSource::cases())
            ->map(fn (ManifestationSource $source): array => [
                'value' => $source->value,
                'label' => $source->label(),
            ])
            ->values();

        $types = collect(ManifestationType::cases())
            ->map(fn (ManifestationType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values();

        $statuses = collect(ManifestationStatus::cases())
            ->map(fn (ManifestationStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'is_final' => $status->isFinal(),
            ])
            ->values();

        $subjects = Subject::query()
            ->active()
            ->whereHas(
                'subsubjects',
                fn ($query) => $query->active(),
            )
            ->with([
                'subsubjects' => fn ($query) => $query
                    ->active()
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Subject $subject): array => [
                'id' => $subject->id,
                'name' => $subject->name,
                'subsubjects' => $subject->subsubjects
                    ->map(fn ($subsubject): array => [
                        'id' => $subsubject->id,
                        'name' => $subsubject->name,
                    ])
                    ->values(),
            ])
            ->values();

        $sectors = Sector::query()
            ->active()
            ->orderBy('acronym')
            ->get()
            ->map(fn (Sector $sector): array => [
                'id' => $sector->id,
                'acronym' => $sector->acronym,
                'name' => $sector->name,
                'label' => "{$sector->acronym} — {$sector->name}",
            ])
            ->values();

        $assignees = User::query()
            ->where('status', UserStatus::Active->value)
            ->whereIn('role', [
                UserRole::Administrator->value,
                UserRole::Manager->value,
                UserRole::Operator->value,
            ])
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
            ])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
            ])
            ->values();

        return response()->json([
            'sources' => $sources,
            'types' => $types,
            'statuses' => $statuses,
            'subjects' => $subjects,
            'sectors' => $sectors,
            'assignees' => $assignees,
        ]);
    }
}
