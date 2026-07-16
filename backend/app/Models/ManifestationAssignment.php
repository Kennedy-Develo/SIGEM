<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManifestationAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'manifestation_id',
        'assignee_id',
        'assigned_by_id',
        'ended_by_id',
        'assigned_at',
        'ended_at',
        'assignment_reason',
        'ending_reason',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function manifestation(): BelongsTo
    {
        return $this->belongsTo(Manifestation::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assignee_id',
        );
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_by_id',
        );
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'ended_by_id',
        );
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public function scopeForAssignee(
        Builder $query,
        User|int $assignee,
    ): Builder {
        $assigneeId = $assignee instanceof User
            ? $assignee->id
            : $assignee;

        return $query->where('assignee_id', $assigneeId);
    }

    public function isCurrent(): bool
    {
        return $this->ended_at === null;
    }
}
