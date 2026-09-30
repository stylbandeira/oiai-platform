<?php

namespace App\Http\Resources;

use App\Models\Unity;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Unity */
class UnityResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->name,
            'abbreviation' => $this->abbreviation,
            'dimension' => $this->dimension,
            'convertion_factor' => $this->convertion_factor,
            'base_unity_id' => $this->base_unity_id,
        ];
    }
}
