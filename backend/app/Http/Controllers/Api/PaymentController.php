<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        // A tenant only ever sees their own ledger.
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return response()->json(['data' => []], 200);
        }

        return response()->json(
            Payment::with('tenant:id,name')->where('tenant_id', $tenant->id)->latest('payment_date')->get(),
            200
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'        => ['required', 'exists:tenants,id'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_date'     => ['required', 'date'],
            'status'           => ['required', Rule::in(Payment::STATUSES)],
            'reference_number' => ['nullable', 'string', 'max:40'],
        ]);

        $payment = Payment::create($validated);

        return response()->json([
            'message' => 'Payment recorded successfully',
            'payment' => $payment->load('tenant:id,name'),
        ], 201);
    }

    public function show(Request $request, Payment $payment)
    {
        if ($request->user()->role !== 'admin' && $request->user()->tenant?->id !== $payment->tenant_id) {
            return response()->json(['message' => 'Not allowed to view this record.'], 403);
        }

        return response()->json($payment->load('tenant:id,name'), 200);
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'tenant_id'        => ['sometimes', 'exists:tenants,id'],
            'amount'           => ['sometimes', 'numeric', 'min:0.01'],
            'payment_date'     => ['sometimes', 'date'],
            'status'           => ['sometimes', Rule::in(Payment::STATUSES)],
            'reference_number' => ['sometimes', 'nullable', 'string', 'max:40'],
        ]);

        $payment->update($validated);

        return response()->json([
            'message' => 'Payment updated successfully',
            'payment' => $payment,
        ], 200);
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully'], 200);
    }
}
