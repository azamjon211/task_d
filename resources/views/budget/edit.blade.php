@extends('layouts.app')

@section('title', 'Smeta qatorini tahrirlash')

@section('content')
    <h1>Smeta qatori: {{ $line->label() }}</h1>

    <section style="max-width: 480px;">
        <form method="POST" action="{{ route('budget-lines.update', $line) }}">
            @csrf
            @method('PUT')
            <div class="field">
                <label for="allocated_amount">Ajratilgan summa</label>
                <input id="allocated_amount" name="allocated_amount" value="{{ old('allocated_amount', $line->allocated_amount->amount) }}" required inputmode="decimal">
                @error('allocated_amount') <div class="error">{{ $message }}</div> @enderror
                <div class="muted">Shu oy bo'yicha to'langan summadan kam qilib bo'lmaydi.</div>
            </div>
            <button type="submit">Saqlash</button>
            <a class="btn btn-light" href="{{ route('budget-lines.index') }}">Bekor qilish</a>
        </form>
    </section>
@endsection
