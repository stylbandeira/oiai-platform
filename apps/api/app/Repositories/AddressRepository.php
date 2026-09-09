<?php

namespace App\Repositories;

use App\Models\Address;

class AddressRepository
{
    protected $address;

    public function __construct(Address $address)
    {
        $this->address = $address;
    }

    public function all()
    {
        return $this->address->all();
    }

    public function find($id)
    {
        return $this->address->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->address->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        return $this->address->destroy($id);
    }
}
