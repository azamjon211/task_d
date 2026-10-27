<?php

namespace App\Http\Controllers;

use App\Enums\ContractStatus;
use App\Http\Requests\Contract\ManageContractRequest;
use App\Http\Requests\Contract\ShowContractRequest;
use App\Http\Requests\Contract\StoreContractRequest;
use App\Http\Requests\Contract\UpdateContractRequest;
use App\Models\Contract;
use App\Repositories\ContractRepository;
use App\Repositories\PenaltyDecisionRepository;
use App\Services\Contract\ContractService;
use App\Services\Delivery\DeliveryService;
use App\Services\Penalty\PenaltyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function __construct(private readonly ContractService $contracts) {}

    public function index(Request $request, ContractRepository $repository): View
    {
        return view('contracts.index', [
            'contracts' => $repository->paginateVisibleTo($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Contract::class);

        return view('contracts.form', ['contract' => new Contract]);
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        $contract = $this->contracts->create($request->user(), $request->attributesForContract());

        return redirect()->route('contracts.show', $contract)
            ->with('status', "Shartnoma yaratildi. Endi tovar qatorlarini qo'shing va faollashtiring.");
    }

    public function show(
        ShowContractRequest $request,
        Contract $contract,
        ContractRepository $repository,
        DeliveryService $deliveries,
        PenaltyService $penalties,
        PenaltyDecisionRepository $decisions,
    ): View {
        $asOf = $request->asOf();
        $contract->load(['creator', 'deliveries.items.contractItem', 'deliveries.creator']);

        return view('contracts.show', [
            'contract' => $contract,
            'paidTotal' => $repository->paidTotal($contract),
            'items' => $deliveries->items($contract),
            'stages' => $repository->stages($contract),
            'asOf' => $asOf,
            'penalty' => $penalties->calculate($contract, $asOf),
            'decisions' => $decisions->historyFor($contract),
        ]);
    }

    public function edit(ManageContractRequest $request, Contract $contract): View|RedirectResponse
    {
        if ($contract->status !== ContractStatus::Draft) {
            return redirect()->route('contracts.show', $contract)
                ->withErrors(['general' => "Faollashtirilgan shartnoma o'zgartirilmaydi."]);
        }

        return view('contracts.form', ['contract' => $contract]);
    }

    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        $this->contracts->update($contract, $request->attributesForContract());

        return redirect()->route('contracts.show', $contract)->with('status', 'Shartnoma saqlandi.');
    }

    public function activate(ManageContractRequest $request, Contract $contract): RedirectResponse
    {
        $this->contracts->activate($contract);

        return redirect()->route('contracts.show', $contract)->with('status', 'Shartnoma faollashtirildi.');
    }
}
