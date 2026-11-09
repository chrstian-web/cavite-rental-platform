<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\RentalApplication;
use App\Services\OnlinePaymentService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReservationFeeController extends Controller
{
    public function __construct(protected OnlinePaymentService $online)
    {
    }

    public function pay(Request $request, RentalApplication $application): RedirectResponse
    {
        $this->authorize('view', $application);
        $request->validate(['terms_accepted' => ['accepted']]);

        abort_unless($application->status === 'approved', 422, 'The application must be approved before a reservation fee can be paid.');
        abort_unless($application->reservation_offer_sent_at && (float) $application->reservation_fee > 0 && filled($application->reservation_terms), 422, 'The owner has not sent a complete reservation-fee offer.');

        $payment = DB::transaction(function () use ($application) {
            $lockedApplication = RentalApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedApplication->status === 'approved', 422, 'The application is no longer approved.');
            abort_unless($lockedApplication->reservation_offer_sent_at && (float) $lockedApplication->reservation_fee > 0 && filled($lockedApplication->reservation_terms), 422, 'The owner has not sent a complete reservation-fee offer.');

            $termsHash = hash('sha256', $lockedApplication->reservation_terms);
            $lockedApplication->update([
                'reservation_terms_accepted_at' => now(),
                'reservation_terms_accepted_hash' => $termsHash,
            ]);

            $payment = Payment::where('rental_application_id', $lockedApplication->id)
                ->where('payment_type', Payment::TYPE_RESERVATION_FEE)
                ->lockForUpdate()
                ->first();

            if ($payment?->status === 'paid') {
                return $payment;
            }

            if (! $payment) {
                $payment = Payment::create([
                    'rental_application_id' => $lockedApplication->id,
                    'user_id' => $lockedApplication->user_id,
                    'payment_type' => Payment::TYPE_RESERVATION_FEE,
                    'amount' => $lockedApplication->reservation_fee,
                    'currency' => 'PHP',
                    'due_date' => today(),
                    'status' => 'pending',
                    'payment_source' => 'online',
                    'notes' => 'Reservation fee for rental application #'.$lockedApplication->id,
                ]);
            }

            return $payment;
        });

        if ($payment->status === 'paid') {
            return redirect()->route('tenant.applications.show', $application)->with('status', 'The reservation fee has already been paid.');
        }

        try {
            $url = $this->online->startCheckout(
                $payment,
                route('tenant.applications.show', [$application, 'checkout' => 'success']),
                route('tenant.applications.show', [$application, 'checkout' => 'cancelled']),
            );
        } catch (RequestException|RuntimeException $e) {
            report($e);

            return back()->withErrors(['online' => 'We could not start the reservation-fee payment. Please try again.']);
        }

        return redirect()->away($url);
    }
}
