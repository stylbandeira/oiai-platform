<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ListProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $productLoaded = $this->relationLoaded('product');
        $unityLoaded = $productLoaded && $this->product->relationLoaded('unity');
        $categoryLoaded = $productLoaded && $this->product->relationLoaded('category');
        $companyProductLoaded = $this->relationLoaded('companyProduct');

        $data = [
            'id' => $this->product_id ?? $this->product->id, // Use product_id se disponível
            'name' => $productLoaded ? $this->product->name : null,
            'sku' => $productLoaded ? $this->product->sku : null,
            'img' => $productLoaded ? $this->product->img : null,
            'ean' => $productLoaded ? $this->product->ean : null,
            'ean' => $this->ean,
            'completed' => $this->completed,
            'average_price' => $productLoaded ? floatval($this->product->average_price) : 0,
            'quantity' => $this->quantity,
        ];

        if ($unityLoaded) {
            $data['unity'] = $this->product->unity->abbreviation;
            $data['unity_id'] = $this->product->unity->id;
            $data['unity_quantity'] = $this->product->quantity;
        } else {
            $data['unity'] = null;
            $data['unity_id'] = null;
            $data['unity_quantity'] = null;
        }

        if ($categoryLoaded) {
            $data['category'] = $this->product->category->name;
        } else {
            $data['category'] = null;
        }

        if ($companyProductLoaded && $this->companyProduct) {
            $data['company_id'] = $this->companyProduct->company_id;
            $data['company_name'] = $this->companyProduct->company->name ?? null;
            $data['store_address'] = $this->companyProduct->company->address ?? null;
        }

        return $data;
    }
}
