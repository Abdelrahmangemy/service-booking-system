<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'day_of_week' => $this->day_of_week,
            'date' => $this->date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'timezone' => $this->timezone,
            'provider' => new UserResource($this->whenLoaded('provider')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
