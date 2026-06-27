<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function index(): JsonResponse
    {
        $user = User::first();
        $wallets = Wallet::where('user_id', $user->id)->get();

        return response()->json([
            'data' => $wallets,
        ]);
    }

    public function show(string $asset): JsonResponse
    {
        $user = User::first();
        $wallet = Wallet::where('user_id', $user->id)
            ->where('asset', strtoupper($asset))
            ->firstOrFail();

        return response()->json([
            'data' => $wallet,
        ]);
    }
}