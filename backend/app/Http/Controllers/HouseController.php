<?php

namespace App\Http\Controllers;

use App\Models\Room;

class HouseController extends Controller
{
    public function rooms()
    {
        $rooms = Room::withCount('tenants')
            ->orderBy('room_number')
            ->get()
            ->map(static function (Room $room) {
                $capacity = max((int) $room->capacity, 1);
                $occupancy = (int) $room->tenants_count;

                return [
                    'room_number' => $room->room_number,
                    'room_type' => $room->room_type,
                    'price' => $room->price,
                    'capacity' => $capacity,
                    'occupancy' => $occupancy,
                    'available_slots' => max($capacity - $occupancy, 0),
                    'is_full' => $occupancy >= $capacity,
                ];
            });

        return view('house.rooms', compact('rooms'));
    }
}
