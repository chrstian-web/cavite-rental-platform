<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Services\OnlinePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Stand-in for PayMongo's hosted checkout page, so the whole online payment
 * flow can be tested without an account or real money. Disabled unless
 * PAYMENT_GATEWAY=fake AND the app is running locally / in tests.
 */
class FakeCheckoutController extends Controller
{
    public function __construct(protected OnlinePaymentService $online)
    {
    }

    public function show(string $checkout): View
    {
        $payment = $this->findPayment($checkout);
        $payment->load('contract.property');

        return view('tenant.payments.fake-checkout', compact('payment', 'checkout'));
    }

    public function pay(string $checkout): RedirectResponse
    {
        $payment = $this->findPayment($checkout);

        PaymentEvent::create([
            'payment_id' => $payment->id,
            'event' => 'fake_payment_succeeded',
            'source' => 'fake',
            'metadata' => ['checkout_id' => $checkout],
        ]);

        $this->online->markPaid($payment, 'fake', 'fake_pay_'.$payment->id, 'gcash');

        return redirect()
            ->route('tenant.payments.show', $payment)
            ->with('status', 'Test payment successful. No real money was charged.');
    }

    public function cancel(string $checkout): RedirectResponse
    {
        $payment = $this->findPayment($checkout);

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
