<?php

namespace App\Actions\Product;

use App\Http\Requests\Product\ProductExportRequest;
use App\Models\Product;
use App\Services\ExportService;

class ExportProductAction
{
    public function execute(ProductExportRequest $request, ExportService $exportService)
    {
        $query = Product::with(['category', 'unity']);

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('sku', 'like', $searchTerm);
            });
        }

        $products = $query->get();

        $columns = [
            'Name' => 'name',
            'Sku' => 'sku',
            'Category' => 'category.name',
            'Unity' => 'unity.name',
            'Quantity' => 'quantity',
            'Preço Médio (R$)' => function ($product) {
                return $product->average_price ? number_format($product->average_price, 2, ',', '.') : '0.00';
            },
        ];

        return $exportService->exportToCSV($products, $columns, 'produtos');
    }
}
