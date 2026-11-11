<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\OnlinePaymentService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class OnlinePaymentController extends Controller
{
    public function __construct(protected OnlinePaymentService $online)
    {
    }

    public function checkout(Payment $payment): RedirectResponse
    {
        // Tenant's own payment, and still pending.
        $this->authorize('submit', $payment);

        try {
            $url = $this->online->startCheckout(
                $payment,
                route('tenant.payments.show', [$payment, 'checkout' => 'success']),
                route('tenant.payments.show', [$payment, 'checkout' => 'cancelled']),
            );
        } catch (RequestException|RuntimeException $e) {
            report($e);

            return back()->withErrors(['online' => 'We could not start the online payment. Please try again, or submit a manual payment instead.']);
        }

        return redirect()->away($url);
    }
}
