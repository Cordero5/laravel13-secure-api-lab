<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenantController extends Controller
{
    /**
     * Read: list tenants with their joined room, plus rooms selectable for assignment.
     */
    public function index()
    {
        $tenants = Tenant::with('room')->orderBy('name')->get();
        $rooms = Room::with('tenants')->orderBy('room_number')->get();

        return view('admin.tenants.index', compact('tenants', 'rooms'));
    }

    /**
     * Create: register a tenant and link them to a room by room ID.
     */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $tenant = Tenant::create($data);

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', "Tenant {$tenant->name} registered successfully.");
    }

    /**
     * Update: edit a tenant and re-point their room assignment by ID.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $data = $this->validated($request, $tenant);

        $tenant->update($data);

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', "Tenant {$tenant->name} updated successfully.");
    }

    /**
     * Delete: remove a tenant record.
     */
    public function destroy(Tenant $tenant)
    {
        $name = $tenant->name;
        $tenant->delete();

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', "Tenant {$name} deleted successfully.");
    }

    /**
     * Validate tenant input and enforce the room's capacity by ID.
     */
    private function validated(Request $request, ?Tenant $editing = null): array
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name'    => ['required', 'string', 'max:100'],
                'email'   => ['required', 'email', 'max:120'],
                'phone'   => ['required', 'string', 'max:30'],
                'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            ],
            [
                'name.required'  => 'Full name is required.',
                'email.required' => 'Email address is required.',
                'phone.required' => 'Contact number is required.',
            ]
        );

        $validator->after(function ($validator) use ($request, $editing) {
            $roomId = $request->input('room_id');

            if (!$roomId) {
                return;
            }

            $room = Room::find((int) $roomId);

            if (!$room) {
                return;
            }

            if (!$room->hasFreeSlot($editing?->id)) {
                $validator->errors()->add(
                    'room_id',
                    "Room {$room->room_number} is full (capacity {$room->capacity}). Free a slot or choose another room."
                );
            }
        });

        return $validator->validate();
    }
}
