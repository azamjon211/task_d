<?php

namespace App\Http\Requests\Accounting;

use App\Http\Requests\Concerns\NormalizesMoneyInput;
use App\Http\Requests\Contract\ContractRequest;
use App\Services\Accounting\PaymentData;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends ContractRequest
{
    use NormalizesMoneyInput;

    protected string $ability = 'manageAccounting';

    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['amount']);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'payment_stage_id' => [
                'required',
                'integer',
                Rule::exists('payment_stages', 'id')->where('contract_id', $this->contract()->getKey()),
            ],
            'budget_line_id' => ['required', 'integer', Rule::exists('budget_lines', 'id')],
            'amount' => ['required', 'regex:'.self::MONEY_REGEX],
            'paid_on' => ['required', 'date_format:Y-m-d'],
            'reference' => ['required', 'string', 'max:255'],
        ];
    }

    public function toData(): PaymentData
    {
        return new PaymentData(
            stageId: $this->integer('payment_stage_id'),
            budgetLineId: $this->integer('budget_line_id'),
            amount: Money::of($this->string('amount')->toString()),
            paidOn: CarbonImmutable::createFromFormat('!Y-m-d', $this->string('paid_on')->toString()),
            reference: trim($this->string('reference')),
        );
    }
}
