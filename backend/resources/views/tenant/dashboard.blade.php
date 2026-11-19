<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Dashboard - Boarding House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ route('tenant.dashboard') }}">Tenant Portal</a>
            <div class="d-flex align-items-center gap-3">
                <a class="btn btn-outline-primary btn-sm" href="{{ route('house.rooms') }}">House rooms</a>
                <form action="{{ route('tenant.logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Sign out</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <h1 class="h3 mb-1">Welcome, {{ auth()->user()->name }}</h1>
        <p class="text-secondary mb-4">Your boarding information and account activity.</p>

        @if(!$tenant)
            <div class="alert alert-info" role="status">Your login is not linked to a tenant record yet. Please contact the house administrator.</div>
        @else
            <section class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5">Your room</h2>
                    @if($tenant->room)
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Room</dt>
                            <dd class="col-sm-9">{{ $tenant->room->room_number }}</dd>
                            <dt class="col-sm-3">Type</dt>
                            <dd class="col-sm-9">{{ $tenant->room->room_type }}</dd>
                            <dt class="col-sm-3">Monthly rate</dt>
                            <dd class="col-sm-9">{{ number_format((float) $tenant->room->price, 2) }}</dd>
                        </dl>
                    @else
                        <p class="text-secondary mb-0">No room is assigned to your tenant record.</p>
                    @endif
                </div>
            </section>

            <section class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Recent payments</h2>
                    @forelse($tenant->payments->sortByDesc('payment_date')->take(5) as $payment)
                        <div class="d-flex justify-content-between border-top py-2">
                            <span>{{ $payment->payment_date?->format('M j, Y') ?? 'Date not set' }}</span>
                            <span>{{ number_format((float) $payment->amount, 2) }} · {{ ucfirst($payment->status) }}</span>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No payments are recorded yet.</p>
                    @endforelse
                </div>
            </section>
        @endif
    </main>
</body>
</html>
