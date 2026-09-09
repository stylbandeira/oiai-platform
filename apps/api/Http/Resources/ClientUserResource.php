<?php

namespace App\Http\Resources;

class ClientUserResource extends BaseUserResource
{
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

    /**
     * Method to be overwritten by child
     */
    protected function getUserSpecificFields(): array
    {
        return [
            'points' => $this->points,
            'reputation' => $this->reputation,
        ];
    }
}
