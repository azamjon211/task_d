<?php

namespace App\Services\Penalty;

use App\Enums\PenaltyDecisionStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Contract;
use App\Models\PenaltyDecision;
use App\Models\User;
use App\Repositories\ContractRepository;
use App\Repositories\DeliveryRepository;
use App\Repositories\PenaltyDecisionRepository;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PenaltyService
{
    public function __construct(
        private readonly PenaltyCalculator $calculator,
        private readonly ContractRepository $contracts,
        private readonly DeliveryRepository $deliveries,
        private readonly PenaltyDecisionRepository $decisions,
    ) {}

    public function calculate(Contract $contract, CarbonImmutable $asOf): ?PenaltyResult
    {
        if (! $contract->status->isInForce()) {
            return null;
        }

        return $this->calculator->calculate(
            $contract->dueDate(),
            $this->contracts->paidTotal($contract),
            $this->deliveries->deliveredPaidAmountsByDate($contract),
            $asOf,
        );
    }

    public function approvedAmount(Contract $contract): Money
    {
        $decision = $this->decisions->latestFor($contract);

        return $decision?->isApproved() ? $decision->effectiveAmount() : Money::zero();
    }

    public function approve(
        Contract $contract,
        User $head,
        CarbonImmutable $asOf,
        ?Money $approvedAmount,
        ?string $comment,
    ): PenaltyDecision {
        return DB::transaction(function () use ($contract, $head, $asOf, $approvedAmount, $comment): PenaltyDecision {
            $contract = $this->contracts->lockForUpdate($contract);
            $penalty = $this->calculateForDecision($contract, $asOf);

            if ($approvedAmount !== null && ! $approvedAmount->isPositive()) {
                throw new BusinessRuleException(
                    "Tasdiqlanadigan summa noldan katta bo'lishi kerak. Jarimani bekor qilish uchun «Rad etish» tugmasidan foydalaning.",
                    'approved_amount',
                );
            }

            if ($approvedAmount?->greaterThan($penalty->total)) {
                throw new BusinessRuleException(
                    "Tasdiqlanadigan summa ({$approvedAmount->format()}) hisoblangan jarimadan ({$penalty->total->format()}) ko'p bo'lishi mumkin emas.",
                    'approved_amount',
                );
            }

            return $this->decisions->create(
                $contract,
                $head,
                PenaltyDecisionStatus::Approved,
                $asOf,
                $penalty->total,
                $approvedAmount,
                $comment,
            );
        });
    }

    public function reject(Contract $contract, User $head, CarbonImmutable $asOf, string $reason): PenaltyDecision
    {
        return DB::transaction(function () use ($contract, $head, $asOf, $reason): PenaltyDecision {
            $contract = $this->contracts->lockForUpdate($contract);
            $penalty = $this->calculateForDecision($contract, $asOf);

            return $this->decisions->create(
                $contract,
                $head,
                PenaltyDecisionStatus::Rejected,
                $asOf,
                $penalty->total,
                null,
                $reason,
            );
        });
    }

    private function calculateForDecision(Contract $contract, CarbonImmutable $asOf): PenaltyResult
    {
        if ($asOf->isAfter(CarbonImmutable::today())) {
            throw new BusinessRuleException(
                "Kelajakdagi sana ({$asOf->format('d.m.Y')}) bo'yicha jarima haqida qaror qabul qilib bo'lmaydi: bu kunlar hali o'tmagan.",
                'as_of',
            );
        }

        $penalty = $this->calculate($contract, $asOf);

        if ($penalty === null) {
            throw new BusinessRuleException('Shartnoma faollashtirilmagan — jarima hisoblanmaydi.');
        }

        if (! $penalty->total->isPositive()) {
            throw new BusinessRuleException(
                "{$asOf->format('d.m.Y')} holatiga jarima yo'q (0,00) — qaror qabul qilinadigan summa yo'q.",
                'as_of',
            );
        }

        return $penalty;
    }
}
