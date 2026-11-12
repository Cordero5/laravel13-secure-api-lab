@extends('admin.layout')

@section('title', 'Manage Rooms')
@section('heading', 'Rooms Management')
@section('aside', $rooms->count() . ' room(s)')

@section('content')
            <div class="row">
                <!-- Add Room Form -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Add New Room</h5>
                            <form action="{{ route('admin.rooms.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Room Number</label>
                                    <input type="text" name="room_number" class="form-control"
                                           value="{{ old('room_number') }}" placeholder="e.g. 101" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Room Type</label>
                                    <select name="room_type" class="form-select" required>
                                        @foreach(['single', 'double', 'deluxe', 'standard'] as $type)
                                            <option value="{{ $type }}" @selected(old('room_type') === $type)>
                                                {{ ucfirst($type) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Price (&#8369;)</label>
                                    <input type="number" step="0.01" min="0" name="price" class="form-control"
                                           value="{{ old('price') }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Capacity</label>
                                    <input type="number" min="1" max="255" step="1" name="capacity" class="form-control"
                                           value="{{ old('capacity', 1) }}" required>
                                    <div class="form-text">
                                        Status is set automatically: the room shows
                                        <span class="badge bg-danger">Occupied</span> once this many tenants are assigned.
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Save Room</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Rooms List Table -->
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Registered Rooms</h5>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Room #</th>
                                            <th>Type</th>
                                            <th>Price</th>
                                            <th>Occupancy</th>
                                            <th>Capacity</th>
                                            <th>Status</th>
                                            <th>Tenant(s)</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($rooms as $room)
                                            <tr>
                                                <td class="text-muted">{{ $room->id }}</td>
                                                <td><strong>{{ $room->room_number }}</strong></td>
                                                <td>{{ ucfirst($room->room_type) }}</td>
                                                <td>&#8369;{{ number_format($room->price, 2) }}</td>
                                                <td>
                                                    <span class="{{ $room->is_full ? 'text-danger' : 'text-success' }} fw-semibold">
                                                        {{ $room->occupancy }} / {{ $room->capacity }}
                                                    </span>
                                                </td>
                                                <td>{{ $room->capacity }}</td>
                                                <td>
                                                    {{-- Driven by capacity vs. occupancy, not a manual choice. --}}
                                                    <span class="badge bg-{{ $room->is_full ? 'danger' : 'success' }}">
                                                        {{ $room->is_full ? 'Occupied' : 'Available' }}
                                                    </span>
                                                    @if(!$room->is_full && $room->available_slots > 0)
                                                        <div class="small text-muted">{{ $room->available_slots }} slot(s) left</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($room->tenants->isNotEmpty())
                                                        {{ $room->tenants->pluck('name')->join(', ') }}
                                                    @else
                                                        <span class="text-muted">&mdash;</span>
                                                    @endif
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-primary js-edit-room"
                                                            data-id="{{ $room->id }}"
                                                            data-room-number="{{ $room->room_number }}"
                                                            data-room-type="{{ $room->room_type }}"
                                                            data-price="{{ $room->price }}"
                                                            data-capacity="{{ $room->capacity }}"
                                                            data-occupancy="{{ $room->occupancy }}">
                                                        Edit
                                                    </button>

                                                    <form action="{{ route('admin.rooms.destroy', $room->id) }}" method="POST"
                                                          class="d-inline"
                                                          onsubmit="return confirm('Delete room {{ $room->room_number }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">No rooms added yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="editRoomForm" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="editing_room_id" id="editing_room_id">
                <div class="mb-3">
                    <label class="form-label">Room Number</label>
                    <input type="text" name="room_number" id="edit_room_number" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Room Type</label>
                    <select name="room_type" id="edit_room_type" class="form-select" required>
                        @foreach(['single', 'double', 'deluxe', 'standard'] as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Price (&#8369;)</label>
                    <input type="number" step="0.01" min="0" name="price" id="edit_price" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Capacity</label>
                    <input type="number" min="1" max="255" step="1" name="capacity" id="edit_capacity" class="form-control" required>
                    <div class="form-text" id="edit_occupancy_hint"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Room</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script data-base-url="{{ route('admin.rooms.update', ['room' => '__ID__']) }}">
    // One modal reused for every row; the room's primary key drives the PUT URL.
    const editModal = new bootstrap.Modal(document.getElementById('editRoomModal'));
    const editForm = document.getElementById('editRoomForm');
    const baseUrl = document.currentScript.dataset.baseUrl;

    document.querySelectorAll('.js-edit-room').forEach(function (button) {
        button.addEventListener('click', function () {
            const d = this.dataset;
            editForm.action = baseUrl.replace('__ID__', d.id);
            document.getElementById('editing_room_id').value = d.id;
            document.getElementById('edit_room_number').value = d.roomNumber;
            document.getElementById('edit_room_type').value = d.roomType;
            document.getElementById('edit_price').value = d.price;

            const capacity = document.getElementById('edit_capacity');
            capacity.min = d.occupancy > 0 ? d.occupancy : 1;
            capacity.value = d.capacity;

            document.getElementById('edit_occupancy_hint').textContent =
                d.occupancy > 0
                    ? d.occupancy + ' tenant(s) currently assigned, so capacity cannot go below ' + d.occupancy + '.'
                    : 'Status is calculated automatically from capacity and assigned tenants.';

            editModal.show();
        });
    });
</script>
@endpush
