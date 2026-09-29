<?php

namespace App\Http\Controllers;

use App\Contracts\Product\ProductCombinationOptimizer;
use App\Http\Requests\List\ShoppingListRequirementRequest;
use App\Models\CompanyProducts;
use App\Models\ItensList;
use App\Models\ShoppingListRequirement;
use Illuminate\Http\JsonResponse;

final class ShoppingListRequirementController extends Controller
{
    public function store(ShoppingListRequirementRequest $request, ItensList $list): JsonResponse
    {
        $this->authorize('update', $list);

        $requirement = $list->requirements()->create($request->validated());

        return response()->json(['requirement' => $requirement->load(['productType', 'desiredUnit'])], 201);
    }

    public function optimize(
        ItensList $list,
        ShoppingListRequirement $requirement,
        ProductCombinationOptimizer $optimizer,
    ): JsonResponse {
        $this->authorize('view', $list);
        abort_unless($requirement->list_id === $list->id, 404);

        $offers = CompanyProducts::query()
            ->whereHas('product', fn ($query) => $query->where('product_type_id', $requirement->product_type_id))
            ->with(['product.unity', 'company'])
            ->get();

        return response()->json([
            'combination' => $optimizer->optimize($requirement, $offers),
        ]);
    }
}
