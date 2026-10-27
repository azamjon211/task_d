<?php

namespace App\Http\Requests\Delivery;

use App\Http\Requests\Contract\ContractRequest;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

class StoreDeliveryRequest extends ContractRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'delivered_on' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$this->contract()->start_date->toDateString(),
                'before_or_equal:today',
            ],
            'items' => ['required', 'array'],
            'items.*' => ['array'],
            'items.*.paid' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'items.*.bonus' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $total = array_sum(array_map(fn (array $quantity): int => $quantity['paid'] + $quantity['bonus'], $this->quantities()));

                if ($total === 0) {
                    $validator->errors()->add('items', "Partiyada kamida bitta qator bo'yicha miqdor kiriting.");
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'delivered_on.after_or_equal' => "Partiya sanasi shartnoma boshlanish sanasidan (:date) oldin bo'lishi mumkin emas.",
            'delivered_on.before_or_equal' => "Partiya sanasi kelajakda bo'lishi mumkin emas.",
        ];
    }

    public function deliveredOn(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->string('delivered_on')->toString());
    }

    /**
     * @return array<int, array{paid: int, bonus: int}> shartnoma qatori ID => miqdorlar
     */
    public function quantities(): array
    {
        $quantities = [];

        foreach ((array) $this->input('items', []) as $itemId => $values) {
            $quantities[(int) $itemId] = [
                'paid' => (int) ($values['paid'] ?? 0),
                'bonus' => (int) ($values['bonus'] ?? 0),
            ];
        }

        return $quantities;
    }
}
