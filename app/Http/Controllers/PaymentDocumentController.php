<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PaymentDocumentController extends Controller
{
    /**
     * Private proof-of-payment download — the tenant who submitted it, the
     * property owner/manager, or a Super Admin only. Never a public URL,
     * matching VerificationDocumentController / ApplicationDocumentController.
     */
    public function download(Payment $payment): Response
    {
        $this->authorize('view', $payment);

        abort_unless($payment->proof_path, 404);
        abort_unless(Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path, $payment->proof_original_filename);
    }
}
