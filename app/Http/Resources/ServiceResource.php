<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'duration_minutes' => $this->duration_minutes,
            'price_cents' => $this->price_cents,
            'price_formatted' => '$' . number_format($this->price_cents / 100, 2),
            'is_published' => $this->is_published,
            'provider' => new UserResource($this->whenLoaded('provider')),
            'bookings_count' => $this->whenCounted('bookings'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
