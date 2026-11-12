@extends('admin.layout')

@section('title', 'Announcements')
@section('heading', 'Announcements Management')
@section('aside', $announcements->count() . ' post(s)')

@section('content')
<div class="row">
    <!-- Post Announcement Form -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-3">Post Announcement</h5>
                <form action="{{ route('admin.announcements.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" maxlength="150"value="{{ old('title') }}" placeholder="e.g. Water interruption" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="content" class="form-control" rows="5" maxlength="5000" required>{{ old('content') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Publish Date &amp; Time</label>
                        <input type="datetime-local" name="published_at" class="form-control"value="{{ old('published_at', now()->format('Y-m-d\TH:i')) }}">
                        <div class="form-text">Shown to tenants as the posting time.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Publish</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Announcements Table -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-3">Published Announcements</h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Published</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($announcements as $announcement)
                                <tr>
                                    <td class="text-muted">{{ $announcement->id }}</td>
                                    <td><strong>{{ $announcement->title }}</strong></td>
                                    <td class="small">{{ Str::limit($announcement->content, 90) }}</td>
                                    <td class="small">
                                        {{ ($announcement->published_at ?? $announcement->created_at)?->format('M d, Y h:i A') }}
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary js-edit-announcement"
                                                data-id="{{ $announcement->id }}"
                                                data-title="{{ $announcement->title }}"
                                                data-content="{{ $announcement->content }}"
                                                data-published="{{ ($announcement->published_at ?? $announcement->created_at)?->format('Y-m-d\TH:i') }}">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.announcements.destroy', $announcement->id) }}" method="POST"class="d-inline" onsubmit="return confirm('Delete this announcement?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No announcements posted yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="editForm" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" id="edit_title" class="form-control" maxlength="150" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="content" id="edit_content" class="form-control" rows="5" maxlength="5000" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Publish Date &amp; Time</label>
                    <input type="datetime-local" name="published_at" id="edit_published" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Announcement</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));
    const editForm = document.getElementById('editForm');
    const baseUrl = "{{ route('admin.announcements.update', ['announcement' => '__ID__']) }}";

    document.querySelectorAll('.js-edit-announcement').forEach(function (button) {
        button.addEventListener('click', function () {
            const d = this.dataset;
            editForm.action = baseUrl.replace('__ID__', d.id);
            document.getElementById('edit_title').value = d.title;
            document.getElementById('edit_content').value = d.content;
            document.getElementById('edit_published').value = d.published || '';
            editModal.show();
        });
    });
</script>
@endpush
