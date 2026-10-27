@extends('layouts.app')

@php($editing = $contract->exists)

@section('title', $editing ? 'Shartnomani tahrirlash' : 'Yangi shartnoma')

@section('content')
    <h1>{{ $editing ? "Shartnomani tahrirlash: {$contract->number}" : 'Yangi shartnoma' }}</h1>

    <section style="max-width: 560px;">
        <form method="POST" action="{{ $editing ? route('contracts.update', $contract) : route('contracts.store') }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="field">
                <label for="number">Shartnoma raqami</label>
                <input id="number" name="number" value="{{ old('number', $contract->number) }}" required maxlength="50">
                @error('number') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="supplier">Yetkazuvchi</label>
                <input id="supplier" name="supplier" value="{{ old('supplier', $contract->supplier) }}" required maxlength="255" style="width: 100%;">
                @error('supplier') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="row" style="margin-bottom: 12px;">
                <div class="field">
                    <label for="start_date">Boshlanish sanasi</label>
                    <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $contract->start_date?->toDateString()) }}" required>
                    @error('start_date') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="duration_days">Muddat (kun)</label>
                    <input id="duration_days" type="number" name="duration_days" min="1" max="3650" value="{{ old('duration_days', $contract->duration_days) }}" required>
                    @error('duration_days') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>
            <p class="muted">Muddat sanasi = boshlanish sanasi + muddat (kalendar kunlarida).</p>

            <button type="submit">Saqlash</button>
            <a class="btn btn-light" href="{{ $editing ? route('contracts.show', $contract) : route('contracts.index') }}">Bekor qilish</a>
        </form>
    </section>
@endsection
