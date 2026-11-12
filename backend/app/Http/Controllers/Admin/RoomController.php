<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    /**
     * Read: list every room with its assigned tenants.
     */
    public function index()
    {
        $rooms = Room::with('tenants')->orderBy('room_number')->get();

        return view('admin.rooms.index', compact('rooms'));
    }

    /**
     * Create: store a new room. Status is derived from capacity, not typed in.
     */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $room = new Room($data);
        $room->status = $room->is_full ? 'occupied' : 'available';
        $room->save();

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', "Room {$room->room_number} added successfully.");
    }

    /**
     * Update: persist edits to an existing room, matched by its route-bound ID.
     */
    public function update(Request $request, Room $room)
    {
        $data = $this->validated(
            $request,
            $room->tenants()->count()
        );

        $room->fill($data);
        $room->syncOccupancyStatus();
        $room->save();

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', "Room {$room->room_number} updated successfully.");
    }

    /**
     * Delete: remove a room, refusing while tenants are still assigned to it.
     */
    public function destroy(Room $room)
    {
        if ($room->tenants()->exists()) {
            return redirect()
                ->route('admin.rooms.index')
                ->with('error', "Room {$room->room_number} still has tenants assigned. Unassign them first.");
        }

        $roomNumber = $room->room_number;
        $room->delete();

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', "Room {$roomNumber} deleted successfully.");
    }

    /**
     * Shared validation. When $currentOccupancy is supplied (an update), the new
     * capacity may not be lower than the number of tenants already living there.
     */
    private function validated(Request $request, ?int $currentOccupancy = null): array
    {
        $rules = [
            'room_number' => ['required', 'string', 'max:20', Rule::unique('rooms', 'room_number')->ignore($request->route('room')?->id)],
            'room_type'   => ['required', 'string', 'max:50'],
            'price'       => ['required', 'numeric', 'min:0'],
            'capacity'    => ['required', 'integer', 'min:1', 'max:255'],
        ];

        // Never let an edit shrink the room below the tenants already in it.
        if ($currentOccupancy !== null && $currentOccupancy > 0) {
            $rules['capacity'][] = 'gte:' . $currentOccupancy;
        }

        return $request->validate($rules, [
            'room_number.unique' => 'That room number already exists.',
            'capacity.gte'       => "This room already has {$currentOccupancy} tenant(s), so capacity cannot go below that.",
            'capacity.min'       => 'A room must have a capacity of at least 1.',
        ]);
    }
}
