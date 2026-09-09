<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'cnpj' => $this->cnpj,
            'img' => $this->img_url,
            'website' => $this->website,
            'status' => $this->status,
            'phone' => $this->phone,
            'description' => $this->description,
            'raw_address' => $this->raw_address,
            'geocode_status' => $this->geocode_status,
            'address' => $this->whenLoaded('address', new AddressResource($this->address)),

            'total_products' => $this->whenLoaded('products', $this->products->count()),
            'ownership_status' => $this->whenLoaded(
                'ownerRelationship',
                fn() => $this->ownerRelationship?->status
            ),
        ];
    }
}
