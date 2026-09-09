<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends BaseModel
{
    use HasFactory;
    const VALID_AREA_CODES = [
        'PE' => '26',
        'SP' => '35',
    ];

    protected $table = 'invoice';

    public $fillable = [
        'access_key',
        'user_id',
        'company_id',
        'receipt_data',
        'invoice_data',
        'pending'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
