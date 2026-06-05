<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['name', 'address', 'phone', 'email', 'tax_number', 'logo', 'is_active', 'show_customer_field'];

    protected $casts = [
        'is_active' => 'boolean',
        'show_customer_field' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function currencyRates(): HasMany
    {
        return $this->hasMany(CurrencyRate::class);
    }
}
