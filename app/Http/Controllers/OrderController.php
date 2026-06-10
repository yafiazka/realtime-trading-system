<?php

namespace App\Http\Controllers;

use App\Classes\OperatorAmount;
use App\Enums\OrderSideEnum;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\TradingPair;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(PlaceOrderRequest $request, OrderService $orderService): JsonResponse
    {
        $pair = TradingPair::findOrFail($request->trading_pair_id);

        try {
            $order = $orderService->placeLimitOrder(
                $request->user(),
                $pair,
                $request->enum('side', OrderSideEnum::class),
                new OperatorAmount($request->price),
                new OperatorAmount($request->quantity),
                $request->client_order_id
            );

            return response()->json([
                'message' => 'Order placed successfully.',
                'data' => $order
            ], 201);
        } catch (\App\Exceptions\InsufficientFundsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
