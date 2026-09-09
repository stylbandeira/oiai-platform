<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ClientListResource extends JsonResource
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
            'optimized' => $this->optimized,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'favorite' => boolval($this->favorite),
            'status' => $this->status,
            'total' => floatval($this->total),
            'created_at' => $this->created_at,
            'products' => $this->whenLoaded('listProducts', function () {
                return ListProductResource::collection($this->listProducts);
            }),
            'companyId' => $this->whenLoaded('listProducts.companyProduct.company', function () {
                return $this->listProducts->company->id;
            }),
            'productsQuantity' => $this->whenLoaded('products', $this->products()->count()),
        ];
    }
}
