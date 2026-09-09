<?php

namespace App\Http\Resources;

class CompanyUserResource extends BaseUserResource
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
            'activeCompanies' => $this->whenLoaded('activeCompanies', $this->activeCompanies),
            'pendingCompanies' => $this->whenLoaded('pendingCompanies', function () {
                return CompanyResource::collection($this->pendingCompanies);
            })
        ];
    }
}
