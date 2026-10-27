@extends('layouts.app')

@section('title', 'Shartnomalar')

@section('content')
    <div class="actions">
        <h1 style="margin: 0; flex: 1;">Shartnomalar</h1>
        @can('create', \App\Models\Contract::class)
            <a class="btn" href="{{ route('contracts.create') }}">+ Yangi shartnoma</a>
        @endcan
    </div>

    <section>
        @if ($contracts->isEmpty())
            <p class="muted">Shartnomalar yo'q.</p>
        @else
            <table>
                <thead>
                <tr>
                    <th>Raqam</th>
                    <th>Yetkazuvchi</th>
                    <th>Boshlanish</th>
                    <th>Muddat sanasi</th>
                    <th class="num">Pullik summa</th>
                    <th>Holat</th>
                    <th>Ijrochi</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($contracts as $contract)
                    <tr>
                        <td><a href="{{ route('contracts.show', $contract) }}">{{ $contract->number }}</a></td>
                        <td>{{ $contract->supplier }}</td>
                        <td>{{ $contract->start_date->format('d.m.Y') }}</td>
                        <td>{{ $contract->dueDate()->format('d.m.Y') }}</td>
                        <td class="num">{{ $contract->itemsPaidTotal()->format() }}</td>
                        <td><span class="badge badge-{{ $contract->status->value }}">{{ $contract->status->label() }}</span></td>
                        <td>{{ $contract->creator->name }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($contracts->hasPages())
                <div class="actions" style="margin: 8px 0 0; align-items: center;">
                    @if ($contracts->previousPageUrl())
                        <a class="btn btn-light" href="{{ $contracts->previousPageUrl() }}">&larr; Oldingi</a>
                    @endif
                    <span class="muted">{{ $contracts->currentPage() }} / {{ $contracts->lastPage() }} sahifa</span>
                    @if ($contracts->nextPageUrl())
                        <a class="btn btn-light" href="{{ $contracts->nextPageUrl() }}">Keyingi &rarr;</a>
                    @endif
                </div>
            @endif
        @endif
    </section>
@endsection
