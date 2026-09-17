<?php

namespace App\Http\Controllers;

use App\Actions\Unity\IndexUnityAction;
use App\Http\Requests\Unity\UnityIndexRequest;
use App\Http\Resources\UnityResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UnityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(UnityIndexRequest $request, IndexUnityAction $action)
    {
        $unities = $action->execute($request->validated());

        return UnityResource::collection($unities);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
