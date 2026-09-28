<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\ProductTypeStoreRequest;
use App\Models\ProductType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class ProductTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => ProductType::query()->orderBy('name')->get(),
        ]);
    }

    public function store(ProductTypeStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['normalized_name'] = Str::lower(Str::ascii($data['name']));

        return response()->json([
            'product_type' => ProductType::create($data),
        ], 201);
    }
}
