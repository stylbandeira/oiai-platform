<?php

namespace App\Models;

use App\Models\Scopes\WithTrashedForAdminScope;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new WithTrashedForAdminScope);
    }
}
