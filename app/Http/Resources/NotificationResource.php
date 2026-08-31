<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->data['type'] ?? class_basename($this->type),
            'data' => $this->data,
            'read' => (bool) $this->read_at,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
