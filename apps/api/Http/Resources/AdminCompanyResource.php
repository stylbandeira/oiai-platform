<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminCompanyResource extends JsonResource
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
            'img' => $this->img ? config('app.url') . $this->img_url : null,
            'website' => $this->website,
            'status' => $this->status,
            'phone' => $this->phone,
            'description' => $this->description,
            'raw_address' => $this->raw_address,
            'created_at' => $this->created_at->format('m/d/Y'),

            //Only for Admin
            'total_products' => $this->whenLoaded('products', $this->products->count()),
        ];
    }
}
