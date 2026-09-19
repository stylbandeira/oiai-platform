<?php

namespace App\Http\Controllers;

use App\Actions\ProductCategory\IndexProductCategoryAction;

class ProductCategoryController extends Controller
{
    /**
     * Return all categories
     *
     * @return mixed
     */
    public function index(IndexProductCategoryAction $action)
    {
        return $action->execute();
    }
}
