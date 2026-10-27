@extends('layouts.app')

@section('title', 'Kirish')

@section('content')
    <section style="max-width: 380px; margin: 60px auto;">
        <h1>Tizimga kirish</h1>
        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus style="width: 100%;">
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="password">Parol</label>
                <input id="password" type="password" name="password" required style="width: 100%;">
                @error('password') <div class="error">{{ $message }}</div> @enderror
            </div>
            <button type="submit">Kirish</button>
        </form>
    </section>
@endsection
