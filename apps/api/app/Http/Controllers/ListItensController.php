<?php

namespace App\Http\Controllers;

use App\Actions\ListItens\UpdateListItensAction;
use App\Http\Requests\ListItens\ListItensUpdateRequest;
use App\Models\ItensList;

class ListItensController extends Controller
{
    /**
     * Update list itens
     *
     * @return void
     */
    public function update(ListItensUpdateRequest $request, ItensList $list, UpdateListItensAction $action)
    {
        return $action->execute($request, $list);
    }
}
