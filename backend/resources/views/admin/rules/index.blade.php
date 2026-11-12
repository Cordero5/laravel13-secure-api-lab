@extends('admin.layout')

@section('title', 'House Rules')
@section('heading', 'House Rules Management')
@section('aside', $rules->count() . ' rule(s) &middot; ' . $rules->where('is_active', true)->count() . ' active')

@section('content')
<div class="row">
    <!-- Add Rule Form -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-3">Add House Rule</h5>
                <form action="{{ route('admin.rules.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" maxlength="150"value="{{ old('title') }}" placeholder="e.g. Quiet hours" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" maxlength="5000" required>{{ old('description') }}</textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active"@checked(old('is_active', true))>
                        <label class="form-check-label" for="is_active">Visible to tenants</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Rule</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Rules Table -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-3">Posted Rules</h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Visibility</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rules as $rule)
                                <tr>
                                    <td class="text-muted">{{ $rule->id }}</td>
                                    <td><strong>{{ $rule->title }}</strong></td>
                                    <td class="small">{{ Str::limit($rule->description, 90) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $rule->is_active ? 'success' : 'secondary' }}">
                                            {{ $rule->is_active ? 'Active' : 'Hidden' }}
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary js-edit-rule"
                                                data-id="{{ $rule->id }}"
                                                data-title="{{ $rule->title }}"
                                                data-description="{{ $rule->description }}"
                                                data-active="{{ $rule->is_active ? 1 : 0 }}">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.rules.destroy', $rule->id) }}" method="POST"class="d-inline" onsubmit="return confirm('Delete this rule?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No house rules posted yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Rule Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
          <form method="POST" id="editForm" class="modal-content"
              data-update-url="{{ route('admin.rules.update', ['houseRule' => '__ID__']) }}">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit House Rule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" id="edit_title" class="form-control" maxlength="150" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="4" maxlength="5000" required></textarea>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" name="is_active" id="edit_is_active">
                    <label class="form-check-label" for="edit_is_active">Visible to tenants</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Rule</button>
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

    document.querySelectorAll('.js-edit-rule').forEach(function (button) {
        button.addEventListener('click', function () {
            const d = this.dataset;
            editForm.action = baseUrl.replace('__ID__', d.id);
            document.getElementById('edit_title').value = d.title;
            document.getElementById('edit_description').value = d.description;
            document.getElementById('edit_is_active').checked = d.active === '1';
            editModal.show();
        });
    });
</script>
@endpush
