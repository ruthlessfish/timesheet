<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'hourly_rate' => $this->hourly_rate,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'total_hours' => $this->total_hours,
            'total_amount' => $this->total_amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'client' => new ClientResource($this->whenLoaded('client')),
            'time_entries' => TimeEntryResource::collection($this->whenLoaded('timeEntries')),
        ];
    }
}
