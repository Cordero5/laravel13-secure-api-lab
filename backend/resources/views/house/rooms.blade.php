<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>House Rooms - Boarding House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar bg-white border-bottom">
        <div class="container">
            <span class="navbar-brand fw-semibold">House Rooms</span>
            <a class="btn btn-outline-primary btn-sm" href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('tenant.dashboard') }}">Back to dashboard</a>
        </div>
    </nav>

    <main class="container py-4">
        <h1 class="h3 mb-1">Available rooms</h1>
        <p class="text-secondary mb-4">Room information shared with residents.</p>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Room</th>
                            <th scope="col">Type</th>
                            <th scope="col">Monthly rate</th>
                            <th scope="col">Occupancy</th>
                            <th scope="col">Availability</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                            <tr>
                                <th scope="row">{{ $room['room_number'] }}</th>
                                <td>{{ $room['room_type'] }}</td>
                                <td>{{ number_format((float) $room['price'], 2) }}</td>
                                <td>{{ $room['occupancy'] }} / {{ $room['capacity'] }}</td>
                                <td>{{ $room['is_full'] ? 'Full' : $room['available_slots'] . ' slot(s) available' }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-secondary text-center py-4" colspan="5">No rooms are listed yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
