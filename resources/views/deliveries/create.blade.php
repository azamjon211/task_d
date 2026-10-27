@extends('layouts.app')

@section('title', 'Partiya kiritish')

@section('content')
    <h1>Partiya kiritish — shartnoma {{ $contract->number }}</h1>

    <section>
        <form method="POST" action="{{ route('contracts.deliveries.store', $contract) }}">
            @csrf
            <div class="field">
                <label for="delivered_on">Partiya sanasi</label>
                <input id="delivered_on" type="date" name="delivered_on" value="{{ old('delivered_on', now()->toDateString()) }}" required>
                @error('delivered_on') <div class="error">{{ $message }}</div> @enderror
                <div class="muted">Partiya jarima hisobida shu sanadan keyingi kundan hisobga olinadi.</div>
            </div>

            <table>
                <thead>
                <tr>
                    <th>Tovar</th>
                    <th class="num">Pullik qoldiq</th>
                    <th>Pullik keldi</th>
                    <th class="num">Bonus qoldiq</th>
                    <th>Bonus keldi</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($items as $itemId => $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td class="num">{{ $item->remainingPaid() }}</td>
                        <td>
                            <input type="number" name="items[{{ $itemId }}][paid]" min="0" value="{{ old("items.$itemId.paid", 0) }}">
                            @error("items.$itemId.paid") <div class="error">{{ $message }}</div> @enderror
                        </td>
                        <td class="num">{{ $item->remainingBonus() }}</td>
                        <td>
                            <input type="number" name="items[{{ $itemId }}][bonus]" min="0" value="{{ old("items.$itemId.bonus", 0) }}">
                            @error("items.$itemId.bonus") <div class="error">{{ $message }}</div> @enderror
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @error('items') <div class="error">{{ $message }}</div> @enderror

            <button type="submit">Qabul qilish</button>
            <a class="btn btn-light" href="{{ route('contracts.show', $contract) }}">Bekor qilish</a>
        </form>
    </section>
@endsection
