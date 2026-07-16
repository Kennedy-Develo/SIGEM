<?php

namespace App\Enums;

enum ManifestationStatus: string
{
    case Registered = 'registered';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Cadastrada',
            self::InProgress => 'Em andamento',
            self::Completed => 'Concluída',
            self::Archived => 'Arquivada',
        };
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::Completed, self::Archived => true,
            self::Registered, self::InProgress => false,
        };
    }
}
