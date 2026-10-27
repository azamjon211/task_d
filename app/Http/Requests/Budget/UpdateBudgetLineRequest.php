<?php

namespace App\Http\Requests\Budget;

use App\Http\Requests\Concerns\NormalizesMoneyInput;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetLineRequest extends FormRequest
{
    use NormalizesMoneyInput;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('budget_line'));
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['allocated_amount']);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'allocated_amount' => ['required', 'regex:'.self::MONEY_REGEX],
        ];
    }

    public function allocatedAmount(): Money
    {
        return Money::of($this->string('allocated_amount')->toString());
    }
}
