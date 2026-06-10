<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TradingPair extends Model
{
    protected $fillable = [
        'base_asset',
        'quote_asset',
        'tick_size',
        'min_quantity',
        'is_active',
    ];

    protected $casts = [
        'tick_size' => 'decimal:8',
        'min_quantity' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
