<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class AuditLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the user who performed the action.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'actor_id',
        );
    }

    /**
     * Get the model affected by the action.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Prevent audit records from being changed or removed by the application.
     */
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException(
                'Registros de auditoria não podem ser alterados.',
            );
        });

        static::deleting(function (): never {
            throw new LogicException(
                'Registros de auditoria não podem ser removidos.',
            );
        });
    }
}
