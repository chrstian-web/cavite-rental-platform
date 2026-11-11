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

    /**
     * "Complete payment" on the checkout page. GCash is the only method, so it always
     * goes on to the GCash screen, where the owner's number is shown.
     */
    public function continue(Request $request, string $checkout): RedirectResponse
    {
        $this->findPayment($checkout);
        $request->validate([
            'payment_method' => ['required', 'in:gcash'],
        ]);

        return redirect()->route('fake-checkout.gcash', $checkout);
    }

    public function gcash(string $checkout): View|RedirectResponse
    {
        $payment = $this->findPayment($checkout);
        $payment->load(['contract.property', 'application.property']);
        $property = $payment->application?->property ?? $payment->contract?->property;
        $recipientNumber = $property?->gcash_number ?: $property?->contact_number;

        if (blank($recipientNumber)) {
            return redirect()->route('fake-checkout.show', $checkout)
                ->withErrors(['payment_method' => 'The owner has not added a GCash number yet. Please contact the owner.']);
        }

        return view('tenant.payments.fake-gcash', [
            'payment' => $payment,
            'property' => $property,
            'checkout' => $checkout,
            'recipientNumber' => $recipientNumber,
        ]);
    }

    public function pay(Request $request, string $checkout): RedirectResponse
    {
        $payment = $this->findPayment($checkout);
        $data = $request->validate([
            'payment_method' => ['required', 'in:gcash'],
            'amount' => ['nullable', 'numeric'],
        ]);

        // The GCash screen asks the renter to type the amount they are sending; it must match what is due.
        if (isset($data['amount']) && abs((float) $data['amount'] - (float) $payment->amount) > 0.009) {
            return back()->withInput()->withErrors(['amount' => 'Enter the exact amount due: ₱'.number_format((float) $payment->amount, 2).'.']);
        }

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
            ->route('tenant.payments.show', $payment)
            ->with('status', 'Payment recorded using '.str($data['payment_method'])->replace('_', ' ')->title().'.');
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
