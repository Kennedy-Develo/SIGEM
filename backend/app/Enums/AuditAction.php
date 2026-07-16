<?php

namespace App\Enums;

enum AuditAction: string
{
    case UserAccessUpdated = 'user.access_updated';

    public function label(): string
    {
        return match ($this) {
            self::UserAccessUpdated => 'Acesso de usuário atualizado',
        };
    }
}
