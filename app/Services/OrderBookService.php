<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderSideEnum;
use App\Models\Order;
use Illuminate\Support\Facades\Redis;

class OrderBookService
{
    private function formatMember(int $orderId): string
    {
        return str_pad((string) $orderId, 20, '0', STR_PAD_LEFT);
    }

    private function getKey(int $tradingPairId, OrderSideEnum $side): string
    {
        $suffix = $side === OrderSideEnum::BUY ? 'bids' : 'asks';
        return "orderbook:{$tradingPairId}:{$suffix}";
    }

    public function addOrder(Order $order): void
    { 
        $key = $this->getKey($order->trading_pair_id, $order->side);
        $member = $this->formatMember($order->id);
        
        $score = $order->side === OrderSideEnum::BUY 
            ? -(float) $order->price 
            : (float) $order->price;

        Redis::connection()->zadd($key, $score, $member);
    }

    public function removeOrder(Order $order): void
    {
        $key = $this->getKey($order->trading_pair_id, $order->side);
        $member = $this->formatMember($order->id);
        Redis::connection()->zrem($key, $member);
    }

    public function getBestOrders(int $tradingPairId, OrderSideEnum $side, int $limit = 50): array
    {
        $key = $this->getKey($tradingPairId, $side);
        
        $members = Redis::connection()->zrangebyscore($key, '-inf', '+inf', [
            'limit' => [0, $limit]
        ]);

        return array_map(fn($m) => (int) ltrim($m, '0') ?: 0, $members);
    }
}