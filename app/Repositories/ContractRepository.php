<?php

namespace App\Repositories;

use App\Enums\ContractStatus;
use App\Enums\StageCondition;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\PaymentStage;
use App\Models\User;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ContractRepository
{
    /**
     * @return LengthAwarePaginator<int, Contract>
     */
    public function paginateVisibleTo(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return Contract::query()
            ->visibleTo($user)
            ->with(['items', 'creator'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function lockForUpdate(Contract $contract): Contract
    {
        return Contract::query()->whereKey($contract->getKey())->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  array{number: string, supplier: string, start_date: string, duration_days: int}  $attributes
     */
    public function create(User $creator, array $attributes): Contract
    {
        $contract = new Contract($attributes);
        $contract->created_by = $creator->id;
        $contract->save();

        return $contract;
    }

    /**
     * @param  array{number: string, supplier: string, start_date: string, duration_days: int}  $attributes
     */
    public function update(Contract $contract, array $attributes): Contract
    {
        $contract->update($attributes);

        return $contract;
    }

    public function markActive(Contract $contract): void
    {
        $contract->forceFill([
            'status' => ContractStatus::Active,
            'activated_at' => now(),
        ])->save();
    }

    public function markCompleted(Contract $contract): void
    {
        $contract->forceFill([
            'status' => ContractStatus::Completed,
            'completed_at' => now(),
        ])->save();
    }

    /**
     * @param  array{name: string, paid_quantity: int, bonus_quantity: int, unit_price: Money}  $attributes
     */
    public function addItem(Contract $contract, array $attributes): ContractItem
    {
        $item = new ContractItem($attributes);
        $item->contract_id = $contract->getKey();
        $item->save();

        return $item;
    }

    public function deleteItem(ContractItem $item): void
    {
        $item->delete();
    }

    /**
     * @return Collection<int, PaymentStage>
     */
    public function stages(Contract $contract): Collection
    {
        return PaymentStage::query()->where('contract_id', $contract->getKey())->orderBy('sequence')->get();
    }

    /**
     * @param  array{name: string, percent: string, condition: StageCondition, due_date: ?string}  $attributes
     */
    public function addStage(Contract $contract, array $attributes): PaymentStage
    {
        $lastSequence = (int) PaymentStage::query()->where('contract_id', $contract->getKey())->max('sequence');

        $stage = new PaymentStage([...$attributes, 'sequence' => $lastSequence + 1]);
        $stage->contract_id = $contract->getKey();
        $stage->save();

        return $stage;
    }

    public function setStageDueDate(PaymentStage $stage, ?string $dueDate): void
    {
        $stage->update(['due_date' => $dueDate]);
    }

    public function setStageAmount(PaymentStage $stage, Money $amount): void
    {
        $stage->forceFill(['amount' => $amount])->save();
    }

    public function paidTotal(Contract $contract): Money
    {
        $total = ContractItem::query()
            ->where('contract_id', $contract->getKey())
            ->sum(DB::raw('paid_quantity * unit_price'));

        return Money::of((string) $total);
    }

    public function itemCount(Contract $contract): int
    {
        return ContractItem::query()->where('contract_id', $contract->getKey())->count();
    }
}
