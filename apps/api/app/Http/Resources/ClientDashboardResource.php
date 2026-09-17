<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'activeLists' => $this->activeLists ? $this->activeLists->count() : 0,
            'points' => $this->points,
            'monthEconomy' => $this->monthEconomy,
            'reputation' => $this->reputation,
            'recentActivity' => $this->recentActivity,
        ];
    }
}
