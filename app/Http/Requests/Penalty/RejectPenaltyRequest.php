<?php

namespace App\Http\Requests\Penalty;

use App\Http\Requests\Contract\ContractRequest;
use Carbon\CarbonImmutable;

class RejectPenaltyRequest extends ContractRequest
{
    protected string $ability = 'decidePenalty';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'as_of' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function asOf(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->string('as_of')->toString());
    }
}
