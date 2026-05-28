<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    protected $fillable = ['sale_id', 'payment_type', 'amount', 'exchange_rate', 'amount_in_tl'];

    protected $casts = [
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_in_tl' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public static function paymentLabel(string $type): string
    {
        return match($type) {
            'TL' => 'Türk Lirası',
            'EURO' => 'Euro',
            'DOLAR' => 'Dolar',
            'RUBLE' => 'Ruble',
            'PAUND' => 'Pound',
            'KREDI_KARTI' => 'Kredi Kartı',
            'BANKA_HAVALE' => 'Banka Havale',
            default => $type,
        };
    }
}
