<?php

use App\Http\Controllers\OrderBookController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/orderbook/{tradingPairId}', [OrderBookController::class, 'show']);
Route::get('/trades/recent/{tradingPairId}', [OrderBookController::class, 'recentTrades']);

Route::get('/wallets', [WalletController::class, 'index']);
Route::get('/wallets/{asset}', [WalletController::class, 'show']);

Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders', [OrderController::class, 'index']);
Route::get('/orders/{order}', [OrderController::class, 'show']);
Route::delete('/orders/{order}', [OrderController::class, 'destroy']);

Route::get('/trades', [OrderBookController::class, 'userTrades']);
Route::get('/trades/recent/{tradingPairId}', [OrderBookController::class, 'recentTrades']);