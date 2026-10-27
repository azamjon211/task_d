<?php

namespace App\Services\Delivery;

use App\Exceptions\BusinessRuleException;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Delivery;
use App\Models\User;
use App\Repositories\ContractRepository;
use App\Repositories\DeliveryRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    public function __construct(
        private readonly ContractRepository $contracts,
        private readonly DeliveryRepository $deliveries,
    ) {}

    /**
     * @return Collection<int, ContractItem> shartnoma qatori ID => qator
     */
    public function items(Contract $contract): Collection
    {
        return $this->deliveries->itemsWithDelivered($contract);
    }

    /**
     * @param  array<int, array{paid: int, bonus: int}>  $quantities  shartnoma qatori ID => miqdorlar
     */
    public function record(Contract $contract, User $executor, CarbonImmutable $deliveredOn, array $quantities): Delivery
    {
        return DB::transaction(function () use ($contract, $executor, $deliveredOn, $quantities): Delivery {
            $contract = $this->contracts->lockForUpdate($contract);

            if (! $contract->status->isInForce()) {
                throw new BusinessRuleException(
                    "Partiya qabul qilinmadi: shartnoma faol emas (holati: {$contract->status->label()}).",
                );
            }

            $this->assertWithinRemaining($this->items($contract), $quantities);

            $delivery = $this->deliveries->create($contract, $executor, $deliveredOn, $quantities);

            if ($this->isPaidFullyDelivered($contract)) {
                $this->contracts->markCompleted($contract);
            }

            return $delivery;
        });
    }

    /**
     * @param  Collection<int, ContractItem>  $items
     * @param  array<int, array{paid: int, bonus: int}>  $quantities
     */
    private function assertWithinRemaining(Collection $items, array $quantities): void
    {
        foreach ($quantities as $itemId => $quantity) {
            $item = $items->get($itemId) ?? throw new BusinessRuleException(
                "Partiyada shartnomaga tegishli bo'lmagan qator bor.",
            );

            if ($quantity['paid'] > $item->remainingPaid()) {
                throw new BusinessRuleException(
                    "«{$item->name}»: pullik qoldiq {$item->remainingPaid()} dona, siz {$quantity['paid']} dona kiritdingiz.",
                    "items.{$itemId}.paid",
                );
            }

            if ($quantity['bonus'] > $item->remainingBonus()) {
                throw new BusinessRuleException(
                    "«{$item->name}»: bonus qoldiq {$item->remainingBonus()} dona, siz {$quantity['bonus']} dona kiritdingiz.",
                    "items.{$itemId}.bonus",
                );
            }
        }
    }

    private function isPaidFullyDelivered(Contract $contract): bool
    {
        return $this->items($contract)->every(fn (ContractItem $item): bool => $item->remainingPaid() === 0);
    }
}
