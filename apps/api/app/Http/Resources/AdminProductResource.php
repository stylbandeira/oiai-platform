<?php

namespace App\Http\Resources;

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
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
