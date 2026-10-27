<?php

namespace App\Http\Requests\Penalty;

use App\Http\Requests\Concerns\NormalizesMoneyInput;
use App\Http\Requests\Contract\ContractRequest;
use App\Support\Money;
use Carbon\CarbonImmutable;

class ApprovePenaltyRequest extends ContractRequest
{
    use NormalizesMoneyInput;

    protected string $ability = 'decidePenalty';

    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['approved_amount']);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'as_of' => ['required', 'date_format:Y-m-d'],
            'approved_amount' => ['nullable', 'regex:'.self::MONEY_REGEX],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function asOf(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->string('as_of')->toString());
    }

    public function approvedAmount(): ?Money
    {
        return $this->filled('approved_amount') ? Money::of($this->string('approved_amount')->toString()) : null;
    }
}
