<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;

/** @mixin Product */
class AdminProductResource extends BaseProductResource
{
    protected function getUserSpecificFields(): array
    {
        return [
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'quantity' => $this->whenLoaded('unity', $this->quantity),
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
