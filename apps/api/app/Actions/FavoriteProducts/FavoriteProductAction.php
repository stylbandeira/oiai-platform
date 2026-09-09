<?php

namespace App\Actions\FavoriteProducts;

use App\Http\Requests\FavoriteProducts\FavoriteProductRequest;
use App\Models\FavoriteProducts;
use App\Models\Product;
use App\Repositories\FavoriteProductsRepository;
use Illuminate\Support\Facades\Log;

class FavoriteProductAction
{
    public function __construct(
        private FavoriteProductsRepository $favoriteProductsRepository
    ) {}
    public function execute(FavoriteProductRequest $request, Product $product)
    {
        $user = $request->user();

        try {
            $favorite = $this->favoriteProductsRepository->findUserProductFavorite($user, $product);

            $shouldFavorite = $request->has('favorite')
                ? $request->validated()['favorite']
                : !$favorite;

            if ($shouldFavorite && !$favorite) {
                $this->favoriteProductsRepository->create($user, $product);
            }

            if (!$shouldFavorite && $favorite) {
                $favorite->delete();
            }
        } catch (\Throwable $th) {
            Log::alert([
                'error' => 'Problem with favoriting product',
                'message' => $th->getMessage(),
            ]);

            return response([
                'message' => 'Não foi possível remover o item dos seus favoritos',
            ], 400);
        }

        return response([
            'message' => $shouldFavorite ? 'Produto favoritado' : 'Produto desfavoritado',
        ]);
    }
}
