<?php

namespace App\Http\Controllers;

use App\Actions\ProductCategory\IndexProductCategoryAction;

class ProductCategoryController extends Controller
{
    /**
     * Return all categories
     *
     * @return void
     */
    public function index(IndexProductCategoryAction $action)
    {
        return $action->execute();
    }
}
