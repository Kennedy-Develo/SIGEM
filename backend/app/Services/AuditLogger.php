<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * Fields that must never be stored in audit records.
     *
     * @var list<string>
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'remember_token',
        'token',
    ];

    public function __construct(
        private readonly Request $request,
    ) {}

    /**
     * Create an immutable audit record.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        AuditAction $action,
        Model $subject,
        ?User $actor = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
    ): AuditLog {
        $actorMetadata = array_filter([
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
        ], static fn (mixed $value): bool => $value !== null);

        return AuditLog::query()->create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'metadata' => [
                ...$actorMetadata,
                ...$this->sanitize($metadata),
            ],
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }

    /**
     * Remove sensitive information before persistence.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitize(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            if (in_array($key, self::SENSITIVE_FIELDS, true)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            $sanitized[$key] = is_array($value)
                ? $this->sanitize($value)
                : $value;
        }

        return $sanitized;
    }
}
