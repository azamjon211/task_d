<?php

namespace App\Repositories;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Delivery;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DeliveryRepository
{
    /**
     * @return Collection<int, ContractItem>
     */
    public function itemsWithDelivered(Contract $contract): Collection
    {
        return ContractItem::query()
            ->where('contract_id', $contract->getKey())
            ->withDelivered()
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    /**
     * @return array<string, Money> Y-m-d => summa
     */
    public function deliveredPaidAmountsByDate(Contract $contract): array
    {
        $rows = DB::table('delivery_items')
            ->join('deliveries', 'deliveries.id', '=', 'delivery_items.delivery_id')
            ->join('contract_items', 'contract_items.id', '=', 'delivery_items.contract_item_id')
            ->where('deliveries.contract_id', $contract->getKey())
            ->groupBy('deliveries.delivered_on')
            ->select('deliveries.delivered_on')
            ->selectRaw('SUM(delivery_items.paid_quantity * contract_items.unit_price) as amount')
            ->get();

        $amounts = [];

        foreach ($rows as $row) {
            $date = CarbonImmutable::parse($row->delivered_on)->toDateString();
            $amounts[$date] = ($amounts[$date] ?? Money::zero())->plus(Money::of((string) $row->amount));
        }

        return $amounts;
    }

    /**
     * @param  array<int, array{paid: int, bonus: int}>  $quantities  shartnoma qatori ID => miqdorlar
     */
    public function create(Contract $contract, User $creator, CarbonImmutable $deliveredOn, array $quantities): Delivery
    {
        $delivery = new Delivery(['delivered_on' => $deliveredOn]);
        $delivery->contract_id = $contract->getKey();
        $delivery->created_by = $creator->id;
        $delivery->save();

        foreach ($quantities as $contractItemId => $quantity) {
            if ($quantity['paid'] === 0 && $quantity['bonus'] === 0) {
                continue;
            }

            $delivery->items()->create([
                'contract_item_id' => $contractItemId,
                'paid_quantity' => $quantity['paid'],
                'bonus_quantity' => $quantity['bonus'],
            ]);
        }

        return $delivery;
    }
}
