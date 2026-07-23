<?php

namespace App\Enums;

enum AuditAction: string
{
    case UserAccessUpdated = 'user.access_updated';
    case ManifestationUpdated = 'manifestation.updated';
    case ManifestationLifecycleChanged = 'manifestation.lifecycle_changed';
    case ManifestationTrashed = 'manifestation.trashed';
    case ManifestationRestored = 'manifestation.restored';

    public function label(): string
    {
        return match ($this) {
            self::UserAccessUpdated => 'Acesso de usuário atualizado',
            self::ManifestationUpdated => 'Manifestação atualizada',
            self::ManifestationLifecycleChanged => 'Ciclo de vida da manifestação atualizado',
            self::ManifestationTrashed => 'Manifestação enviada para a lixeira',
            self::ManifestationRestored => 'Manifestação restaurada da lixeira',
        };
    }
}
