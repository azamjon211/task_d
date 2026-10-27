@extends('layouts.app')

@php($isDraft = $contract->status === \App\Enums\ContractStatus::Draft)

@section('title', "Shartnoma {$contract->number}")

@section('content')
    <h1>
        Shartnoma {{ $contract->number }}
        <span class="badge badge-{{ $contract->status->value }}">{{ $contract->status->label() }}</span>
    </h1>

    <div class="actions">
        @can('manage', $contract)
            @if ($isDraft)
                <a class="btn btn-light" href="{{ route('contracts.edit', $contract) }}">Tahrirlash</a>
                <form method="POST" action="{{ route('contracts.activate', $contract) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-success">Faollashtirish</button>
                </form>
            @else
                <a class="btn" href="{{ route('contracts.deliveries.create', $contract) }}">Partiya kiritish</a>
            @endif
        @endcan
        @can('manageAccounting', $contract)
            <a class="btn" href="{{ route('contracts.accounting', $contract) }}">Buxgalteriya</a>
        @endcan
    </div>

    <section>
        <div class="grid">
            <div class="stat"><div class="label">Yetkazuvchi</div><div class="value" style="font-size: 15px;">{{ $contract->supplier }}</div></div>
            <div class="stat"><div class="label">Boshlanish sanasi</div><div class="value">{{ $contract->start_date->format('d.m.Y') }}</div></div>
            <div class="stat"><div class="label">Muddat</div><div class="value">{{ $contract->duration_days }} kun</div></div>
            <div class="stat"><div class="label">Muddat sanasi</div><div class="value">{{ $contract->dueDate()->format('d.m.Y') }}</div></div>
            <div class="stat"><div class="label">Pullik summa</div><div class="value">{{ $paidTotal->format() }}</div></div>
            <div class="stat"><div class="label">Ijrochi</div><div class="value" style="font-size: 15px;">{{ $contract->creator->name }}</div></div>
        </div>
    </section>

    <section>
        <h2>Tovar qatorlari va qoldiq</h2>
        @if ($items->isEmpty())
            <p class="muted">Hali tovar qatori yo'q. Faollashtirish uchun kamida 1 ta qator kerak.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Nomi</th>
                    <th class="num">Narxi</th>
                    <th class="num">Pullik soni</th>
                    <th class="num">Pullik summa</th>
                    <th class="num">Pullik keldi</th>
                    <th class="num">Pullik qoldiq</th>
                    <th class="num">Bonus soni</th>
                    <th class="num">Bonus keldi</th>
                    <th class="num">Bonus qoldiq</th>
                    @if ($isDraft) @can('manage', $contract) <th></th> @endcan @endif
                </tr>
                </thead>
                <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td class="num">{{ $item->unit_price->format() }}</td>
                        <td class="num">{{ $item->paid_quantity }}</td>
                        <td class="num">{{ $item->paidTotal()->format() }}</td>
                        <td class="num">{{ $item->deliveredPaid() }}</td>
                        <td class="num"><strong>{{ $item->remainingPaid() }}</strong></td>
                        <td class="num">{{ $item->bonus_quantity }}</td>
                        <td class="num">{{ $item->deliveredBonus() }}</td>
                        <td class="num"><strong>{{ $item->remainingBonus() }}</strong></td>
                        @if ($isDraft)
                            @can('manage', $contract)
                                <td>
                                    <form method="POST" action="{{ route('contracts.items.destroy', [$contract, $item]) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger">O'chirish</button>
                                    </form>
                                </td>
                            @endcan
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        @if ($isDraft)
            @can('manage', $contract)
                <h2 style="margin-top: 16px;">Qator qo'shish</h2>
                <form method="POST" action="{{ route('contracts.items.store', $contract) }}">
                    @csrf
                    <div class="row">
                        <div class="field">
                            <label for="item_name">Nomi</label>
                            <input id="item_name" name="name" value="{{ old('name') }}" required maxlength="255">
                        </div>
                        <div class="field">
                            <label for="paid_quantity">Pullik soni</label>
                            <input id="paid_quantity" type="number" name="paid_quantity" min="0" value="{{ old('paid_quantity', 0) }}" required>
                        </div>
                        <div class="field">
                            <label for="bonus_quantity">Bonus soni</label>
                            <input id="bonus_quantity" type="number" name="bonus_quantity" min="0" value="{{ old('bonus_quantity', 0) }}" required>
                        </div>
                        <div class="field">
                            <label for="unit_price">Narxi</label>
                            <input id="unit_price" name="unit_price" value="{{ old('unit_price') }}" placeholder="2,50" required inputmode="decimal" style="width: 120px;">
                        </div>
                        <button type="submit">Qo'shish</button>
                    </div>
                    @foreach (['name', 'paid_quantity', 'bonus_quantity', 'unit_price'] as $field)
                        @error($field) <div class="error">{{ $message }}</div> @enderror
                    @endforeach
                </form>
            @endcan
        @endif
    </section>

    <section>
        <h2>To'lov bosqichlari</h2>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Bosqich</th>
                <th class="num">Foiz</th>
                <th class="num">Summa</th>
                <th>Qachon to'lanadi</th>
                <th>Muddat sanasi</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($stages as $stage)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $stage->name }}</td>
                    <td class="num">{{ $stage->percent }}%</td>
                    <td class="num">{{ $stage->amount?->format() ?? $paidTotal->percent($stage->percent)->format().' *' }}</td>
                    <td>{{ $stage->condition->label() }}</td>
                    <td>
                        @if ($isDraft && auth()->user()->can('manage', $contract))
                            <form method="POST" action="{{ route('contracts.stages.update', [$contract, $stage]) }}" class="row">
                                @csrf
                                @method('PATCH')
                                <input type="date" name="due_date" value="{{ old('due_date', $stage->due_date?->toDateString()) }}">
                                <button type="submit" class="btn-light">Saqlash</button>
                            </form>
                        @else
                            {{ $stage->due_date?->format('d.m.Y') ?? '—' }}
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @if ($isDraft)
            <p class="muted">* Taxminiy summa. Aniq summa faollashtirishda yoziladi (yaxlitlash farqi oxirgi bosqichga tushadi).</p>
            @error('due_date') <div class="error">{{ $message }}</div> @enderror
        @endif
    </section>

    <section>
        <h2>Jarima</h2>
        <form method="GET" action="{{ route('contracts.show', $contract) }}" class="row" style="margin-bottom: 14px;">
            <div class="field">
                <label for="as_of">Hisoblash sanasi</label>
                <input id="as_of" type="date" name="as_of" value="{{ $asOf->toDateString() }}">
                @error('as_of') <div class="error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn-light">Hisoblash</button>
        </form>

        @if ($penalty === null)
            <p class="muted">Shartnoma faollashtirilmagan — jarima hisoblanmaydi.</p>
        @else
            <div class="grid" style="margin-bottom: 14px;">
                <div class="stat"><div class="label">Muddat sanasi</div><div class="value">{{ $penalty->dueDate->format('d.m.Y') }}</div></div>
                <div class="stat"><div class="label">Kechikish kunlari</div><div class="value">{{ $penalty->lateDays() }}</div></div>
                <div class="stat"><div class="label">Jarima ({{ $penalty->asOf->format('d.m.Y') }} holatiga)</div><div class="value">{{ $penalty->total->format() }}</div></div>
                <div class="stat"><div class="label">Limit (muddat kunidagi kelmagan summaning 50%)</div><div class="value">{{ $penalty->cap->format() }}</div></div>
            </div>

            @if ($penalty->isBeforeDueDate())
                <p class="muted">Hisoblash sanasi muddat sanasidan oldin yoki unga teng — kechikish yo'q.</p>
            @elseif ($penalty->isCapApplied())
                <p class="muted">Kunlik jarimalar yig'indisi {{ $penalty->accrued->format() }}, limit qo'llandi.</p>
            @endif

            @if ($penalty->days !== [])
                <table>
                    <thead>
                    <tr><th>Sana</th><th class="num">Kelmagan pullik summa</th><th class="num">O'sha kun jarimasi</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($penalty->days as $day)
                        <tr>
                            <td>{{ $day->date->format('d.m.Y') }}</td>
                            <td class="num">{{ $day->undelivered->format() }}</td>
                            <td class="num">{{ $day->penalty->format() }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif

            @can('decidePenalty', $contract)
                @if ($penalty->total->isPositive())
                    <div class="row" style="margin-top: 14px; align-items: flex-start;">
                        <form method="POST" action="{{ route('contracts.penalty.approve', $contract) }}" class="card" style="flex: 1; min-width: 300px;">
                            @csrf
                            <input type="hidden" name="as_of" value="{{ $asOf->toDateString() }}">
                            <h2>Tasdiqlash</h2>
                            <div class="field">
                                <label for="approved_amount">Tasdiqlanadigan summa (bo'sh — hisoblangan {{ $penalty->total->format() }})</label>
                                <input id="approved_amount" name="approved_amount" value="{{ old('approved_amount') }}" inputmode="decimal">
                                @error('approved_amount') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div class="field">
                                <label for="comment">Izoh</label>
                                <textarea id="comment" name="comment" rows="2" style="width: 100%;">{{ old('comment') }}</textarea>
                            </div>
                            <button type="submit" class="btn-success">Tasdiqlash</button>
                        </form>
                        <form method="POST" action="{{ route('contracts.penalty.reject', $contract) }}" class="card" style="flex: 1; min-width: 300px;">
                            @csrf
                            <input type="hidden" name="as_of" value="{{ $asOf->toDateString() }}">
                            <h2>Rad etish</h2>
                            <div class="field">
                                <label for="reason">Sabab</label>
                                <textarea id="reason" name="reason" rows="2" required style="width: 100%;">{{ old('reason') }}</textarea>
                                @error('reason') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn-danger">Rad etish</button>
                        </form>
                    </div>
                    <p class="muted">Qaror {{ $asOf->format('d.m.Y') }} holatidagi hisob bo'yicha qabul qilinadi. Yangi qaror oldingisini almashtiradi.</p>
                @endif
            @endcan
        @endif

        @if ($decisions->isNotEmpty())
            <h2 style="margin-top: 16px;">Boshliq qarorlari</h2>
            <table>
                <thead>
                <tr><th>Holat</th><th>Hisoblash sanasi</th><th class="num">Hisoblangan</th><th class="num">Tasdiqlangan</th><th>Izoh / sabab</th><th>Kim</th><th>Qachon</th></tr>
                </thead>
                <tbody>
                @foreach ($decisions as $decision)
                    <tr>
                        <td>
                            <span class="badge badge-{{ $decision->status->value }}">{{ $decision->status->label() }}</span>
                            @if ($loop->first) <span class="muted">(amaldagi)</span> @endif
                        </td>
                        <td>{{ $decision->calculated_as_of->format('d.m.Y') }}</td>
                        <td class="num">{{ $decision->calculated_amount->format() }}</td>
                        <td class="num">{{ $decision->isApproved() ? $decision->effectiveAmount()->format() : '—' }}</td>
                        <td>{{ $decision->comment }}</td>
                        <td>{{ $decision->decider->name }}</td>
                        <td>{{ $decision->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section>
        <h2>Kelgan partiyalar</h2>
        @if ($contract->deliveries->isEmpty())
            <p class="muted">Partiyalar yo'q.</p>
        @else
            <table>
                <thead><tr><th>Sana</th><th>Tovar</th><th class="num">Pullik</th><th class="num">Bonus</th><th>Kiritgan</th></tr></thead>
                <tbody>
                @foreach ($contract->deliveries as $delivery)
                    @foreach ($delivery->items as $line)
                        <tr>
                            <td>{{ $delivery->delivered_on->format('d.m.Y') }}</td>
                            <td>{{ $line->contractItem->name }}</td>
                            <td class="num">{{ $line->paid_quantity }}</td>
                            <td class="num">{{ $line->bonus_quantity }}</td>
                            <td>{{ $delivery->creator->name }}</td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
