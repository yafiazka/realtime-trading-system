<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Services\MatchingEngineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class MatchOrdersCommand extends Command
{
    protected $signature = 'engine:match';
    protected $description = 'Run the Matching Engine Daemon to process incoming orders';

    public function handle(MatchingEngineService $engine): int
    {
        $this->info('Matching Engine started. Listening for orders...');

        $queueKey = 'matching_engine:queue';

        while (true) {
            $result = Redis::connection()->blpop($queueKey, 5);

            if ($result) {
                $orderId = (int) $result[1];
                $order = Order::find($orderId);

                if ($order && in_array($order->status, [OrderStatusEnum::OPEN, OrderStatusEnum::PARTIALLY_FILLED], true)) {
                    try {
                        $engine->processOrder($order);
                    } catch (\Throwable $e) {
                        $this->error("Error processing order {$orderId}: " . $e->getMessage());
                        Log::error('Matching Engine Error', [
                            'order_id' => $orderId,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            }

            usleep(10000);
        }

        return Command::SUCCESS;
    }
}
