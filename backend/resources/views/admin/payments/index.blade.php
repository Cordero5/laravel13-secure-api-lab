@extends('admin.layout')

@section('title', 'Payments')
@section('heading', 'Payments Management')
@section('aside', $payments->count() . ' record(s) · Total paid ₱' . number_format($payments->where('status', 'paid')->sum('amount'), 2))

@section('content')
@php $statuses = \App\Models\Payment::STATUSES; @endphp
<div class="row">
    <!-- Record Payment Form -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-3">Record Payment</h5>
                <form action="{{ route('admin.payments.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Tenant</label>
                        <select name="tenant_id" class="form-select" required>
                            <option value="">Select tenant&hellip;</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}" @selected(old('tenant_id') == $tenant->id)>
                                    {{ $tenant->name ?: 'Unnamed #' . $tenant->id }}
                                    @if($tenant->room)&mdash; Room {{ $tenant->room->room_number }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (&#8369;)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control"
                               value="{{ old('amount') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control"
                               value="{{ old('payment_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference No. <span class="text-muted small">(optional)</span></label>
                        <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Payment</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-3">Payment Records</h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tenant</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Reference</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td class="text-muted">{{ $payment->id }}</td>
                                    <td><strong>{{ $payment->tenant->name ?? 'Unlinked' }}</strong></td>
                                    <td>&#8369;{{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '&mdash;' }}</td>
                                    <td>
                                        @php
                                            $tone = ['paid' => 'success', 'pending' => 'warning', 'overdue' => 'danger', 'cancelled' => 'secondary'][$payment->status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $tone }}">{{ ucfirst($payment->status) }}</span>
                                    </td>
                                    <td class="small text-muted">{{ $payment->reference_number ?: '&mdash;' }}</td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary js-edit-payment"
                                                data-id="{{ $payment->id }}"
                                                data-tenant-id="{{ $payment->tenant_id }}"
                                                data-amount="{{ $payment->amount }}"
                                                data-payment-date="{{ $payment->payment_date ? $payment->payment_date->format('Y-m-d') : '' }}"
                                                data-status="{{ $payment->status }}"
                                                data-reference="{{ $payment->reference_number }}">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.payments.destroy', $payment->id) }}" method="POST"
                                              class="d-inline" onsubmit="return confirm('Delete this payment record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No payments recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
          <form method="POST" id="editForm" class="modal-content"
              data-update-url="{{ route('admin.payments.update', ['payment' => '__ID__']) }}">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Payment</h5>
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
                    <label class="form-label">Amount (&#8369;)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="edit_amount" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Date</label>
                    <input type="date" name="payment_date" id="edit_payment_date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" id="edit_status" class="form-select">
                        @foreach($statuses as $status)
                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reference No.</label>
                    <input type="text" name="reference_number" id="edit_reference" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Payment</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    const editForm = document.getElementById('editForm');
    const baseUrl = editForm.dataset.updateUrl;

    document.querySelectorAll('.js-edit-payment').forEach(function (button) {
        button.addEventListener('click', function () {
            const d = this.dataset;
            editForm.action = baseUrl.replace('__ID__', d.id);
            document.getElementById('edit_tenant_id').value = d.tenantId;
            document.getElementById('edit_amount').value = d.amount;
            document.getElementById('edit_payment_date').value = d.paymentDate;
            document.getElementById('edit_status').value = d.status;
            document.getElementById('edit_reference').value = d.reference || '';
            editModal.show();
        });
    });
</script>
@endpush
