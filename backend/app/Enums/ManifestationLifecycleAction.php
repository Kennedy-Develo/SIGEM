<?php

namespace App\Enums;

enum ManifestationLifecycleAction: string
{
    case Start = 'start';
    case Extend = 'extend';
    case Forward = 'forward';
    case Answer = 'answer';
    case Complete = 'complete';
    case Archive = 'archive';
    case Reopen = 'reopen';

    public function label(): string
    {
        return match ($this) {
            self::Start => 'Iniciar atendimento',
            self::Extend => 'Prorrogar prazo',
            self::Forward => 'Encaminhar para outro órgão',
            self::Answer => 'Registrar resposta da Ouvidoria',
            self::Complete => 'Concluir manifestação',
            self::Archive => 'Arquivar manifestação',
            self::Reopen => 'Reabrir manifestação',
        };
    }

    public function requiresReason(): bool
    {
        return in_array($this, [
            self::Extend,
            self::Forward,
            self::Archive,
            self::Reopen,
        ], true);
    }

    public function isFinalizing(): bool
    {
        return in_array($this, [
            self::Complete,
            self::Archive,
        ], true);
    }
}
