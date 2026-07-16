<?php

namespace App\Models;

use App\Enums\ManifestationSource;
use App\Enums\ManifestationStatus;
use App\Enums\ManifestationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manifestation extends Model
{
    use HasFactory;

    protected $fillable = [
        'nup',
        'source',
        'type',
        'status',
        'subject_id',
        'subsubject_id',
        'sector_id',
        'conclusion_responsible_area',
        'current_assignee_id',
        'created_by_id',
        'updated_by_id',
        'summary',
        'description',
        'opened_at',
        'original_deadline_at',
        'current_deadline_at',
        'extended_at',
        'extension_reason',
        'forwarded_to_external_agency_at',
        'external_agency',
        'answered_by_ombudsman_at',
        'completed_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'source' => ManifestationSource::class,
            'type' => ManifestationType::class,
            'status' => ManifestationStatus::class,
            'opened_at' => 'date',
            'original_deadline_at' => 'date',
            'current_deadline_at' => 'date',
            'extended_at' => 'datetime',
            'forwarded_to_external_agency_at' => 'datetime',
            'answered_by_ombudsman_at' => 'datetime',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function subsubject(): BelongsTo
    {
        return $this->belongsTo(Subsubject::class);
    }

    /**
     * Tag ou setor atualmente responsável pela manifestação.
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function currentAssignee(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'current_assignee_id',
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_id',
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by_id',
        );
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ManifestationAssignment::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            ManifestationStatus::Completed->value,
            ManifestationStatus::Archived->value,
        ]);
    }

    public function scopeAssignedTo(
        Builder $query,
        User|int $assignee,
    ): Builder {
        $assigneeId = $assignee instanceof User
            ? $assignee->id
            : $assignee;

        return $query->where(
            'current_assignee_id',
            $assigneeId,
        );
    }

    public function isExtended(): bool
    {
        return $this->extended_at !== null;
    }

    public function isForwardedToExternalAgency(): bool
    {
        return $this->forwarded_to_external_agency_at !== null;
    }

    public function isAnsweredByOmbudsman(): bool
    {
        return $this->answered_by_ombudsman_at !== null;
    }

    public function isOverdue(): bool
    {
        if (
            $this->current_deadline_at === null
            || $this->status->isFinal()
        ) {
            return false;
        }

        return $this->current_deadline_at->isBefore(today());
    }

    public function isDueToday(): bool
    {
        if (
            $this->current_deadline_at === null
            || $this->status->isFinal()
        ) {
            return false;
        }

        return $this->current_deadline_at->isToday();
    }
}
