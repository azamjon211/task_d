<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Shartnomalar') — {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; margin: 0; color: #1f2933; background: #f5f7fa; font-size: 14px; }
        header { background: #243b53; color: #fff; padding: 10px 24px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        header a { color: #fff; text-decoration: none; font-weight: 500; }
        header .user { margin-left: auto; display: flex; gap: 12px; align-items: center; }
        main { max-width: 1200px; margin: 0 auto; padding: 20px 24px 60px; }
        h1 { font-size: 22px; margin: 0 0 16px; }
        h2 { font-size: 17px; margin: 0 0 12px; }
        section, .card { background: #fff; border: 1px solid #d9e2ec; border-radius: 6px; padding: 16px; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e4e7eb; vertical-align: top; }
        th { background: #f0f4f8; font-weight: 600; font-size: 13px; }
        td.num, th.num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        input, select, textarea { font: inherit; padding: 6px 8px; border: 1px solid #bcccdc; border-radius: 4px; max-width: 100%; }
        input[type=number] { width: 110px; }
        label { display: block; font-weight: 500; margin-bottom: 4px; }
        .field { margin-bottom: 12px; }
        .row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
        .row .field { margin-bottom: 0; }
        button, .btn { font: inherit; display: inline-block; padding: 6px 14px; border: 1px solid #334e68; background: #334e68; color: #fff; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn-light { background: #fff; color: #334e68; }
        .btn-danger { background: #c62828; border-color: #c62828; }
        .btn-success { background: #2e7d32; border-color: #2e7d32; }
        .inline { display: inline; }
        .alert { padding: 10px 14px; border-radius: 4px; margin-bottom: 16px; }
        .alert-success { background: #e3f9e5; border: 1px solid #91d8a0; }
        .alert-error { background: #ffeeee; border: 1px solid #f29b9b; }
        .error { color: #c62828; font-size: 13px; margin-top: 3px; }
        .muted { color: #627d98; font-size: 13px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: 600; background: #e4e7eb; }
        .badge-draft, .badge-planned { background: #e4e7eb; }
        .badge-active, .badge-ready_to_pay { background: #dceefb; color: #0b4f8a; }
        .badge-completed, .badge-paid, .badge-approved { background: #e3f9e5; color: #1e6b2b; }
        .badge-partially_paid { background: #fff3c4; color: #8d6a00; }
        .badge-overdue, .badge-rejected, .badge-voided { background: #ffe3e3; color: #a61b1b; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; }
        .stat { background: #f0f4f8; border-radius: 4px; padding: 10px; }
        .stat .label { color: #627d98; font-size: 12px; }
        .stat .value { font-size: 18px; font-weight: 600; margin-top: 2px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
    </style>
</head>
<body>
@auth
    <header>
        <a href="{{ route('contracts.index') }}">Shartnomalar</a>
        @can('viewAny', \App\Models\BudgetLine::class)
            <a href="{{ route('budget-lines.index') }}">Smeta</a>
        @endcan
        <div class="user">
            <span>{{ auth()->user()->name }} ({{ auth()->user()->role->label() }})</span>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="btn-light">Chiqish</button>
            </form>
        </div>
    </header>
@endauth
<main>
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @error('general')
        <div class="alert alert-error">{{ $message }}</div>
    @enderror

    @yield('content')
</main>
</body>
</html>
