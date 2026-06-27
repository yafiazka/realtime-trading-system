<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TradingPair;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TradingSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::firstOrCreate(
            ['email' => 'trader1@example.com'],
            [
                'name' => 'Trader One',
                'password' => Hash::make('password'),
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'trader2@example.com'],
            [
                'name' => 'Trader Two',
                'password' => Hash::make('password'),
            ]
        );

        TradingPair::firstOrCreate(
            ['base_asset' => 'BTC', 'quote_asset' => 'USDT'],
            [
                'tick_size' => '0.01000000',
                'min_quantity' => '0.00010000',
                'is_active' => true,
            ]
        );

        TradingPair::firstOrCreate(
            ['base_asset' => 'ETH', 'quote_asset' => 'USDT'],
            [
                'tick_size' => '0.01000000',
                'min_quantity' => '0.00100000',
                'is_active' => true,
            ]
        );

        foreach ([$user1, $user2] as $user) {
            Wallet::firstOrCreate(
                ['user_id' => $user->id, 'asset' => 'USDT'],
                ['balance' => '100000.00000000', 'locked_balance' => '0.00000000']
            );

            Wallet::firstOrCreate(
                ['user_id' => $user->id, 'asset' => 'BTC'],
                ['balance' => '5.00000000', 'locked_balance' => '0.00000000']
            );

            Wallet::firstOrCreate(
                ['user_id' => $user->id, 'asset' => 'ETH'],
                ['balance' => '50.00000000', 'locked_balance' => '0.00000000']
            );
        }
    }
}