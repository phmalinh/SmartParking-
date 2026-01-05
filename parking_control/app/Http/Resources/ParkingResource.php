<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParkingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);
        return [
            'id' => $this->id,
            'car_owner' => $this->car_owner,
            'plate_number' => $this->plate_number,
            'action' => $this->action,
            'createdAt' => $this->when(isset($this->created_at), Carbon::parse($this->created_at)->format('Y-m-d H:i:s')),
            'updatedAt' => $this->when(isset($this->updated_at), Carbon::parse($this->updated_at)->format('Y-m-d H:i:s'))
        ];
    }
}