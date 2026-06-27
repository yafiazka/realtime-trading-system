<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderSideEnum;
use App\Exceptions\InsufficientFundsException;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Order;
use App\Models\TradingPair;
use App\Models\User;
use App\Services\OrderService;
use App\Classes\OperatorAmount;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(PlaceOrderRequest $request, OrderService $orderService): JsonResponse
    {
        $user = User::first();
        $pair = TradingPair::findOrFail($request->trading_pair_id);

        try {
            $order = $orderService->placeLimitOrder(
                $user,
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
        } catch (InsufficientFundsException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function index(): JsonResponse
    {
        $user = User::first();
        $orders = Order::where('user_id', $user->id)
            ->with('tradingPair')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'data' => $order->load('tradingPair'),
        ]);
    }

    public function destroy(Order $order, OrderService $orderService): JsonResponse
    {
        $user = User::first();
        
        if ($order->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $orderService->cancelOrder($user, $order);

            return response()->json([
                'message' => 'Order canceled successfully',
                'data' => $order,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}