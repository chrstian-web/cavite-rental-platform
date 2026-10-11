<?php

namespace App\Http\Controllers;

use App\Services\NotificationAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationAlertController extends Controller
{
    public function __construct(protected NotificationAlertService $alerts)
    {
    }

    /** Polled by the floating panel to pick up new notifications without a page reload. */
    public function index(Request $request): JsonResponse
    {
        return response()->json(['alerts' => $this->alerts->forUser($request->user())]);
    }

    public function dismiss(Request $request, string $id): JsonResponse
    {
        $request->user()->unreadNotifications()->where('id', $id)->first()?->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function dismissAll(Request $request): JsonResponse
    {
        $this->alerts->dismissAll($request->user());

        return response()->json(['ok' => true]);
    }
}
