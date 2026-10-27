<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Database\Factories\ContractItemFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * @property int $paid_quantity
 * @property int $bonus_quantity
 * @property Money $unit_price
 */
class ContractItem extends Model
{
    /** @use HasFactory<ContractItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'paid_quantity',
        'bonus_quantity',
        'unit_price',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'paid_quantity' => 'integer',
            'bonus_quantity' => 'integer',
            'unit_price' => MoneyCast::class,
        ];
    }

    public function paidTotal(): Money
    {
        return $this->unit_price->multipliedBy($this->paid_quantity);
    }

    /**
     * @param  Builder<ContractItem>  $query
     */
    #[Scope]
    protected function withDelivered(Builder $query): void
    {
        $query->withSum('deliveryItems as delivered_paid', 'paid_quantity')
            ->withSum('deliveryItems as delivered_bonus', 'bonus_quantity');
    }

    public function deliveredPaid(): int
    {
        return $this->loadedSum('delivered_paid');
    }

    public function deliveredBonus(): int
    {
        return $this->loadedSum('delivered_bonus');
    }

    public function remainingPaid(): int
    {
        return $this->paid_quantity - $this->deliveredPaid();
    }

    public function remainingBonus(): int
    {
        return $this->bonus_quantity - $this->deliveredBonus();
    }

    private function loadedSum(string $attribute): int
    {
        if (! array_key_exists($attribute, $this->attributes)) {
            throw new LogicException("Kelgan miqdor yuklanmagan: so'rovga withDelivered() qo'shing.");
        }

        return (int) $this->attributes[$attribute];
    }

    /**
     * @return HasMany<DeliveryItem, $this>
     */
    public function deliveryItems(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
