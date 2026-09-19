<?php

namespace App\Http\Resources;

use App\Models\Address;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Address */
class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'country' => $this->country,
            'area' => $this->area,
            'city' => $this->city,
            'street' => $this->street,
            'number' => $this->number,
            'state' => $this->state,
            'cep' => $this->cep,
            'complement' => $this->complement,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'geocode_status' => $this->geocode_status,
            'geocode_error' => $this->geocode_error,
            'geocoded_at' => $this->geocoded_at,
            'full_address' => $this->full_address,
        ];
    }
}
