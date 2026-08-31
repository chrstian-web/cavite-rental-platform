<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property' => ['id' => $this->property->id, 'name' => $this->property->name],
            'category' => $this->category,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'images' => $this->images->map(fn ($img) => asset('storage/'.$img->path)),
            'submitted_at' => $this->created_at->toIso8601String(),
        ];
    }
}
