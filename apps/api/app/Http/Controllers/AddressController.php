<?php

namespace App\Http\Controllers;

use App\Actions\Address\StoreAddressAction;
use App\Http\Requests\Address\AddressStoreRequest;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Address::class, 'address');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return void
     */
    public function store(AddressStoreRequest $request, StoreAddressAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Address $address)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Address $address)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Address $address)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Address $address)
    {
        //
    }
}
