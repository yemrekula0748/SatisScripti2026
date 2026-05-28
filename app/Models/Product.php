<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = ['company_id', 'barcode', 'name', 'sale_price', 'stock', 'unit', 'is_active'];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'stock' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
