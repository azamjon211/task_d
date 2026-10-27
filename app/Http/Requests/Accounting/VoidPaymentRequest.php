<?php

namespace App\Http\Requests\Accounting;

use App\Http\Requests\Contract\ContractRequest;
use App\Models\Payment;

class VoidPaymentRequest extends ContractRequest
{
    protected string $ability = 'manageAccounting';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            $this->reasonField() => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [$this->reasonField() => 'bekor qilish sababi'];
    }

    public function reason(): string
    {
        return trim((string) $this->input($this->reasonField()));
    }

    private function reasonField(): string
    {
        /** @var Payment $payment */
        $payment = $this->route('payment');

        return "void_reason.{$payment->getKey()}";
    }
}
