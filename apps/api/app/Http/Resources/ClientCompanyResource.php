<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Company;

/** @mixin Company */
class ClientCompanyResource extends JsonResource
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
            'email' => $this->email,
            'img' => $this->img,
            'website' => $this->website,
            'status' => $this->status,
            'phone' => $this->phone,
            'description' => $this->description,
            'raw_address' => $this->raw_address,
        ];
    }
}
