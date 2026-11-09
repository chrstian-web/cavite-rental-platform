<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\OnlinePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Clearly labeled local demo stand-in for PayMongo's hosted checkout page.
 * It records no real payment and is disabled in production.
 */
class FakeCheckoutController extends Controller
{
    public function __construct(protected OnlinePaymentService $online)
    {
    }

    public function show(string $checkout): View
    {
        $payment = $this->findPayment($checkout);
        $payment->load(['contract.property', 'application.property']);

        return view('tenant.payments.fake-checkout', compact('payment', 'checkout'));
    }

    public function pay(Request $request, string $checkout): RedirectResponse
    {
        $payment = $this->findPayment($checkout);
        $data = $request->validate([
            'payment_method' => ['required', 'in:gcash,bank_transfer'],
        ]);

        PaymentEvent::create([
            'payment_id' => $payment->id,
            'event' => 'fake_payment_succeeded',
            'source' => 'fake',
            'metadata' => ['checkout_id' => $checkout, 'demo_method' => $data['payment_method']],
        ]);

        $this->online->markPaid($payment, 'fake', 'fake_pay_'.$payment->id, $data['payment_method']);

        if ($payment->rental_application_id) {
            return redirect()
                ->route('tenant.applications.show', $payment->rental_application_id)
                ->with('status', 'Reservation-fee payment recorded using '.str($data['payment_method'])->replace('_', ' ')->title().'.');
        }

        return redirect()
            ->to(route('tenant.payments.show', $payment).'#payment-details')
            ->with('status', 'Payment recorded using '.str($data['payment_method'])->replace('_', ' ')->title().'. Add your reference number and screenshot below.');
    }

    public function cancel(string $checkout): RedirectResponse
    {
        $payment = $this->findPayment($checkout);

        if ($payment->rental_application_id) {
            return redirect()->route('tenant.applications.show', [$payment->rental_application_id, 'checkout' => 'cancelled']);
        }

        return redirect()->route('tenant.payments.show', [$payment, 'checkout' => 'cancelled']);
    }

    protected function findPayment(string $checkout): Payment
    {
        abort_unless($this->online->fakeAllowed(), 404);

        $payment = Payment::where('gateway', 'fake')->where('gateway_checkout_id', $checkout)->firstOrFail();

        abort_unless($payment->user_id === auth()->id(), 403);

        return $payment;
    }
}
