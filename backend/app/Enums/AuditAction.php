<?php

namespace App\Enums;

enum AuditAction: string
{
    case UserAccessUpdated = 'user.access_updated';
    case ManifestationUpdated = 'manifestation.updated';
    case ManifestationLifecycleChanged = 'manifestation.lifecycle_changed';

    public function label(): string
    {
        return match ($this) {
            self::UserAccessUpdated => 'Acesso de usuário atualizado',
            self::ManifestationUpdated => 'Manifestação atualizada',
            self::ManifestationLifecycleChanged => 'Ciclo de vida da manifestação atualizado',
        };
    }
}
