<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ItensList;
use App\Models\ListProducts;

/** @mixin ItensList */
class ClientListResource extends JsonResource
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
                /** @var ListProducts|null $listProduct */
                $listProduct = $this->listProducts->first();

                return $listProduct?->companyProduct?->company?->id;
            }),
            'productsQuantity' => $this->whenLoaded('products', $this->products()->count()),
        ];
    }
}
