<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contract\ManageContractRequest;
use App\Http\Requests\Contract\StoreContractItemRequest;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Services\Contract\ContractService;
use Illuminate\Http\RedirectResponse;

class ContractItemController extends Controller
{
    public function __construct(private readonly ContractService $contracts) {}

    public function store(StoreContractItemRequest $request, Contract $contract): RedirectResponse
    {
        $this->contracts->addItem($contract, $request->attributesForItem());

        return redirect()->route('contracts.show', $contract)->with('status', "Tovar qatori qo'shildi.");
    }

    public function destroy(ManageContractRequest $request, Contract $contract, ContractItem $item): RedirectResponse
    {
        $this->contracts->removeItem($contract, $item);

        return redirect()->route('contracts.show', $contract)->with('status', "Tovar qatori o'chirildi.");
    }
}
