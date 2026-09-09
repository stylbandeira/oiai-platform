<?php

namespace App\Http\Resources;

class AdminUserResource extends BaseUserResource
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
            // 'img' => $this->img ? config('app.url') . '/storage/' . $this->img : null,
            'companies' => $this->whenLoaded('companies', $this->companies),

            //Only for Admin
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

        ];
    }
}
