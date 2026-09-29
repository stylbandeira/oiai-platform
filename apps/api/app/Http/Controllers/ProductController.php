<?php

namespace App\Http\Controllers;

use App\Actions\Product\BulkValidateProductAction;
use App\Actions\Product\DestroyProductAction;
use App\Actions\Product\ExportProductAction;
use App\Actions\Product\ImportProductAction;
use App\Actions\Product\IndexProductAction;
use App\Actions\Product\ShowProductAction;
use App\Actions\Product\StoreProductAction;
use App\Actions\Product\UpdateProductAction;
use App\Http\Requests\Product\ProductBulkValidateRequest;
use App\Http\Requests\Product\ProductExportRequest;
use App\Http\Requests\Product\ProductImportRequest;
use App\Http\Requests\Product\ProductIndexRequest;
use App\Http\Requests\Product\ProductNormalizationDecisionRequest;
use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ClientProductResource;
use App\Models\Product;
use App\Services\ExportService;
use App\Services\Product\ProductNormalizationDecisionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ProductIndexRequest $request, IndexProductAction $action): JsonResource
    {
        $user = $request->user();

        $products = $action->execute($user, $request->validated());

        if ($user->isAdmin()) {
            return AdminProductResource::collection($products);
        }

        return ClientProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResource|Response
     */
    public function store(ProductStoreRequest $request, StoreProductAction $action)
    {
        $user = $request->user();

        if ($request->company_id && ! $user->hasAccessToCompany($request->company_id)) {
            return response([
                'message' => 'Usuário company não possui empresa ativa.',
            ], 400);
        }

        $product = $action->execute($user, $request);

        return response([
            'product' => $user->isAdmin()
                ? new AdminProductResource($product)
                : new ClientProductResource($product),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @throws AuthorizationException
     */
    public function show(Request $request, Product $product, ShowProductAction $action): AdminProductResource|ClientProductResource
    {
        $user = $request->user();

        $product = $action->execute($product);

        if ($user->isClient()) {
            return new ClientProductResource($product);
        }

        return new AdminProductResource($product);
    }

    public function update(ProductUpdateRequest $request, Product $product, UpdateProductAction $action)
    {
        $this->authorize('update', $product);

        $updatedProduct = $action->execute(
            $product,
            $request->validated(),
            $request->file('img')
        );

        return response([
            'product' => $request->user()->isAdmin()
                ? new AdminProductResource($updatedProduct)
                : new ClientProductResource($updatedProduct),
        ]);
    }

    public function import(ProductImportRequest $request, ImportProductAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Exports an CSV file of products
     *
     * @return mixed
     */
    public function export(ProductExportRequest $request, ExportService $exportService, ExportProductAction $action)
    {
        return $action->execute($request, $exportService);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id, DestroyProductAction $action): Response
    {
        $action->execute($id);

        return response([
            'message' => 'Produto deletada com sucesso!',
        ]);
    }

    public function bulkValidate(ProductBulkValidateRequest $request, BulkValidateProductAction $action)
    {
        $affectedProducts = $action->execute($request->user(), $request->validated());

        return response([
            'message' => 'Produtos atualizados com sucesso!',
            'count' => count($affectedProducts),
        ]);
    }

    public function storeNormalizationDecision(
        ProductNormalizationDecisionRequest $request,
        Product $product,
        ProductNormalizationDecisionService $decisions,
    ): Response {
        $decision = $decisions->recordManualDecision(
            product: $product,
            selectedValues: $request->validated('selected_values'),
            reviewedBy: (int) $request->user()->getAuthIdentifier(),
            algorithmVersion: (int) $request->validated('algorithm_version', 2),
            validated: (bool) $request->validated('validated', true),
        );
        $product->refresh()->load('unity');

        return response([
            'decision' => $decision,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'normalized_name' => $product->normalized_name,
                'normalized_quantity' => $product->normalized_quantity,
                'quantity' => $product->quantity,
                'unity_quantity' => $product->quantity,
                'unity' => $product->unity?->abbreviation,
                'quantity_dimension' => $product->quantity_dimension,
                'unit_id' => $product->unit_id,
                'unity_id' => $product->unit_id,
                'normalization_validated' => $product->normalizationValidationIsComplete(),
                'normalization_next_attribute' => $product->nextNormalizationDecisionAttribute(),
                'name_normalization_validated' => $product->name_normalization_validated_at !== null,
                'quantity_normalization_validated' => $product->quantity_normalization_validated_at !== null,
            ],
        ], 201);
    }
}
