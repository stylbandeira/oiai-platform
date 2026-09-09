<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseEventResource extends JsonResource
{
    /**
     * Campos comuns para TODOS os tipos de usuário
     */
    protected function getCommonFields(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'where' => $this->where,
            'type' => $this->type,
            'points' => $this->points,
            'link' => $this->link,
            'target_type' => $this->target_type,
            'checked' => $this->checked,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'is_new' => Carbon::now()->subDays(3) < $this->created_at,
            'created_at' => $this->created_at,
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
