<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItensList extends BaseModel
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_INACTIVE = 'inactive';

    public const VALID_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_INACTIVE,
    ];

    protected $table = "list";

    public $fillable = [
        'user_id',
        'name',
        'favorite',
        'total',
        'optimized',
        'status'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'list_products', 'list_id', 'product_id')->withPivot(['quantity', 'company_product_id']);
    }

    public function listProducts()
    {
        return $this->hasMany(ListProducts::class, 'list_id');
    }

    public function completedSnapshot()
    {
        return $this->hasOne(CompletedList::class, 'list_id');
    }
}
