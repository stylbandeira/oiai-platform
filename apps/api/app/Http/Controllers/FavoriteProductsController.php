<?php

namespace App\Http\Controllers;

use App\Actions\FavoriteProducts\FavoriteProductAction;
use App\Http\Requests\FavoriteProducts\FavoriteProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return mixed
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return mixed
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return mixed
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return mixed
     */
    public function update(Request $request, $id) {}

    /**
     * Favorite or unfavorite a product from an user
     *
     * @return mixed
     */
    public function favorite(FavoriteProductRequest $request, Product $product, FavoriteProductAction $action)
    {
        return $action->execute($request, $product);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return mixed
     */
    public function destroy($id)
    {
        //
    }
}
