@extends('layouts.app')

@section('title', "Buxgalteriya — {$contract->number}")

@section('content')
    <h1>
        Buxgalteriya — shartnoma <a href="{{ route('contracts.show', $contract) }}">{{ $contract->number }}</a>
        <span class="badge badge-{{ $contract->status->value }}">{{ $contract->status->label() }}</span>
    </h1>

    <section>
        <div class="grid">
            <div class="stat"><div class="label">Yetkazuvchi</div><div class="value" style="font-size: 15px;">{{ $contract->supplier }}</div></div>
            <div class="stat"><div class="label">Pullik summa</div><div class="value">{{ $overview->paidTotal->format() }}</div></div>
            <div class="stat"><div class="label">Tasdiqlangan jarima</div><div class="value">{{ $overview->approvedPenalty->format() }}</div></div>
            @if ($overview->penaltyToRecover->isPositive())
                <div class="stat"><div class="label">Yetkazuvchidan undiriladigan jarima</div><div class="value">{{ $overview->penaltyToRecover->format() }}</div></div>
            @endif
        </div>
        <p class="muted" style="margin-bottom: 0;">
            Faqat boshliq tasdiqlagan jarima hisobga olinadi va yakuniy (oxirgi) bosqich to'lovidan ushlab qolinadi.
            Bosqichdan ushlab qolib bo'lmagan qismi yetkazuvchidan undiriladi.
        </p>
    </section>

    <section>
        <h2>To'lov bosqichlari</h2>
        <table>
            <thead>
            <tr>
                <th>Bosqich</th>
                <th class="num">Foiz</th>
                <th class="num">Summa</th>
                <th class="num">Jarima ushlab qolindi</th>
                <th class="num">To'lanadi</th>
                <th class="num">To'langan</th>
                <th class="num">Qoldiq</th>
                <th>Qachon to'lanadi</th>
                <th>Muddat</th>
                <th>Holat</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($overview->summaries as $summary)
                <tr>
                    <td>{{ $summary->stage->name }}</td>
                    <td class="num">{{ $summary->stage->percent }}%</td>
                    <td class="num">{{ $summary->amount->format() }}</td>
                    <td class="num">{{ $summary->penaltyDeduction->format() }}</td>
                    <td class="num">{{ $summary->payable()->format() }}</td>
                    <td class="num">{{ $summary->paid->format() }}</td>
                    <td class="num"><strong>{{ $summary->remaining()->format() }}</strong></td>
                    <td>{{ $summary->stage->condition->label() }}</td>
                    <td>{{ $summary->stage->due_date?->format('d.m.Y') ?? '—' }}</td>
                    <td>
                        <span class="badge badge-{{ $summary->status()->value }}">{{ $summary->status()->label() }}</span>
                        @if ($summary->overpaid()->isPositive())
                            <div class="error">Ortiqcha to'langan: {{ $summary->overpaid()->format() }}</div>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <section>
        <h2>To'lov kiritish</h2>
        <form method="POST" action="{{ route('contracts.payments.store', $contract) }}">
            @csrf
            <div class="row">
                <div class="field">
                    <label for="payment_stage_id">Bosqich</label>
                    <select id="payment_stage_id" name="payment_stage_id" required>
                        @foreach ($overview->summaries as $summary)
                            <option value="{{ $summary->stage->id }}" @selected((int) old('payment_stage_id') === $summary->stage->id)>
                                {{ $summary->stage->name }} — qoldiq {{ $summary->remaining()->format() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="amount">Summa</label>
                    <input id="amount" name="amount" value="{{ old('amount') }}" required inputmode="decimal" style="width: 130px;">
                </div>
                <div class="field">
                    <label for="paid_on">To'lov sanasi</label>
                    <input id="paid_on" type="date" name="paid_on" value="{{ old('paid_on', now()->toDateString()) }}" required>
                </div>
                <div class="field">
                    <label for="budget_line_id">Smeta qatori (oy)</label>
                    <select id="budget_line_id" name="budget_line_id" required>
                        <option value="">— tanlang —</option>
                        @foreach ($overview->budgetLines as $line)
                            <option value="{{ $line->id }}" @selected((int) old('budget_line_id') === $line->id)>
                                {{ $line->label() }} — qoldiq {{ $line->remaining()->format() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="reference">To'lov raqami / izoh</label>
                    <input id="reference" name="reference" value="{{ old('reference') }}" required maxlength="255">
                </div>
                <button type="submit">Kiritish</button>
            </div>
            @foreach (['payment_stage_id', 'amount', 'paid_on', 'budget_line_id', 'reference'] as $field)
                @error($field) <div class="error">{{ $message }}</div> @enderror
            @endforeach
        </form>
    </section>

    <section>
        <h2>To'lov reestri — faol to'lovlar</h2>
        @if ($overview->activePayments->isEmpty())
            <p class="muted">Faol to'lovlar yo'q.</p>
        @else
            <table>
                <thead>
                <tr><th>Sana</th><th class="num">Summa</th><th>Bosqich</th><th>Smeta oyi</th><th>Raqam / izoh</th><th>Kiritgan</th><th>Bekor qilish</th></tr>
                </thead>
                <tbody>
                @foreach ($overview->activePayments as $payment)
                    <tr>
                        <td>{{ $payment->paid_on->format('d.m.Y') }}</td>
                        <td class="num">{{ $payment->amount->format() }}</td>
                        <td>{{ $payment->stage->name }}</td>
                        <td>{{ $payment->budgetLine->label() }}</td>
                        <td>{{ $payment->reference }}</td>
                        <td>{{ $payment->creator->name }}<div class="muted">{{ $payment->created_at->format('d.m.Y H:i') }}</div></td>
                        <td>
                            <form method="POST" action="{{ route('contracts.payments.void', [$contract, $payment]) }}" class="row">
                                @csrf
                                <input name="void_reason[{{ $payment->id }}]" value="{{ old("void_reason.{$payment->id}") }}" placeholder="Sabab" required>
                                <button type="submit" class="btn-danger">Bekor qilish</button>
                            </form>
                            @error("void_reason.{$payment->id}") <div class="error">{{ $message }}</div> @enderror
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section>
        <h2>To'lov reestri — bekor qilingan to'lovlar</h2>
        @if ($overview->voidedPayments->isEmpty())
            <p class="muted">Bekor qilingan to'lovlar yo'q.</p>
        @else
            <table>
                <thead>
                <tr><th>Sana</th><th class="num">Summa</th><th>Bosqich</th><th>Smeta oyi</th><th>Raqam / izoh</th><th>Kiritgan</th><th>Bekor qilgan</th><th>Sabab</th></tr>
                </thead>
                <tbody>
                @foreach ($overview->voidedPayments as $payment)
                    <tr>
                        <td>{{ $payment->paid_on->format('d.m.Y') }}</td>
                        <td class="num"><s>{{ $payment->amount->format() }}</s></td>
                        <td>{{ $payment->stage->name }}</td>
                        <td>{{ $payment->budgetLine->label() }}</td>
                        <td>{{ $payment->reference }}</td>
                        <td>{{ $payment->creator->name }}</td>
                        <td>{{ $payment->voider->name }}<div class="muted">{{ $payment->voided_at->format('d.m.Y H:i') }}</div></td>
                        <td>{{ $payment->void_reason }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
