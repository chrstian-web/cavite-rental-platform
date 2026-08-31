<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class ApiController extends Controller
{
    /**
     * Every API response follows the same envelope so mobile clients
     * (Flutter / React Native / native Android / native iOS) can parse
     * responses with one shared model, regardless of endpoint:
     * { "success": true, "message": "...", "data": ..., "meta": {} }
     */
    protected function success(mixed $data = null, string $message = 'OK', int $status = 200, array $meta = []): JsonResponse
    {
        if ($data instanceof LengthAwarePaginator) {
            $meta = array_merge($meta, [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => (object) $meta,
        ], $status);
    }

    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => (object) ['errors' => $errors],
        ], $status);
    }
}
