<?php

namespace App\Enums;

enum UserRole: string
{
    case Executor = 'executor';
    case Head = 'head';
    case Accountant = 'accountant';

    public function label(): string
    {
        return match ($this) {
            self::Executor => 'Ijrochi',
            self::Head => 'Boshliq',
            self::Accountant => 'Buxgalter',
        };
    }
}
