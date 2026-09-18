<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

/** @mixin User */
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
        /** @var User $user */
        $user = $this->resource;

        return [
            'activeLists' => $user->activeLists()->count(),
            'points' => $user->points,
            'monthEconomy' => $user->monthEconomy,
            'reputation' => $user->reputation,
            'recentActivity' => $user->recentActivity,
        ];
    }
}
