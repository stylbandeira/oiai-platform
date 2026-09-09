<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ClientDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
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
