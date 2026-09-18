<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use App\Models\Product;

/** @mixin Product */
class ClientProductResource extends BaseProductResource
{
    protected function getUserSpecificFields(): array
    {
        return [
            'isFavorite' => boolval(count($this->userFavorites)),

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
