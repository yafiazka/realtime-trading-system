<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderSideEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\OrderTypeEnum;
use App\Models\Order;
use App\Models\Trade;
use App\Models\TradingPair;
use App\ValueObjects\OperatorAmount;
use Illuminate\Support\Facades\DB;

class MatchingEngineService
{
    public function __construct(
        protected OrderBookService $orderBookService,
        protected WalletService $walletService
    ) {}

    public function processOrder(Order $takerOrder): void
    {
        $pair = $takerOrder->tradingPair;
        
        $makerSide = $takerOrder->side === OrderSideEnum::BUY ? OrderSideEnum::SELL : OrderSideEnum::BUY;
        
        $makerOrderIds = $this->orderBookService->getBestOrders($pair->id, $makerSide, 50);
        
        if (empty($makerOrderIds)) {
            if ($takerOrder->type === OrderTypeEnum::LIMIT) {
                $this->orderBookService->addOrder($takerOrder);
            }
            return;
        }

        $takerOrder = Order::where('id', $takerOrder->id)->lockForUpdate()->first();
        if (!$takerOrder || $takerOrder->status === OrderStatusEnum::CANCELED) {
            return;
        }

        $takerRemaining = (new OperatorAmount($takerOrder->quantity))->subtract(new OperatorAmount($takerOrder->filled_quantity));

        foreach ($makerOrderIds as $makerId) {
            if ($takerRemaining->isZero() || $takerRemaining->isNegative()) {
                break;
            }

            $makerOrder = Order::where('id', $makerId)->lockForUpdate()->first();
            if (!$makerOrder || $makerOrder->status === OrderStatusEnum::CANCELED) {
                $this->orderBookService->removeOrder($makerOrder ?? new Order(['id' => $makerId, 'side' => $makerSide, 'trading_pair_id' => $pair->id]));
                continue;
            }

            $isMatch = $takerOrder->side === OrderSideEnum::BUY 
                ? (float) $takerOrder->price >= (float) $makerOrder->price
                : (float) $takerOrder->price <= (float) $makerOrder->price;

            if (!$isMatch) {
                break;
            }

            $makerRemaining = (new OperatorAmount($makerOrder->quantity))->subtract(new OperatorAmount($makerOrder->filled_quantity));
            
            $tradeQuantity = $takerRemaining->isLessThan($makerRemaining) ? $takerRemaining : $makerRemaining;
            $tradePrice = new OperatorAmount($makerOrder->price);

            $this->executeTrade($takerOrder, $makerOrder, $tradePrice, $tradeQuantity, $pair);

            $takerRemaining = $takerRemaining->subtract($tradeQuantity);
            
            $makerOrder->filled_quantity = (string) (new OperatorAmount($makerOrder->filled_quantity))->add($tradeQuantity);
            
            $makerQtyObj = new OperatorAmount($makerOrder->quantity);
            $makerFilledObj = new OperatorAmount($makerOrder->filled_quantity);
            
            $makerOrder->status = $makerFilledObj->isGreaterThan($makerQtyObj) || $makerFilledObj->__toString() === $makerQtyObj->__toString() 
                ? OrderStatusEnum::FILLED 
                : OrderStatusEnum::PARTIALLY_FILLED;
            
            if ($makerOrder->status === OrderStatusEnum::FILLED) {
                $this->orderBookService->removeOrder($makerOrder);
            }
            $makerOrder->save();
        }

        $takerOrder->filled_quantity = (string) (new OperatorAmount($takerOrder->quantity))->subtract($takerRemaining);
        
        if ($takerRemaining->isZero()) {
            $takerOrder->status = OrderStatusEnum::FILLED;
        } else {
            $takerOrder->status = OrderStatusEnum::PARTIALLY_FILLED;
            if ($takerOrder->type === OrderTypeEnum::LIMIT) {
                $this->orderBookService->addOrder($takerOrder);
            }
        }
        $takerOrder->save();
    }

    private function executeTrade(Order $taker, Order $maker, OperatorAmount $price, OperatorAmount $quantity, TradingPair $pair): void
    {
        DB::transaction(function () use ($taker, $maker, $price, $quantity, $pair) {
            $trade = Trade::create([
                'maker_order_id' => $maker->id,
                'taker_order_id' => $taker->id,
                'price' => (string) $price,
                'quantity' => (string) $quantity,
            ]);

            $totalQuoteAmount = $price->multiply((string) $quantity);
            $baseAmount = $quantity;

            if ($maker->side === OrderSideEnum::SELL) {
                $this->walletService->executeTradeSettlement(
                    userId: $maker->user_id,
                    assetSpent: $pair->base_asset,
                    amountSpent: $baseAmount,
                    assetReceived: $pair->quote_asset,
                    amountReceived: $totalQuoteAmount,
                    referenceType: Trade::class,
                    referenceId: $trade->id
                );
            } else {
                $this->walletService->executeTradeSettlement(
                    userId: $maker->user_id,
                    assetSpent: $pair->quote_asset,
                    amountSpent: $totalQuoteAmount,
                    assetReceived: $pair->base_asset,
                    amountReceived: $baseAmount,
                    referenceType: Trade::class,
                    referenceId: $trade->id
                );
            }

            if ($taker->side === OrderSideEnum::BUY) {
                $this->walletService->executeTradeSettlement(
                    userId: $taker->user_id,
                    assetSpent: $pair->quote_asset,
                    amountSpent: $totalQuoteAmount,
                    assetReceived: $pair->base_asset,
                    amountReceived: $baseAmount,
                    referenceType: Trade::class,
                    referenceId: $trade->id
                );
            } else {
                $this->walletService->executeTradeSettlement(
                    userId: $taker->user_id,
                    assetSpent: $pair->base_asset,
                    amountSpent: $baseAmount,
                    assetReceived: $pair->quote_asset,
                    amountReceived: $totalQuoteAmount,
                    referenceType: Trade::class,
                    referenceId: $trade->id
                );
            }
        }, 3);
    }
}