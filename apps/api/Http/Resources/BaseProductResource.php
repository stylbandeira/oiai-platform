<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BaseProductResource extends JsonResource
{

    /**
     * Campos comuns para TODOS os tipos de usuário
     */
    protected function getCommonFields(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'img' => $this->img ? config('app.url') . '/storage/' . $this->img : null,
            'ean' => $this->ean,
            'average_price' => floatval($this->average_price),
            'validated' => $this->validated,

            'mentioned_quantity' => $this->mentioned_quantity,
            'mentioned_quantity_variant' => $this->mentioned_quantity_variant,

            'unity' => $this->whenLoaded('unity', $this->unity->abbreviation),
            'unity_id' => $this->whenLoaded('unity', $this->unity->id),
            'unity_quantity' => $this->whenLoaded('unity', $this->quantity),
            'category' => $this->whenLoaded('category', $this->category->name),
            'companies_count' => $this->whenLoaded('companies', count($this->companies)),
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
