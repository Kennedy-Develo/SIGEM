<?php

namespace App\Enums;

enum ManifestationType: string
{
    case AccessToInformation = 'access_to_information';
    case Communication = 'communication';
    case Denunciation = 'denunciation';
    case Praise = 'praise';
    case Complaint = 'complaint';
    case Simplify = 'simplify';
    case Request = 'request';
    case Suggestion = 'suggestion';

    public function label(): string
    {
        return match ($this) {
            self::AccessToInformation => 'Acesso à Informação',
            self::Communication => 'Comunicação',
            self::Denunciation => 'Denúncia',
            self::Praise => 'Elogio',
            self::Complaint => 'Reclamação',
            self::Simplify => 'Simplifique',
            self::Request => 'Solicitação',
            self::Suggestion => 'Sugestão',
        };
    }
}
