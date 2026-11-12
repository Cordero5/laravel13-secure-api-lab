<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CheckInController extends Controller
{
    public function index()
    {
        $checkIns = CheckIn::with(['tenant', 'room'])->latest('check_in_date')->get();
        $tenants = Tenant::with('room')->orderBy('name')->get();
        $rooms = Room::with('tenants')->orderBy('room_number')->get();

        return view('admin.checkins.index', compact('checkIns', 'tenants', 'rooms'));
    }

    /**
     * Check in: record the stay and assign the chosen room to the tenant,
     * which is what makes the room count as occupied.
     */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $entry = CheckIn::create($data + ['status' => 'checked_in']);

        $entry->tenant->update(['room_id' => $entry->room_id]);

        return redirect()
            ->route('admin.checkins.index')
            ->with('success', "{$entry->tenant->name} checked in and assigned to Room {$entry->room->room_number}.");
    }

    /**
     * Edit an existing check-in record and follow the room change through to
     * the tenant assignment.
     */
    public function update(Request $request, CheckIn $checkIn)
    {
        $data = $this->validated($request, $checkIn);

        $checkIn->update($data);

        if ($checkIn->status === CheckIn::STATUS_ACTIVE) {
            $checkIn->tenant->update(['room_id' => $checkIn->room_id]);
        }

        return redirect()
            ->route('admin.checkins.index')
            ->with('success', 'Check-in record updated successfully.');
    }

    /**
     * Stamp the check-out time, close the record and free the room.
     */
    public function checkout(Request $request, CheckIn $checkIn)
    {
        $data = $request->validate([
            'check_out_date' => ['required', 'date', 'after_or_equal:' . ($checkIn->check_in_date?->format('Y-m-d\TH:i') ?? 'now')],
        ]);

        $checkIn->update([
            'check_out_date' => $data['check_out_date'],
            'status' => CheckIn::STATUS_CHECKED_OUT,
        ]);

        $this->releaseRoom($checkIn);

        return redirect()
            ->route('admin.checkins.index')
            ->with('success', 'Tenant checked out and the room is now free.');
    }

    public function destroy(CheckIn $checkIn)
    {
        $this->releaseRoom($checkIn);

        $checkIn->delete();

        return redirect()
            ->route('admin.checkins.index')
            ->with('success', 'Check-in record deleted successfully.');
    }

    /**
     * Drop the tenant's room assignment once they have no open check-in left,
     * so a closed stay stops holding the room.
     */
    private function releaseRoom(CheckIn $checkIn): void
    {
        $tenant = $checkIn->tenant;

        if (!$tenant || $tenant->room_id !== $checkIn->room_id) {
            return;
        }

        if ($tenant->checkIns()->where('status', CheckIn::STATUS_ACTIVE)->whereKeyNot($checkIn->getKey())->exists()) {
            return;
        }

        $tenant->update(['room_id' => null]);
    }

    /**
     * Validate the form and enforce that the room still has a free slot.
     */
    private function validated(Request $request, ?CheckIn $editing = null): array
    {
        $validator = Validator::make(
            $request->all(),
            [
                'tenant_id'     => ['required', 'integer', 'exists:tenants,id'],
                'room_id'       => ['required', 'integer', 'exists:rooms,id'],
                'check_in_date' => ['required', 'date'],
            ],
            [
                'tenant_id.required' => 'Please choose the tenant.',
                'room_id.required'   => 'Please choose the room.',
            ]
        );

        $validator->after(function ($validator) use ($request, $editing) {
            $room = Room::find((int) $request->input('room_id'));
            $tenantId = (int) $request->input('tenant_id');

            if (!$room) {
                return;
            }

            if (!$room->hasFreeSlot($tenantId)) {
                $validator->errors()->add(
                    'room_id',
                    "Room {$room->room_number} is full (capacity {$room->capacity}). Free a slot or choose another room."
                );
            }

            // One open stay per tenant, otherwise two records fight over the
            // room assignment and the release logic cannot tell which wins.
            $alreadyCheckedIn = Tenant::find($tenantId)
                ?->checkIns()
                ->where('status', CheckIn::STATUS_ACTIVE)
                ->when($editing, fn ($q) => $q->whereKeyNot($editing->getKey()))
                ->exists();

            if ($alreadyCheckedIn) {
                $validator->errors()->add(
                    'tenant_id',
                    'This tenant is already checked in. Check them out first or edit the existing record.'
                );
            }
        });

        return $validator->validate();
    }
}
