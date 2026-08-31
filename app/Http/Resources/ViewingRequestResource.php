<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViewingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'property' => ['id' => $this->property->id, 'name' => $this->property->name],
            'preferred_date' => $this->preferred_date->toDateString(),
            'preferred_time' => $this->preferred_time,
            'message' => $this->message,
            'status' => $this->status,
        ];
    }
}
