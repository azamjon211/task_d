<?php

namespace App\Services\Contract;

use App\Enums\ContractStatus;
use App\Enums\StageCondition;
use App\Exceptions\BusinessRuleException;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\PaymentStage;
use App\Models\User;
use App\Repositories\ContractRepository;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class ContractService
{
    /**
     * @var list<array{name: string, percent: string, condition: StageCondition}>
     */
    private const array DEFAULT_STAGES = [
        ['name' => 'Avans', 'percent' => '30.00', 'condition' => StageCondition::OnActivation],
        ['name' => 'Yetkazib berish', 'percent' => '70.00', 'condition' => StageCondition::OnFullDelivery],
    ];

    public function __construct(private readonly ContractRepository $contracts) {}

    /**
     * @param  array{number: string, supplier: string, start_date: string, duration_days: int}  $attributes
     */
    public function create(User $executor, array $attributes): Contract
    {
        return DB::transaction(function () use ($executor, $attributes): Contract {
            $contract = $this->contracts->create($executor, $attributes);

            foreach (self::DEFAULT_STAGES as $stage) {
                $this->contracts->addStage($contract, [...$stage, 'due_date' => null]);
            }

            return $contract;
        });
    }

    /**
     * @param  array{number: string, supplier: string, start_date: string, duration_days: int}  $attributes
     */
    public function update(Contract $contract, array $attributes): Contract
    {
        return $this->whileDraft($contract, fn (Contract $locked): Contract => $this->contracts->update($locked, $attributes));
    }

    /**
     * @param  array{name: string, paid_quantity: int, bonus_quantity: int, unit_price: Money}  $attributes
     */
    public function addItem(Contract $contract, array $attributes): ContractItem
    {
        return $this->whileDraft($contract, function (Contract $locked) use ($attributes): ContractItem {
            $newTotal = $this->contracts->paidTotal($locked)
                ->plus($attributes['unit_price']->multipliedBy($attributes['paid_quantity']));

            if ($newTotal->greaterThan(Money::of(Money::MAX))) {
                throw new BusinessRuleException(
                    "Shartnomaning pullik summasi juda katta: {$newTotal->format()} (eng ko'pi ".Money::of(Money::MAX)->format().').',
                    'unit_price',
                );
            }

            return $this->contracts->addItem($locked, $attributes);
        });
    }

    public function removeItem(Contract $contract, ContractItem $item): void
    {
        $this->whileDraft($contract, fn () => $this->contracts->deleteItem($item));
    }

    public function setStageDueDate(Contract $contract, PaymentStage $stage, ?string $dueDate): void
    {
        $this->whileDraft($contract, fn () => $this->contracts->setStageDueDate($stage, $dueDate));
    }

    public function activate(Contract $contract): Contract
    {
        return $this->whileDraft($contract, function (Contract $locked): Contract {
            if ($this->contracts->itemCount($locked) === 0) {
                throw new BusinessRuleException('Faollashtirish uchun kamida 1 ta tovar qatori kerak.');
            }

            $paidTotal = $this->contracts->paidTotal($locked);

            if (! $paidTotal->isPositive()) {
                throw new BusinessRuleException("Faollashtirish uchun shartnomaning pullik summasi noldan katta bo'lishi kerak.");
            }

            $stages = $this->contracts->stages($locked);
            $allocated = Money::zero();

            foreach ($stages as $index => $stage) {
                $amount = $index === $stages->count() - 1
                    ? $paidTotal->minus($allocated)
                    : $paidTotal->percent($stage->percent);

                $this->contracts->setStageAmount($stage, $amount);
                $allocated = $allocated->plus($amount);
            }

            $this->contracts->markActive($locked);

            return $locked;
        });
    }

    /**
     * @template TResult
     *
     * @param  callable(Contract): TResult  $callback
     * @return TResult
     */
    private function whileDraft(Contract $contract, callable $callback): mixed
    {
        return DB::transaction(function () use ($contract, $callback): mixed {
            $locked = $this->contracts->lockForUpdate($contract);

            if ($locked->status !== ContractStatus::Draft) {
                throw new BusinessRuleException(
                    "Shartnoma {$locked->status->label()} holatida — faollashtirilgandan keyin shartnoma, qatorlar va bosqichlar o'zgarmaydi.",
                );
            }

            return $callback($locked);
        });
    }
}
