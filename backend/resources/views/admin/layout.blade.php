<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Boarding House Admin') - Boarding House Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    @stack('styles')
    <style>
        :root {
            --bh-bg: #f4f6fb;
            --bh-surface: #ffffff;
            --bh-ink: #1b2430;
            --bh-muted: #6b7688;
            --bh-line: #e6eaf2;
            --bh-brand: #2c516e;
            --bh-brand-2: #40739e;
            --bh-brand-soft: #eaf0f6;
            --bh-ok: #1f8a55;
            --bh-warn: #b5730a;
            --bh-bad: #c93c3c;
            --bh-radius: 14px;
            --bh-shadow: 0 1px 2px rgba(20, 30, 45, .05), 0 10px 26px -14px rgba(20, 30, 45, .18);
        }

        body {
            min-height: 100vh;
            background-color: var(--bh-bg);
            color: var(--bh-ink);
            -webkit-font-smoothing: antialiased;
        }

        /* Sidebar */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1e2836 0%, #171e26 100%);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, .6);
            font-weight: 500;
            font-size: .925rem;
            padding: 10px 14px;
            margin: 2px 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 11px;
            transition: background-color .15s ease, color .15s ease;
        }
        .sidebar .nav-link i { font-size: 1.05rem; width: 1.1rem; text-align: center; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background-color: rgba(255, 255, 255, .07); }
        .sidebar .nav-link.active { box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .09); }
        .sidebar .nav-link.active i { color: #8ec3ea; }

        /* Cards — these two rules intentionally outrank Bootstrap's !important
           helpers so every page shares one card treatment. */
        .card { border-radius: var(--bh-radius); }
        .card.border-0 { border: 1px solid var(--bh-line) !important; }
        .card.shadow-sm { box-shadow: var(--bh-shadow) !important; }
        .card-title { font-weight: 650; letter-spacing: -.011em; }

        /* Tables */
        .table { --bs-table-border-color: #eef1f7; margin-bottom: 0; }
        .table > :not(caption) > * > * { padding: .7rem .75rem; }
        .table thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            font-weight: 650;
            color: var(--bh-muted);
            background: transparent;
            border-bottom: 1px solid var(--bh-line);
        }

        /* Controls */
        .btn { border-radius: 9px; --bs-btn-font-weight: 550; }
        .btn-primary {
            --bs-btn-bg: var(--bh-brand); --bs-btn-border-color: var(--bh-brand);
            --bs-btn-hover-bg: #23445e; --bs-btn-hover-border-color: #23445e;
            --bs-btn-active-bg: #1d3a51; --bs-btn-active-border-color: #1d3a51;
        }
        .btn-outline-secondary {
            --bs-btn-color: #4a5568; --bs-btn-border-color: #d3dae6;
            --bs-btn-hover-bg: #eef1f6; --bs-btn-hover-border-color: #c6cfdd; --bs-btn-hover-color: var(--bh-ink);
        }
        .form-control { border-radius: 9px; border-color: #d8dfea; }
        .form-control:focus { border-color: var(--bh-brand-2); box-shadow: 0 0 0 3px rgba(64, 115, 158, .14); }
        .form-label { font-size: .82rem; font-weight: 600; color: var(--bh-muted); }
        .badge { border-radius: 999px; font-weight: 600; letter-spacing: .015em; }
        .alert { border: 0; border-radius: 12px; box-shadow: var(--bh-shadow); }
        .page-head { border-bottom: 1px solid var(--bh-line) !important; }
        .page-head h1 { font-weight: 700; letter-spacing: -.02em; }
        .avatar {
            width: 34px; height: 34px; border-radius: 10px; flex: 0 0 34px;
            background: var(--bh-brand); color: #fff;
            display: grid; place-items: center; font-size: .78rem; font-weight: 700;
        }

        @media (prefers-reduced-motion: reduce) {
            .sidebar .nav-link, .btn { transition: none; }
        }
    </style>
</head>
<body>

@php
    $nav = [
        ['route' => 'admin.dashboard',      'label' => 'Dashboard',              'icon' => 'speedometer2',   'pattern' => 'admin.dashboard'],
        ['route' => 'admin.rooms.index',     'label' => 'Rooms',                  'icon' => 'door-closed',    'pattern' => 'admin.rooms.*'],
        ['route' => 'admin.tenants.index',   'label' => 'Tenants',                'icon' => 'people',         'pattern' => 'admin.tenants.*'],
        ['route' => 'admin.payments.index',  'label' => 'Payments',               'icon' => 'credit-card',    'pattern' => 'admin.payments.*'],
        ['route' => 'admin.checkins.index',  'label' => 'Check-In / Check-Out',   'icon' => 'box-arrow-in-right', 'pattern' => 'admin.checkins.*'],
        ['route' => 'admin.rules.index',     'label' => 'House Rules',            'icon' => 'journal-text',   'pattern' => 'admin.rules.*'],
        ['route' => 'admin.announcements.index', 'label' => 'Announcements',      'icon' => 'megaphone',      'pattern' => 'admin.announcements.*'],
    ];
@endphp

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse px-0">
            <div class="p-3 text-white border-bottom border-secondary d-flex align-items-center gap-2">
                <span class="avatar" style="background:rgba(142,195,234,.16);color:#8ec3ea;border-radius:10px">
                    <i class="bi bi-buildings"></i>
                </span>
                <div>
                    <h5 class="mb-0" style="font-size:.98rem;font-weight:650;letter-spacing:-.01em">Boarding House</h5>
                    <div class="text-uppercase" style="font-size:.62rem;letter-spacing:.12em;color:rgba(255,255,255,.42)">Admin panel</div>
                </div>
            </div>
            <ul class="nav flex-column mt-3">
                @foreach($nav as $item)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs($item['pattern']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                            <i class="bi bi-{{ $item['icon'] }}"></i> {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="page-head d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-3 mb-4">
                <div>
                    <h1 class="h2">@yield('heading', 'Dashboard')</h1>
                    <span class="text-muted">@yield('aside')</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    @auth
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar">{{ Str::of(auth()->user()->name)->trim()->explode(' ')->map(fn ($part) => strtoupper(Str::substr($part, 0, 1)))->take(2)->implode('') }}</span>
                            <div class="text-end d-none d-sm-block">
                                <div class="fw-semibold" style="font-size:.86rem;line-height:1.2">{{ auth()->user()->name }}</div>
                                <div class="text-muted" style="font-size:.74rem">{{ ucfirst(auth()->user()->role ?? 'staff') }}</div>
                            </div>
                        </div>
                        <form action="{{ route('admin.logout') }}" method="POST" class="mb-0">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Logout</button>
                        </form>
                    @endauth
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <strong>Please fix the following:</strong>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')

</body>
</html>
