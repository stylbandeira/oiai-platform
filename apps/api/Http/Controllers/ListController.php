<?php

namespace App\Http\Controllers;

use App\Actions\List\DestroyListAction;
use App\Actions\List\IndexListAction;
use App\Actions\List\OptimizeListAction;
use App\Actions\List\ShowListAction;
use App\Actions\List\StoreListAction;
use App\Actions\List\UpdateListAction;
use App\Http\Requests\List\ListOptimizeRequest;
use App\Http\Requests\List\ListStoreRequest;
use App\Http\Requests\List\ListUpdateRequest;
use App\Models\ItensList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ListController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(ItensList::class, 'list');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, IndexListAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ListStoreRequest $request, StoreListAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Display the specified resource.
     *
     * @param ItensList $list
     * @return void
     */
    public function show(ItensList $list, ShowListAction $action)
    {
        return $action->execute($list);
    }

    public function optimize(ListOptimizeRequest $request, ItensList $list, OptimizeListAction $action)
    {
        $locationData = $request->safe()->only([
            'latitude',
            'longitude',
            'distance',
        ]);

        if ($locationData !== []) {
            $list->forceFill($locationData)->save();
            $list->refresh();
        }

        $optimizedList = $action->execute($list->id);

        return response([
            'list' => $optimizedList,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  ItensList  $list
     * @return \Illuminate\Http\Response
     */
    public function update(ListUpdateRequest $request, ItensList $list, UpdateListAction $action)
    {
        return $action->execute($request, $list);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  ItensList  $list
     * @return \Illuminate\Http\Response
     */
    public function destroy(ItensList $list, DestroyListAction $action)
    {
        return $action->execute($list);
    }
}
