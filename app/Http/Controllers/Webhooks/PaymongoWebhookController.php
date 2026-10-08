<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\OnlinePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymongoWebhookController extends Controller
{
    public function __invoke(Request $request, OnlinePaymentService $online): JsonResponse
    {
        // The fake gateway has no real signature check, so never accept webhooks while it is active.
        abort_unless($online->gatewayName() === 'paymongo', 404);

        $online->handlePaymongoWebhook($request->getContent(), $request->header('Paymongo-Signature'));

        return response()->json(['received' => true]);
    }
}
