<?php

namespace App\Http\Resources;

class CompanyProductResource extends BaseProductResource
{
    protected function getUserSpecificFields(): array
    {
        return [];
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
