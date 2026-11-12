<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Announcements already due, newest first. A future published_at is
     * treated as scheduled and stays hidden from the mobile app.
     */
    public function index()
    {
        return response()->json(
            Announcement::whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->get(),
            200
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:150'],
            'content'      => ['required', 'string', 'max:5000'],
            'published_at' => ['nullable', 'date'],
        ]);

        $announcement = Announcement::create($validated);

        return response()->json([
            'message'      => 'Announcement created successfully',
            'announcement' => $announcement,
        ], 201);
    }

    public function show(Announcement $announcement)
    {
        return response()->json($announcement, 200);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title'        => ['sometimes', 'string', 'max:150'],
            'content'      => ['sometimes', 'string', 'max:5000'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $announcement->update($validated);

        return response()->json([
            'message'      => 'Announcement updated successfully',
            'announcement' => $announcement,
        ], 200);
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted successfully'], 200);
    }
}
