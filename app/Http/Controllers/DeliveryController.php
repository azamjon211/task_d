<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contract\ManageContractRequest;
use App\Http\Requests\Delivery\StoreDeliveryRequest;
use App\Models\Contract;
use App\Services\Delivery\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(private readonly DeliveryService $deliveries) {}

    public function create(ManageContractRequest $request, Contract $contract): View
    {
        return view('deliveries.create', [
            'contract' => $contract,
            'items' => $this->deliveries->items($contract),
        ]);
    }

    public function store(StoreDeliveryRequest $request, Contract $contract): RedirectResponse
    {
        $this->deliveries->record($contract, $request->user(), $request->deliveredOn(), $request->quantities());

        return redirect()->route('contracts.show', $contract)->with('status', 'Partiya qabul qilindi.');
    }
}
