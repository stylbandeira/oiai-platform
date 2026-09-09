<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unity extends BaseModel
{
    use HasFactory;

    protected $table = 'unities';

    protected $fillable = [
        'name',
        'abbreviation',
        'dimension',
        'convertion_factor',
        'base_unity_id'
    ];

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn($value) => strtolower($value),
            set: fn($value) => strtolower($value)
        );
    }

    public function abbreviation(): Attribute
    {
        return Attribute::make(
            get: fn($value) => strtolower($value),
            set: fn($value) => strtolower($value)
        );
    }
}
