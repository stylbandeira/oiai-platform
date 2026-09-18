<?php

namespace App\Http\Resources;

use App\Models\ListProducts;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ListProducts */
class ListProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var ListProducts $resource */
        $resource = $this->resource;
        $productLoaded = $resource->relationLoaded('product');
        $unityLoaded = $productLoaded && $resource->product->relationLoaded('unity');
        $categoryLoaded = $productLoaded && $resource->product->relationLoaded('category');
        $companyProductLoaded = $resource->relationLoaded('companyProduct');

        $data = [
            'id' => $resource->product_id ?? $resource->product->id,
            'name' => $productLoaded ? $resource->product->name : null,
            'sku' => $productLoaded ? $resource->product->sku : null,
            'img' => $productLoaded ? $resource->product->img : null,
            'ean' => $productLoaded ? $resource->product->ean : null,
            'completed' => $resource->completed,
            'average_price' => $productLoaded ? (float) $resource->product->average_price : 0,
            'quantity' => $resource->quantity,
        ];

        if ($unityLoaded) {
            $data['unity'] = $resource->product->unity->abbreviation;
            $data['unity_id'] = $resource->product->unity->id;
            $data['unity_quantity'] = $resource->product->quantity;
        } else {
            $data['unity'] = null;
            $data['unity_id'] = null;
            $data['unity_quantity'] = null;
        }

        if ($categoryLoaded) {
            $data['category'] = $resource->product->category->name;
        } else {
            $data['category'] = null;
        }

        if ($companyProductLoaded && $resource->companyProduct) {
            $data['company_id'] = $resource->companyProduct->company_id;
            $data['company_name'] = $resource->companyProduct->company->name ?? null;
            $data['store_address'] = $resource->companyProduct->company->address ?? null;
        }

        return $data;
    }
}
