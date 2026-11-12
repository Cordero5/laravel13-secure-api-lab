<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CheckInController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return response()->json(['data' => []], 200);
        }

        return response()->json(
            CheckIn::with('room:id,room_number,room_type')
                ->where('tenant_id', $tenant->id)
                ->latest('check_in_date')
                ->get(),
            200
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'      => ['required', 'exists:tenants,id'],
            'room_id'        => ['required', 'exists:rooms,id'],
            'check_in_date'  => ['required', 'date'],
            'check_out_date' => ['nullable', 'date', 'after_or_equal:check_in_date'],
            'status'         => ['required', Rule::in(CheckIn::STATUSES)],
        ]);

        $checkIn = CheckIn::create($validated);

        return response()->json([
            'message'  => 'Check-in record created successfully',
            'check_in' => $checkIn->load(['tenant:id,name', 'room:id,room_number']),
        ], 201);
    }

    public function show(Request $request, CheckIn $checkIn)
    {
        if ($request->user()->role !== 'admin' && $request->user()->tenant?->id !== $checkIn->tenant_id) {
            return response()->json(['message' => 'Not allowed to view this record.'], 403);
        }

        return response()->json($checkIn->load(['tenant:id,name', 'room:id,room_number,room_type']), 200);
    }

    public function update(Request $request, CheckIn $checkIn)
    {
        $validated = $request->validate([
            'tenant_id'      => ['sometimes', 'exists:tenants,id'],
            'room_id'        => ['sometimes', 'exists:rooms,id'],
            'check_in_date'  => ['sometimes', 'date'],
            'check_out_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:check_in_date'],
            'status'         => ['sometimes', Rule::in(CheckIn::STATUSES)],
        ]);

        $checkIn->update($validated);

        return response()->json([
            'message'  => 'Check-in record updated successfully',
            'check_in' => $checkIn,
        ], 200);
    }

    public function destroy(CheckIn $checkIn)
    {
        $checkIn->delete();

        return response()->json(['message' => 'Check-in record deleted successfully'], 200);
    }
}
