<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\Request;

/**
 * Endpoints that exist to demonstrate role-based authorization. Each one
 * returns a short identifying message plus a small slice of real data, so a
 * Postman screenshot shows both the HTTP status and who was allowed in.
 *
 * The Expo app never calls these routes, which is why they can be guarded
 * without risking any screen on the phone.
 */
class AuthorizationController extends Controller
{
    /**
     * Admin-only view of the whole house.
     */
    public function overview(Request $request)
    {
        $rooms = Room::withCount('tenants')->get();

        return response()->json([
            'message' => 'Welcome, Administrator',
            'access'  => 'admin',
            'granted_to' => $request->user()->email,
            'house' => [
                'rooms' => $rooms->count(),
                'occupied_rooms' => $rooms->filter->is_full->count(),
                'tenants' => Tenant::count(),
                'collected' => (float) Payment::where('status', 'paid')->sum('amount'),
                'outstanding' => (float) Payment::whereIn('status', ['pending', 'overdue'])->sum('amount'),
            ],
        ]);
    }

    /**
     * Tenant-only view of the caller's own record.
     */
    public function summary(Request $request)
    {
        $tenant = $request->user()->tenant;

        return response()->json([
            'message' => 'Welcome, Tenant',
            'access'  => 'tenant',
            'granted_to' => $request->user()->email,
            'tenant' => $tenant ? [
                'name'  => $tenant->name,
                'room'  => $tenant->room?->room_number,
                'payments' => $tenant->payments()->count(),
            ] : null,
        ]);
    }

    /**
     * Shared resource: readable by both roles, the direct equivalent of the
     * laboratory's /students route guarded with role:admin,student.
     */
    public function rooms(Request $request)
    {
        return response()->json([
            'message' => 'Room list is open to staff and boarders',
            'access'  => $request->user()->role,
            // occupancy is a derived accessor, so ask for it explicitly on
            // this collection instead of appending it model-wide.
            'rooms' => Room::orderBy('room_number')->get()->append([
                'occupancy', 'available_slots', 'is_full',
            ])->makeHidden(['created_at', 'updated_at', 'deleted_at']),
        ]);
    }
}
