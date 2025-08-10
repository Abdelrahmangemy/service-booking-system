<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service' => new ServiceResource($this->whenLoaded('service')),
            'provider' => new UserResource($this->whenLoaded('provider')),
            'customer' => new UserResource($this->whenLoaded('customer')),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,
            'duration_minutes' => $this->start_time->diffInMinutes($this->end_time),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
