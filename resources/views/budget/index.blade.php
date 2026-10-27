@extends('layouts.app')

@section('title', 'Smeta')

@section('content')
    <h1>Smeta (byudjet)</h1>

    <section>
        <table>
            <thead>
            <tr>
                <th>Moliyaviy yil</th>
                <th>Xarajat moddasi</th>
                <th>Oy</th>
                <th class="num">Ajratilgan</th>
                <th class="num">To'langan</th>
                <th class="num">Qoldiq</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($lines as $line)
                <tr>
                    <td>{{ $line->fiscal_year }}</td>
                    <td>{{ $line->expense_item }}</td>
                    <td>{{ $line->monthName() }}</td>
                    <td class="num">{{ $line->allocated_amount->format() }}</td>
                    <td class="num">{{ $line->spentAmount()->format() }}</td>
                    <td class="num"><strong>{{ $line->remaining()->format() }}</strong></td>
                    <td>
                        <a class="btn btn-light" href="{{ route('budget-lines.edit', $line) }}">Tahrirlash</a>
                        @if ($line->spentAmount()->isZero())
                            <form method="POST" action="{{ route('budget-lines.destroy', $line) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger">O'chirish</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Smeta qatorlari yo'q.</td></tr>
            @endforelse
            </tbody>
        </table>
        <p class="muted">Oy qoldig'i = ajratilgan − shu oy bo'yicha faol (bekor qilinmagan) to'lovlar.</p>
    </section>

    <section>
        <h2>Smeta qatori qo'shish</h2>
        <form method="POST" action="{{ route('budget-lines.store') }}">
            @csrf
            <div class="row">
                <div class="field">
                    <label for="fiscal_year">Moliyaviy yil</label>
                    <input id="fiscal_year" type="number" name="fiscal_year" min="2000" max="2100" value="{{ old('fiscal_year', now()->year) }}" required>
                </div>
                <div class="field">
                    <label for="expense_item">Xarajat moddasi</label>
                    <input id="expense_item" name="expense_item" value="{{ old('expense_item') }}" required maxlength="255">
                </div>
                <div class="field">
                    <label for="month">Oy</label>
                    <select id="month" name="month" required>
                        @foreach ($months as $number => $name)
                            <option value="{{ $number }}" @selected((int) old('month') === $number)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="allocated_amount">Ajratilgan summa</label>
                    <input id="allocated_amount" name="allocated_amount" value="{{ old('allocated_amount') }}" required inputmode="decimal" style="width: 140px;">
                </div>
                <button type="submit">Qo'shish</button>
            </div>
            @foreach (['fiscal_year', 'expense_item', 'month', 'allocated_amount'] as $field)
                @error($field) <div class="error">{{ $message }}</div> @enderror
            @endforeach
        </form>
    </section>
@endsection
