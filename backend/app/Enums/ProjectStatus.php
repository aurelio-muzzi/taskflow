<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case PLANNING = 'PLANNING';
    case ACTIVE = 'ACTIVE';
    case ON_HOLD = 'ON_HOLD';
    case COMPLETED = 'COMPLETED';
    case ARCHIVED = 'ARCHIVED';

    /**
     * Retorna o nome amigável do status do projeto.
     */
    public function label(): string
    {
        return match ($this) {
            self::PLANNING => 'Planejamento',
            self::ACTIVE => 'Ativo',
            self::ON_HOLD => 'Em Espera',
            self::COMPLETED => 'Concluído',
            self::ARCHIVED => 'Arquivado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
