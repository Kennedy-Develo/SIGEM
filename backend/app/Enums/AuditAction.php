<?php

namespace App\Enums;

enum AuditAction: string
{
    case UserAccessUpdated = 'user.access_updated';
    case ManifestationUpdated = 'manifestation.updated';

    public function label(): string
    {
        return match ($this) {
            self::UserAccessUpdated => 'Acesso de usuário atualizado',
            self::ManifestationUpdated => 'Manifestação atualizada',
        };
    }
}
