<?php

namespace App\Enums;

enum StageCondition: string
{
    case OnActivation = 'on_activation';
    case OnFullDelivery = 'on_full_delivery';

    public function label(): string
    {
        return match ($this) {
            self::OnActivation => 'Shartnoma faollashgandan keyin',
            self::OnFullDelivery => 'Pullik tovar to\'liq kelgandan keyin',
        };
    }
}
