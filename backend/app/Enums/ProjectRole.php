<?php

namespace App\Enums;

enum ProjectRole: string
{
    case OWNER = 'OWNER';
    case MANAGER = 'MANAGER';
    case MEMBER = 'MEMBER';
    case VIEWER = 'VIEWER';

    /**
     * Retorna o nome amigável do papel no projeto.
     */
    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Proprietário',
            self::MANAGER => 'Gerente',
            self::MEMBER => 'Membro',
            self::VIEWER => 'Visualizador',
        };
    }
}
