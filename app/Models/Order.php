<?php

namespace App\Models;

use App\Enums\OrderSideEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\OrderTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'trading_pair_id',
        'client_order_id',
        'side',
        'type',
        'price',
        'quantity',
        'filled_quantity',
        'status'
    ];

    protected $casts = [
        'side' => OrderSideEnum::class,
        'type' => OrderTypeEnum::class,
        'status' => OrderStatusEnum::class,
        'price' => 'decimal:8',
        'quantity' => 'decimal:8',
        'filled_quantity' => 'decimal:8',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
