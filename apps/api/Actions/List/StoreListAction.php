<?php

namespace App\Actions\List;

use App\Http\Requests\List\ListStoreRequest;
use App\Http\Resources\ClientListResource;
use App\Models\ItensList;
use App\Models\Product;
use App\Repositories\ListRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreListAction
{
    public function __construct(
        private ProductRepository $productRepository,
        private ListRepository $listRepository,
    ) {}
    public function execute(ListStoreRequest $request)
    {
        $user = Auth::user();

        $list = DB::transaction(function () use ($request, $user) {
            $list = $this->listRepository->createForUser($user, $request->validated());

            $productsWithQuantities = [];
            $productIds = array_column(array_column($request->products, 'product'), 'id');

            foreach ($request->products as $product) {
                $productsWithQuantities[$product['product']['id']] = ['quantity' => $product['quantity']];
            }

            $this->listRepository->attachProducts($list, $productsWithQuantities);
            $this->productRepository->incrementListAdded($productIds);

            return $list;
        });

        return response([
            'message' => 'Lista criada com sucesso!',
            'list' => new ClientListResource($list),
        ]);
    }
}
