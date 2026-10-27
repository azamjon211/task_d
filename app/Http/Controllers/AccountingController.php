<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contract\ContractAccountingRequest;
use App\Models\Contract;
use App\Services\Accounting\AccountingService;
use Illuminate\View\View;

class AccountingController extends Controller
{
    public function show(ContractAccountingRequest $request, Contract $contract, AccountingService $accounting): View
    {
        return view('accounting.show', [
            'contract' => $contract,
            'overview' => $accounting->overview($contract),
        ]);
    }
}
