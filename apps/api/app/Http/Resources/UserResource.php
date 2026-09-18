<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;

/** @mixin User */
class UserResource extends BaseUserResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $viewer = $request->user();
        $viewAsAdmin = $viewer?->isAdmin() === true;

        return array_merge(
            $this->getCommonFields(),
            match (true) {
                $viewAsAdmin || $this->type === User::TYPE_ADMIN => [
                    'companies' => $this->whenLoaded('companies', $this->companies),
                    'email_verified_at' => $this->email_verified_at,
                    'created_at' => $this->created_at,
                    'updated_at' => $this->updated_at,
                    'deleted_at' => $this->deleted_at,
                ],
                $this->type === User::TYPE_COMPANY => [
                    'activeCompanies' => $this->whenLoaded('activeCompanies', $this->activeCompanies),
                    'pendingCompanies' => $this->whenLoaded(
                        'pendingCompanies',
                        fn () => CompanyResource::collection($this->pendingCompanies)
                    ),
                ],
                default => [
                    'points' => $this->points,
                    'reputation' => $this->reputation,
                ],
            }
        );
    }
}
