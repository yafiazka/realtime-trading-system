<?php

namespace App\Services;

use App\Classes\OperatorAmount;
use App\Enums\OrderSideEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\OrderTypeEnum;
use App\Models\Order;
use App\Models\TradingPair;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        protected WalletService $walletService
    ) {
        $this->walletService = $walletService;
    }

    public function placeLimitOrder(
        User $user,
        TradingPair $pair,
        OrderSideEnum $side,
        OperatorAmount $price,
        OperatorAmount $quantity,
        string $clientOrderId
    ): Order {
        $existingOrder = Order::where('client_order_id', $clientOrderId)->first();
        if ($existingOrder) {
            return $existingOrder;
        }

        $this->validateOrderRules($pair, $price, $quantity);

        $assetToLock = $side === OrderSideEnum::BUY ? $pair->quote_asset : $pair->base_asset;
        $amountToLock = $this->calculateRequiredFunds($side, $price, $quantity);

        return DB::transaction(function () use ($user, $pair, $side, $price, $quantity, $clientOrderId, $assetToLock, $amountToLock) {

            $order = Order::create([
                'user_id' => $user->id,
                'trading_pair_id' => $pair->id,
                'client_order_id' => $clientOrderId,
                'side' => $side->value,
                'type' => OrderTypeEnum::LIMIT->value,
                'price' => (string) $price,
                'quantity' => (string) $quantity,
                'filled_quantity' => '0.00000000',
                'status' => OrderStatusEnum::OPEN->value,
            ]);

            $this->walletService->lockFundsForOrder(
                userId: $user->id,
                asset: $assetToLock,
                amountToLock: $amountToLock,
                referenceType: Order::class,
                referenceId: $order->id
            );


            return $order;
        });
    }

    public function cancelOrder(User $user, Order $order): void
    {
        if ($order->user_id !== $user->id) {
            throw new InvalidArgumentException("You do not have access to this order.");
        }

        if (!in_array($order->status, [OrderStatusEnum::OPEN, OrderStatusEnum::PARTIALLY_FILLED])) {
            throw new InvalidArgumentException("Order cannot be canceled (Status: {$order->status->value}).");
        }

        $pair = $order->tradingPair;
        $remainingQuantity = (new OperatorAmount($order->quantity))->subtract(new OperatorAmount($order->filled_quantity));
        $assetToUnlock = $order->side === OrderSideEnum::BUY ? $pair->quote_asset : $pair->base_asset;
        $amountToUnlock = $this->calculateRequiredFunds($order->side, new OperatorAmount($order->price), $remainingQuantity);

        DB::transaction(function () use ($order, $assetToUnlock, $amountToUnlock) {
            $this->walletService->unlockFunds(
                userId: $order->user_id,
                asset: $assetToUnlock,
                amountToUnlock: $amountToUnlock,
                referenceType: Order::class,
                referenceId: $order->id
            );

            $order->status = OrderStatusEnum::CANCELED;
            $order->save();
        });
    }

    private function calculateRequiredFunds(OrderSideEnum $side, OperatorAmount $price, OperatorAmount $quantity): OperatorAmount
    {
        if ($side === OrderSideEnum::BUY) {
            return $price->multiply((string) $quantity);
        }

        return $quantity;
    }

    private function validateOrderRules(TradingPair $pair, OperatorAmount $price, OperatorAmount $quantity): void
    {
        $minQty = new OperatorAmount($pair->min_quantity);
        if ($quantity->isLessThan($minQty)) {
            throw new InvalidArgumentException("Minimum quantity for {$pair->base_asset}/{$pair->quote_asset} is {$pair->min_quantity}.");
        }
        $tickSize = new OperatorAmount($pair->tick_size);
        if (!$tickSize->isZero()) {
            $remainder = $price->modulo($tickSize);
        }
    }
}
