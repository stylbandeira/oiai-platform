<?php

namespace App\Actions\Address;

use App\Http\Requests\Address\AddressStoreRequest;
use App\Models\Address;
use App\Repositories\AddressRepository;

class StoreAddressAction
{
    private AddressRepository $addressRepository;

    public function __construct(AddressRepository $addressRepository)
    {
        $this->addressRepository = $addressRepository;
    }

    public function execute(AddressStoreRequest $request)
    {
        $address = $this->addressRepository->create($request->validated());

        return response([
            'address' => $address,
        ]);
    }
}
