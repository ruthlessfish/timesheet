<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
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
            'description' => $this->description,
            'quantity' => $this->quantity,
            'rate' => $this->rate,
            'amount' => $this->amount,
            'time_entry_id' => $this->time_entry_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'time_entry' => new TimeEntryResource($this->whenLoaded('timeEntry')),
        ];
    }
}
