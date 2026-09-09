<?php

namespace App\Http\Resources;

class ClientProductResource extends BaseProductResource
{
    protected function getUserSpecificFields(): array
    {
        return [
            'isFavorite' => boolval(count($this->userFavorites))

        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
