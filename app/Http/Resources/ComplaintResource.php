<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'plaque' => $this->vehicle_plaque,
            'transport_line' => $this->route_code,
            'notes' => $this->notes,
            'photo' => $this->image,
            'state' => $this->state,
            'created_at' => (new Carbon($this->created_at))->format('d/m/Y H:i')
        ];
    }
}
