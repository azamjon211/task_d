<?php

namespace App\Http\Requests\Contract;

use App\Http\Requests\Concerns\NormalizesMoneyInput;
use App\Support\Money;
use Illuminate\Validation\Validator;

class StoreContractItemRequest extends ContractRequest
{
    use NormalizesMoneyInput;

    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['unit_price']);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'paid_quantity' => ['required', 'integer', 'min:0', 'max:100000000'],
            'bonus_quantity' => ['required', 'integer', 'min:0', 'max:100000000'],
            'unit_price' => ['required', 'regex:'.self::MONEY_REGEX],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['paid_quantity', 'bonus_quantity'])) {
                    return;
                }

                if ($this->integer('paid_quantity') + $this->integer('bonus_quantity') === 0) {
                    $validator->errors()->add('paid_quantity', "Pullik yoki bonus sonidan kamida bittasi noldan katta bo'lishi kerak.");
                }
            },
        ];
    }

    /**
     * @return array{name: string, paid_quantity: int, bonus_quantity: int, unit_price: Money}
     */
    public function attributesForItem(): array
    {
        return [
            'name' => trim($this->string('name')),
            'paid_quantity' => $this->integer('paid_quantity'),
            'bonus_quantity' => $this->integer('bonus_quantity'),
            'unit_price' => Money::of($this->string('unit_price')->toString()),
        ];
    }
}
