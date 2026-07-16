<?php

namespace App\Enums;

enum ManifestationSource: string
{
    case FalaBr = 'fala_br';
    case Sei = 'sei';

    public function label(): string
    {
        return match ($this) {
            self::FalaBr => 'FALA.BR',
            self::Sei => 'SEI',
        };
    }
}
