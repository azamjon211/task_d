<?php

namespace Database\Factories;

use App\Models\ContractItem;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryItem>
 */
class DeliveryItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_id' => Delivery::factory(),
            'contract_item_id' => ContractItem::factory(),
            'paid_quantity' => 0,
            'bonus_quantity' => 0,
        ];
    }
}
