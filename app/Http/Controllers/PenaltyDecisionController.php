<?php

namespace App\Http\Controllers;

use App\Http\Requests\Penalty\ApprovePenaltyRequest;
use App\Http\Requests\Penalty\RejectPenaltyRequest;
use App\Models\Contract;
use App\Services\Penalty\PenaltyService;
use Illuminate\Http\RedirectResponse;

class PenaltyDecisionController extends Controller
{
    public function __construct(private readonly PenaltyService $penalties) {}

    public function approve(ApprovePenaltyRequest $request, Contract $contract): RedirectResponse
    {
        $decision = $this->penalties->approve(
            $contract,
            $request->user(),
            $request->asOf(),
            $request->approvedAmount(),
            $request->input('comment'),
        );

        return redirect()->route('contracts.show', [$contract, 'as_of' => $request->asOf()->toDateString()])
            ->with('status', "Jarima tasdiqlandi: {$decision->effectiveAmount()->format()}.");
    }

    public function reject(RejectPenaltyRequest $request, Contract $contract): RedirectResponse
    {
        $this->penalties->reject($contract, $request->user(), $request->asOf(), $request->string('reason')->trim()->toString());

        return redirect()->route('contracts.show', [$contract, 'as_of' => $request->asOf()->toDateString()])
            ->with('status', 'Jarima rad etildi.');
    }
}
