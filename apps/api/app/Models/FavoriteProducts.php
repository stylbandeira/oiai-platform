<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FavoriteProducts extends BaseModel
{
    use HasFactory;

    protected $table = 'favorite_products';
    protected $fillable = [
        'user_id',
        'product_id'
    ];
}
