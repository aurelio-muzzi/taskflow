<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case REVIEW = 'review';
    case DONE = 'done';

    public function label(): string
    {
        return match ($this) {
            self::TODO => 'A Fazer',
            self::IN_PROGRESS => 'Em Progresso',
            self::REVIEW => 'Em Revisão',
            self::DONE => 'Concluída',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
