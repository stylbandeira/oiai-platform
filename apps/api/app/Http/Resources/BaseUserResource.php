<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BaseUserResource extends JsonResource
{
    protected bool $withNotifications = false;

    public function withNotifications(): static
    {
        $this->withNotifications = true;

        return $this;
    }

    /**
     * Campos comuns para TODOS os tipos de usuário
     */
    protected function getCommonFields(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cpf' => $this->cpf,
            'status' => $this->status,
            'type' => $this->type,
            'email' => $this->email,
            'points' => $this->points,
            'notifications' => $this->when(
                $this->withNotifications,
                fn() => BaseEventResource::collection(
                    $this->notifications()->get()
                )
            ),
            'notificationList' => $this->when(
                $this->withNotifications,
                fn() => BaseEventResource::collection(
                    $this->visibleEvents()
                        ->latest()
                        ->get()
                )
            ),
            'token' => $this->token,
            'email_verified' => $this->hasVerifiedEmail()
        ];
    }

    /**
     * Method to be overwritten by child
     */
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
        return array_merge(
            $this->getCommonFields(),
            $this->getUserSpecificFields()
        );
    }
}
