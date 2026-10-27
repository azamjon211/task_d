<?php

namespace App\Models;

use Database\Factories\DeliveryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $contract_item_id
 * @property int $paid_quantity
 * @property int $bonus_quantity
 */
class DeliveryItem extends Model
{
    /** @use HasFactory<DeliveryItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'contract_item_id',
        'paid_quantity',
        'bonus_quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paid_quantity' => 'integer',
            'bonus_quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * @return BelongsTo<ContractItem, $this>
     */
    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }
}
