<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('tenant')->latest('payment_date')->get();
        $tenants = Tenant::orderBy('name')->get();

        return view('admin.payments.index', compact('payments', 'tenants'));
    }

    public function store(Request $request)
    {
        Payment::create($this->validated($request));

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    public function update(Request $request, Payment $payment)
    {
        $payment->update($this->validated($request));

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return redirect()
            ->route('admin.payments.index')
            ->with('success', 'Payment deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tenant_id'        => ['required', 'integer', 'exists:tenants,id'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_date'     => ['required', 'date'],
            'status'           => ['required', Rule::in(Payment::STATUSES)],
            'reference_number' => ['nullable', 'string', 'max:40'],
        ], [
            'tenant_id.required' => 'Please choose which tenant this payment belongs to.',
        ]);
    }
}
