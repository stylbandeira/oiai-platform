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
use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ClientProductResource;
use App\Models\Product;
use App\Services\ExportService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ProductIndexRequest $request, IndexProductAction $action)
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductStoreRequest $request, StoreProductAction $action)
    {
        $user = $request->user();

        if ($request->company_id && !$user->hasAccessToCompany($request->company_id)) {
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
     * @param  Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Product $product, ShowProductAction $action)
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
     * @param ProductExportRequest $request
     * @param ExportService $exportService
     * @return void
     */
    public function export(ProductExportRequest $request, ExportService $exportService, ExportProductAction $action)
    {
        return $action->execute($request, $exportService);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param integer $id
     * @param DestroyProductAction $action
     * @return void
     */
    public function destroy(int $id, DestroyProductAction $action)
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
}
