<?php

namespace App\Http\Controllers;

use App\Http\Requests\Accounting\StorePaymentRequest;
use App\Http\Requests\Accounting\VoidPaymentRequest;
use App\Models\Contract;
use App\Models\Payment;
use App\Services\Accounting\PaymentService;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function store(StorePaymentRequest $request, Contract $contract): RedirectResponse
    {
        $payment = $this->payments->register($contract, $request->user(), $request->toData());

        return redirect()->route('contracts.accounting', $contract)
            ->with('status', "To'lov reestrga yozildi: {$payment->amount->format()}.");
    }

    public function void(VoidPaymentRequest $request, Contract $contract, Payment $payment): RedirectResponse
    {
        $this->payments->void($contract, $payment, $request->user(), $request->reason());

        return redirect()->route('contracts.accounting', $contract)
            ->with('status', "To'lov bekor qilindi: {$payment->amount->format()}. Bosqich va smeta qoldig'i qayta hisoblandi.");
    }
}
