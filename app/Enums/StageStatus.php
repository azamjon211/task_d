<?php

namespace App\Enums;

enum StageStatus: string
{
    case Planned = 'planned';
    case ReadyToPay = 'ready_to_pay';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Rejalashtirilgan',
            self::ReadyToPay => 'To\'lashga tayyor',
            self::PartiallyPaid => 'Qisman to\'langan',
            self::Paid => 'To\'langan',
            self::Overdue => 'Muddati o\'tgan',
        };
    }
}
