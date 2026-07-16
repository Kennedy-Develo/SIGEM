<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Administrador que aprovou esta conta.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by');
    }

    /**
     * Usuários aprovados por esta conta.
     */
    public function approvedUsers(): HasMany
    {
        return $this->hasMany(self::class, 'approved_by');
    }

    /**
     * Manifestações atualmente atribuídas ao usuário.
     */
    public function currentManifestations(): HasMany
    {
        return $this->hasMany(
            Manifestation::class,
            'current_assignee_id',
        );
    }

    /**
     * Manifestações cadastradas pelo usuário.
     */
    public function createdManifestations(): HasMany
    {
        return $this->hasMany(
            Manifestation::class,
            'created_by_id',
        );
    }

    /**
     * Manifestações atualizadas pelo usuário.
     */
    public function updatedManifestations(): HasMany
    {
        return $this->hasMany(
            Manifestation::class,
            'updated_by_id',
        );
    }

    /**
     * Histórico de manifestações atribuídas ao usuário.
     */
    public function manifestationAssignments(): HasMany
    {
        return $this->hasMany(
            ManifestationAssignment::class,
            'assignee_id',
        );
    }

    /**
     * Atribuições realizadas pelo usuário.
     */
    public function assignmentsMade(): HasMany
    {
        return $this->hasMany(
            ManifestationAssignment::class,
            'assigned_by_id',
        );
    }

    /**
     * Atribuições encerradas pelo usuário.
     */
    public function assignmentsEnded(): HasMany
    {
        return $this->hasMany(
            ManifestationAssignment::class,
            'ended_by_id',
        );
    }

    /**
     * Verifica se a conta está ativa.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Verifica se o usuário é administrador.
     */
    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    /**
     * Define as conversões automáticas dos atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'approved_at' => 'datetime',
            'blocked_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }
}
