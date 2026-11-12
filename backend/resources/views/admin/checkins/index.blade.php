@extends('admin.layout')

@section('title', 'Check-In / Check-Out')
@section('heading', 'Check-In / Check-Out Management')
@section('aside', $checkIns->where('status', 'checked_in')->count() . ' currently checked in')

@section('content')
<div class="row">
    <!-- New Check-In Form -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-3">Record Check-In</h5>
                <p class="text-muted small">Checking a tenant in assigns them the room you pick here, and the room is released again on check-out.</p>
                <form action="{{ route('admin.checkins.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Tenant</label>
                        <select name="tenant_id" class="form-select" required>
                            <option value="">Select tenant&hellip;</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                    {{ $tenant->name ?: 'Unnamed #' . $tenant->id }}
                                    @if($tenant->room)
                                        &mdash; Room {{ $tenant->room->room_number }}
                                    @else
                                        &mdash; no room yet
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room</label>
                        <select name="room_id" class="form-select" required>
                            <option value="">Select room&hellip;</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>
                                    Room {{ $room->room_number }} &mdash; {{ $room->occupancy }}/{{ $room->capacity }}
                                    @if($room->is_full)(full)@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Check-In Date &amp; Time</label>
                        <input type="datetime-local" name="check_in_date" class="form-control"value="{{ old('check_in_date', now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Check-In</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Check-In Records -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-3">Movement Records</h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tenant</th>
                                <th>Room</th>
                                <th>Checked In</th>
                                <th>Checked Out</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($checkIns as $entry)
                                <tr>
                                    <td class="text-muted">{{ $entry->id }}</td>
                                    <td><strong>{{ $entry->tenant->name ?? 'Unlinked' }}</strong></td>
                                    <td>{{ $entry->room ? 'Room ' . $entry->room->room_number : '&mdash;' }}</td>
                                    <td class="small">{{ $entry->check_in_date ? $entry->check_in_date->format('M d, Y h:i A') : '&mdash;' }}</td>
                                    <td class="small">{{ $entry->check_out_date ? $entry->check_out_date->format('M d, Y h:i A') : '&mdash;' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $entry->status === 'checked_in' ? 'primary' : 'secondary' }}">
                                            {{ $entry->status === 'checked_in' ? 'Checked In' : 'Checked Out' }}
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @if($entry->status === 'checked_in')
                                            <form action="{{ route('admin.checkins.checkout', $entry->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="check_out_date" value="{{ now()->format('Y-m-d\TH:i') }}">
                                                <button type="submit" class="btn btn-sm btn-outline-success"
                                                        onclick="return confirm('Check this tenant out now?');">Check Out</button>
                                            </form>
                                        @endif

                                        <button type="button" class="btn btn-sm btn-outline-primary js-edit-checkin"
                                                data-id="{{ $entry->id }}"
                                                data-tenant-id="{{ $entry->tenant_id }}"
                                                data-room-id="{{ $entry->room_id }}"
                                                data-check-in="{{ $entry->check_in_date ? $entry->check_in_date->format('Y-m-d\TH:i') : '' }}">
                                            Edit
                                        </button>

                                        <form action="{{ route('admin.checkins.destroy', $entry->id) }}" method="POST"class="d-inline" onsubmit="return confirm('Delete this record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No check-in records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Check-In Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true"
    data-update-url="{{ route('admin.checkins.update', ['checkIn' => '__ID__']) }}">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="editForm" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Check-In Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tenant</label>
                    <select name="tenant_id" id="edit_tenant_id" class="form-select" required>
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}">{{ $tenant->name ?: 'Unnamed #' . $tenant->id }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Room</label>
                    <select name="room_id" id="edit_room_id" class="form-select" required>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}">Room {{ $room->room_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Check-In Date &amp; Time</label>
                    <input type="datetime-local" name="check_in_date" id="edit_check_in" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Record</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    const editForm = document.getElementById('editForm');
    const baseUrl = document.getElementById('editModal').dataset.updateUrl;

    document.querySelectorAll('.js-edit-checkin').forEach(function (button) {
        button.addEventListener('click', function () {
            const d = this.dataset;
            editForm.action = baseUrl.replace('__ID__', d.id);
            document.getElementById('edit_tenant_id').value = d.tenantId;
            document.getElementById('edit_room_id').value = d.roomId;
            document.getElementById('edit_check_in').value = d.checkIn;
            editModal.show();
        });
    });
</script>
@endpush
