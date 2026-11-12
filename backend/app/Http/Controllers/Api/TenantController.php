<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenantController extends Controller
{
    /**
     * The signed-in tenant's own profile, with their assigned room.
     */
    public function me(Request $request)
    {
        // The dashboard draws a bed-occupancy meter for the assigned room, and
        // occupancy is a derived accessor, so ask for it on this instance only.
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return response()->json(['message' => 'No tenant profile linked to this account.'], 404);
        }

        $tenant->load(['room', 'payments', 'checkIns.room']);
        $tenant->room?->append(['occupancy', 'available_slots', 'is_full']);

        return response()->json($tenant, 200);
    }

    /**
     * Every registered tenant, so residents can see who else is in the house.
     * Anyone hitting this route still needs a valid bearer token.
     */
    public function index(Request $request)
    {
        return response()->json(
            Tenant::with('room')->orderBy('name')->get(),
            200
        );
    }

    public function show(Request $request, Tenant $tenant)
    {
        return response()->json($tenant->load(['room', 'payments', 'checkIns.room']), 200);
    }

    /**
     * A tenant may update their own contact details. Room reassignment stays
     * on the admin dashboard because it is capacity-gated there.
     */
    public function update(Request $request, Tenant $tenant)
    {
        if ($request->user()->id !== $tenant->user_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Not allowed to edit this record.'], 403);
        }

        $validated = $request->validate([
            'name'  => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:120'],
            'phone' => ['sometimes', 'string', 'max:30'],
        ]);

        $tenant->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'tenant'  => $tenant->load('room'),
        ], 200);
    }

    /**
     * Admin only: register a tenant over the API. If a users row already owns
     * this email, the new tenant record is linked to that account instead of
     * leaving an orphan nobody can sign in with.
     */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $user = User::where('email', $data['email'])->first();

        if ($user && Tenant::where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'That account already has a tenant record. Edit it instead of adding a duplicate.',
            ], 409);
        }

        $tenant = Tenant::create($data + ['user_id' => $user?->id]);

        return response()->json([
            'message' => "Tenant {$tenant->name} registered successfully.",
            'tenant'  => $tenant->load('room'),
        ], 201);
    }

    /**
     * Admin only: remove a tenant record. The users row survives, and the
     * foreign keys on payments and check-ins are ON DELETE SET NULL, so the
     * history is kept with the owner column blanked rather than destroyed.
     */
    public function destroy(Tenant $tenant)
    {
        $name = $tenant->name;
        $tenant->delete();

        return response()->json([
            'message' => "Tenant {$name} deleted successfully.",
        ], 200);
    }

    /**
     * Same rules as the admin panel, including the capacity check that keeps a
     * room from taking more tenants than it has beds for.
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
