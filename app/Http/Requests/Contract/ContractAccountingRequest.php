<?php

namespace App\Http\Requests\Contract;

class ContractAccountingRequest extends ContractRequest
{
    protected string $ability = 'manageAccounting';
}
