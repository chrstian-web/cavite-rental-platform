<?php

namespace App\Http\Controllers;

use App\Services\PaymentAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentAlertController extends Controller
{
    public function __construct(protected PaymentAlertService $alerts)
    {
    }

    /** Polled by the floating panel to pick up new payments without a page reload. */
    public function index(Request $request): JsonResponse
    {
        abort_if($request->user()->isTenant(), 403);

        return response()->json(['alerts' => $this->alerts->forUser($request->user())]);
    }

    public function dismiss(Request $request, string $id): JsonResponse
    {
        abort_if($request->user()->isTenant(), 403);

        $request->user()->unreadNotifications()->where('id', $id)->first()?->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function dismissAll(Request $request): JsonResponse
    {
        abort_if($request->user()->isTenant(), 403);

        $this->alerts->dismissAll($request->user());

        return response()->json(['ok' => true]);
    }
}
