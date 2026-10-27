<?php

namespace App\Http\Requests\Contract;

use Carbon\CarbonImmutable;

class ShowContractRequest extends ContractRequest
{
    protected string $ability = 'view';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'as_of' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('contracts.show', $this->contract());
    }

    public function asOf(): CarbonImmutable
    {
        return $this->filled('as_of')
            ? CarbonImmutable::createFromFormat('!Y-m-d', $this->string('as_of')->toString())
            : CarbonImmutable::today();
    }
}
