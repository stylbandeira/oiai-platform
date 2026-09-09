<?php

namespace App\Actions\Product;

use App\Http\Requests\Product\ProductImportRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unity;
use App\Rules\ExistsOr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImportProductAction
{
    public function execute(ProductImportRequest $request)
    {
        try {
            $file = $request->file('file');
            $filePath = $file->store('temp-csv');

            $result = $this->processCSV($filePath);
            Storage::delete($filePath);
            $products = $result['data'];

            $productCollection = collect($products);
            $categoryNames = $productCollection->pluck('category')
                ->filter(fn($c) => !is_numeric($c))
                ->map(fn($c) => mb_strtolower($c))
                ->unique()
                ->values();
            $categories = ProductCategory::whereIn('name', $categoryNames)
                ->pluck('id', 'name');

            $unityNames = $productCollection->pluck('unity')
                ->filter(fn($u) => !is_numeric($u))
                ->map(fn($u) => mb_strtolower($u))
                ->unique()
                ->values();
            $unities = Unity::whereIn('name', $unityNames)
                ->pluck('id', 'name');

            $products = $productCollection->map(function ($product) use ($categories, $unities) {
                if (!is_numeric($product['category'])) {
                    $product['category'] = $categories[$product['category']] ?? null;
                }

                if (!is_numeric($product['unity'])) {
                    $product['unity'] = $unities[$product['unity']] ?? null;
                }

                $product['average_price'] = $product['average_price'] == '' ? 0 : $product['average_price'];

                $product['category_id'] = $product['category'];
                unset($product['category']);
                $product['unit_id'] = $product['unity'];
                unset($product['unity']);

                return $product;
            });

            Product::insert($products->toArray());

            return response([
                'message' => 'CSV processado com sucesso!',
                'data' => $result,
            ]);
        } catch (\Throwable $th) {
            Log::alert(['Catch' => $th->getMessage()]);
            return response([
                'message' => 'Erro ao processar CSV: ' . $th->getMessage(),
            ]);
        }
    }

    private function processCSV($filePath): array
    {
        $fullPath = Storage::path($filePath);
        $handle = fopen($fullPath, 'r');

        $headers = fgetcsv($handle, 1000, ',');
        $headerCount = count($headers);

        $rows = [];
        $rowCount = 0;
        $errors = [];

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $rowCount++;

            if (count($row) !== $headerCount) {
                $errors[] = "Linha $rowCount: Esperado $headerCount colunas, encontrado " . count($row);
                continue;
            }

            $rows[] = array_combine($headers, $row);
        }

        fclose($handle);

        return [
            'total_rows' => $rowCount,
            'processed_rows' => count($rows),
            'errors' => $errors,
            'sample_data' => array_slice($rows, 0, 3),
            'data' => $rows,
        ];
    }
}
