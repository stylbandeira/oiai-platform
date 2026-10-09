<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
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
            'img' => $this->img ? config('app.url').'/storage/'.$this->img : null,
            'ean' => $this->ean,
            'average_price' => floatval($this->average_price),
            'validated' => $this->validated,
            'normalization_validated' => $this->normalizationValidationIsComplete(),
            'normalization_next_attribute' => $this->nextNormalizationDecisionAttribute(),
            'name_normalization_validated' => $this->name_normalization_validated_at !== null,
            'quantity_normalization_validated' => $this->quantity_normalization_validated_at !== null,

            'mentioned_quantity' => $this->mentioned_quantity,
            'mentioned_quantity_variant' => $this->mentioned_quantity_variant,

            'unity' => $this->whenLoaded('unity', fn () => $this->unity?->abbreviation),
            'unity_id' => $this->whenLoaded('unity', fn () => $this->unity?->id),
            'unity_quantity' => $this->whenLoaded('unity', fn () => $this->quantity),
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'product_type_id' => $this->product_type_id,
            'companies_count' => $this->whenLoaded('companies', fn () => $this->companies->count()),
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
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return array_merge(
            $this->getCommonFields(),
            $this->getUserSpecificFields()
        );
    }
}
