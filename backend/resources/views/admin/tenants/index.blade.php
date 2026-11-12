@extends('admin.layout')

@section('title', 'Manage Tenants')
@section('heading', 'Tenants Management')
@section('aside', $tenants->count() . ' tenant(s)')

@section('content')

            <div class="row">
                <!-- Add Tenant Form -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Register New Tenant</h5>
                            <form action="{{ route('admin.tenants.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="phone" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Assign Room</label>
                                    <select name="room_id" class="form-select">
                                        <option value="">None / Unassigned</option>
                                        @foreach($rooms as $room)
                                            <option value="{{ $room->id }}" @disabled($room->is_full)>
                                                Room {{ $room->room_number }} (&#8369;{{ number_format($room->price, 2) }}) &mdash;
                                                {{ $room->occupancy }}/{{ $room->capacity }}
                                                @if($room->is_full) FULL @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Register Tenant</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tenants List Table -->
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Registered Tenants</h5>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Assigned Room</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
<tbody>
    @forelse($tenants as $tenant)
        <tr>
            <td class="text-muted">{{ $tenant->id }}</td>
            <td><strong>{{ $tenant->name ?: 'Unnamed' }}</strong></td>
            <td>{{ $tenant->email ?: 'N/A' }}</td>
            <td>{{ $tenant->phone ?: 'N/A' }}</td>
            <td>
                @if($tenant->room)
                    <span class="badge bg-success">Room {{ $tenant->room->room_number }}</span>
                    <span class="text-muted small">(&#8369;{{ number_format($tenant->room->price, 2) }})</span>
                @else
                    <span class="badge bg-secondary">Unassigned</span>
                @endif
            </td>
            <td class="text-end text-nowrap">
                <button type="button"
                        class="btn btn-sm btn-outline-primary js-edit-tenant"
                        data-id="{{ $tenant->id }}"
                        data-name="{{ $tenant->name }}"
                        data-email="{{ $tenant->email }}"
                        data-phone="{{ $tenant->phone }}"
                        data-room-id="{{ $tenant->room_id }}">
                    Edit
                </button>

                <form action="{{ route('admin.tenants.destroy', $tenant->id) }}" method="POST"
                    class="d-inline"
                    onsubmit="return confirm('Delete tenant {{ $tenant->name }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="text-center text-muted py-4">No tenants registered yet.</td>
        </tr>
    @endforelse
</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<!-- Edit Tenant Modal -->
<div class="modal fade" id="editTenantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="editTenantForm" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Tenant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Assign Room</label>
                    <select name="room_id" id="edit_room_id" class="form-select">
                        <option value="">None / Unassigned</option>
                        @foreach($rooms as $room)
                            {{-- Not disabled here: the tenant being edited already occupies a slot,and the controller re-checks capacity excluding them. --}}
                            <option value="{{ $room->id }}">
                                Room {{ $room->room_number }} (&#8369;{{ number_format($room->price, 2) }}) &mdash;
                                {{ $room->occupancy }}/{{ $room->capacity }}
                                @if($room->is_full) FULL @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Tenant</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const editModal = new bootstrap.Modal(document.getElementById('editTenantModal'));
    const editForm = document.getElementById('editTenantForm');
    const baseUrl = "{{ route('admin.tenants.update', ['tenant' => '__ID__']) }}";

    document.querySelectorAll('.js-edit-tenant').forEach(function (button) {
        button.addEventListener('click', function () {
            const d = this.dataset;
            editForm.action = baseUrl.replace('__ID__', d.id);
            document.getElementById('edit_name').value = d.name || '';
            document.getElementById('edit_email').value = d.email || '';
            document.getElementById('edit_phone').value = d.phone || '';
            document.getElementById('edit_room_id').value = d.roomId || '';
            editModal.show();
        });
    });
</script>
@endpush
