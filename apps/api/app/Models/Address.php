<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends BaseModel
{
    use HasFactory;
    protected $table = 'address';
    protected $fillable = [
        'country',
        'area',
        'city',
        'street',
        'number',
        'state',
        'cep',
        'complement',
        'latitude',
        'longitude',
        'geocode_status',
        'geocode_error',
        'geocoded_at'
    ];

    protected $attributes = [
        'country' => 'Brasil'
    ];

    public function getFullAddressAttribute()
    {
        return str_replace(',,', ',', $this->street .
            ',' . $this->number .
            ', ' . $this->area .
            ', ' . $this->city  .
            ', ' . $this->state);
    }
}
