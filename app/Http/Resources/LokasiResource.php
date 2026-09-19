<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LokasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'instansi_id' => $this->instansi_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius_meter' => $this->radius_meter,
        ];
    }
}
