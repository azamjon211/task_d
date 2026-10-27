<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contract\UpdateStageDueDateRequest;
use App\Models\Contract;
use App\Models\PaymentStage;
use App\Services\Contract\ContractService;
use Illuminate\Http\RedirectResponse;

class PaymentStageController extends Controller
{
    public function update(UpdateStageDueDateRequest $request, Contract $contract, PaymentStage $stage, ContractService $contracts): RedirectResponse
    {
        $contracts->setStageDueDate($contract, $stage, $request->dueDate());

        return redirect()->route('contracts.show', $contract)->with('status', "«{$stage->name}» bosqichining muddat sanasi saqlandi.");
    }
}
