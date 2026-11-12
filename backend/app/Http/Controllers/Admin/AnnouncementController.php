<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::latest('published_at')->latest('id')->get();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function store(Request $request, FcmService $fcm)
    {
        $data = $this->validated($request);

        // A blank date means "publish now", so tenants see it straight away.
        $data['published_at'] ??= now();

        $announcement = Announcement::create($data);

        $push = $fcm->broadcast($announcement->title, Str::limit($announcement->content, 180));

        $message = 'Announcement published successfully.';

        if ($push['status'] === 'attempted') {
            $message .= " Push sent to {$push['sent']} device(s).";
        } elseif ($push['status'] === 'skipped') {
            $message .= $push['reason'] === 'no_tokens'
                ? ' No tenant devices are registered for push yet.'
                : ' Push is not configured yet (see config/fcm.php).';
        } elseif ($push['status'] === 'failed') {
            $message .= ' The announcement saved, but the push failed - check the logs.';
        }

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', $message);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $announcement->update($this->validated($request));

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'        => ['required', 'string', 'max:150'],
            'content'      => ['required', 'string', 'max:5000'],
            'published_at' => ['nullable', 'date'],
        ]);
    }
}
