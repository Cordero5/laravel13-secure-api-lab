<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    /**
     * Room catalogue with live occupancy, so the app can show free beds
     * without recomputing the capacity rule on the device.
     */
    public function index()
    {
        $rooms = Room::withCount('tenants')->orderBy('room_number')->get()->map(function (Room $room) {
            return $this->present($room);
        });

        return response()->json($rooms, 200);
    }

    public function show(Room $room)
    {
        $room->load('tenants.user:id,name,email');

        return response()->json(
            $this->present($room)->put('tenants', $room->tenants->map(fn ($tenant) => [
                'id'    => $tenant->id,
                'name'  => $tenant->name,
                'email' => $tenant->email,
            ])),
            200
        );
    }

    /**
     * Create a room. Reached only through the role:admin group in routes/api.php,
     * which is why no per-user check is repeated here. Status is derived from
     * capacity, exactly as on the dashboard, never typed in by the caller.
     */
    public function store(Request $request)
    {
        $room = new Room($this->validated($request));
        $room->status = $room->is_full ? 'occupied' : 'available';
        $room->save();

        return response()->json([
            'message' => "Room {$room->room_number} added successfully.",
            'room'    => $this->present($room),
        ], 201);
    }

    /**
     * Edit a room. Capacity may not be shrunk below the tenants already living
     * there, the same rule the dashboard enforces.
     */
    public function update(Request $request, Room $room)
    {
        $room->fill($this->validated($request, $room->tenants()->count()));
        $room->syncOccupancyStatus();
        $room->save();

        return response()->json([
            'message' => "Room {$room->room_number} updated successfully.",
            'room'    => $this->present($room),
        ], 200);
    }

    /**
     * Delete a room, refused while tenants are still assigned to it. Returns 409
     * so a client can tell "blocked by a rule" apart from "not allowed at all".
     */
    public function destroy(Room $room)
    {
        if ($room->tenants()->exists()) {
            return response()->json([
                'message' => "Room {$room->room_number} still has tenants assigned. Unassign them first.",
            ], 409);
        }

        $roomNumber = $room->room_number;
        $room->delete();

        return response()->json(['message' => "Room {$roomNumber} deleted successfully."], 200);
    }

    /**
     * Shared validation, mirroring Admin\RoomController so both surfaces apply
     * one set of rules.
     */
    private function validated(Request $request, ?int $currentOccupancy = null): array
    {
        $rules = [
            'room_number' => ['required', 'string', 'max:20', Rule::unique('rooms', 'room_number')->ignore($request->route('room')?->id)],
            'room_type'   => ['required', 'string', 'max:50'],
            'price'       => ['required', 'numeric', 'min:0'],
            'capacity'    => ['required', 'integer', 'min:1', 'max:255'],
        ];

        if ($currentOccupancy !== null && $currentOccupancy > 0) {
            $rules['capacity'][] = 'gte:' . $currentOccupancy;
        }

        return $request->validate($rules, [
            'room_number.unique' => 'That room number already exists.',
            'capacity.gte'       => "This room already has {$currentOccupancy} tenant(s), so capacity cannot go below that.",
            'capacity.min'       => 'A room must have a capacity of at least 1.',
        ]);
    }

    /**
     * Room fields plus the derived occupancy values.
     */
    private function present(Room $room): \Illuminate\Support\Collection
    {
        return collect($room->only(['id', 'room_number', 'room_type', 'price', 'capacity', 'status']))
            ->put('occupancy', $room->occupancy)
            ->put('available_slots', $room->available_slots)
            ->put('is_full', $room->is_full);
    }
}
