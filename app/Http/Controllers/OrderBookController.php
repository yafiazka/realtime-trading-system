<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Trade;
use App\Models\TradingPair;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class OrderBookController extends Controller
{
    public function show(int $tradingPairId): JsonResponse
    {
        $pair = TradingPair::findOrFail($tradingPairId);
        
        $bids = Order::where('trading_pair_id', $pair->id)
            ->where('side', 'BUY')
            ->whereIn('status', ['OPEN', 'PARTIALLY_FILLED'])
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get(['price', 'quantity', 'filled_quantity']);

        $asks = Order::where('trading_pair_id', $pair->id)
            ->where('side', 'SELL')
            ->whereIn('status', ['OPEN', 'PARTIALLY_FILLED'])
            ->orderBy('price', 'asc')
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get(['price', 'quantity', 'filled_quantity']);

        return response()->json([
            'pair' => $pair,
            'bids' => $bids,
            'asks' => $asks,
        ]);
    }

    public function userTrades(): JsonResponse
    {
        $user = User::first();
        
        $trades = Trade::whereHas('makerOrder', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orWhereHas('takerOrder', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['makerOrder', 'takerOrder'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $trades,
        ]);
    }

    public function recentTrades(int $tradingPairId): JsonResponse
    {
        $pair = TradingPair::findOrFail($tradingPairId);
        
        $trades = Trade::whereHas('makerOrder', function ($query) use ($pair) {
                $query->where('trading_pair_id', $pair->id);
            })
            ->orWhereHas('takerOrder', function ($query) use ($pair) {
                $query->where('trading_pair_id', $pair->id);
            })
            ->with(['makerOrder', 'takerOrder'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'pair' => $pair,
            'trades' => $trades,
        ]);
    }
}