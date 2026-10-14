<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductReviewSuggestion;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;

/** @mixin Product */
class AdminProductResource extends BaseProductResource
{
    protected function getUserSpecificFields(): array
    {
        return [
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'quantity' => $this->quantity,
            'raw_name' => $this->raw_name,
            'status' => $this->reviewStatus(),
            'pending_suggestions' => $this->relationLoaded('pendingReviewSuggestions')
                ? $this->pendingReviewSuggestions->map(fn (ProductReviewSuggestion $suggestion) => [
                    'id' => $suggestion->id,
                    'type' => $suggestion->type,
                    'original_name' => $suggestion->original_name,
                    'suggested_values' => $suggestion->suggested_values,
                    'suggested_product' => $suggestion->suggestedProduct
                        ? [
                            'id' => $suggestion->suggestedProduct->id,
                            'name' => $suggestion->suggestedProduct->name,
                        ] : null,
                    'submitted_by' => $suggestion->submitter?->name,
                ])->all()
                : [],
        ];
    }

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
}
