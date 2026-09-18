<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;

/** @mixin User */
class CompanyUserResource extends BaseUserResource
{
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

    /**
     * Method to be overwritten by child
     */
    protected function getUserSpecificFields(): array
    {
        return [
            'activeCompanies' => $this->whenLoaded('activeCompanies', $this->activeCompanies),
            'pendingCompanies' => $this->whenLoaded('pendingCompanies', function () {
                return CompanyResource::collection($this->pendingCompanies);
            }),
        ];
    }
}
