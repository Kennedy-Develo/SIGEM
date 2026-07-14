<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Manager = 'manager';
    case Operator = 'operator';
    case Reader = 'reader';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrador',
            self::Manager => 'Gestor',
            self::Operator => 'Operador',
            self::Reader => 'Leitor',
        };
    }
}
