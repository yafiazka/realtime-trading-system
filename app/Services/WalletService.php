<?php

namespace App\Services;

use App\Classes\OperatorAmount;
use App\Enums\LedgerType;
use App\Exceptions\InsufficientFundsException;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(
        protected LedgerService $ledgerService
    ) {
        $this->ledgerService = $ledgerService;
    }

    public function getWalletLocked(int $userId, string $asset): Wallet
    {
        return Wallet::where('user_id', $userId)
            ->where('asset', $asset)
            ->lockForUpdate()
            ->firstOr(function () use ($userId, $asset) {
                return Wallet::create([
                    'user_id' => $userId,
                    'asset' => $asset,
                    'balance' => '0.00000000',
                    'locked_balance' => '0.00000000',
                ]);
            });
    }

    public function lockFundsForOrder(int $userId, string $asset, OperatorAmount $amountToLock, string $referenceType, int|string $referenceId): void
    {
        DB::transaction(function () use ($userId, $asset, $amountToLock, $referenceType, $referenceId) {
            $wallet = $this->getWalletLocked($userId, $asset);
            $currentBalance = new OperatorAmount($wallet->balance);

            if ($currentBalance->isLessThan($amountToLock)) {
                throw new InsufficientFundsException(
                    asset: $asset,
                    required: (string) $amountToLock,
                    available: (string) $currentBalance
                );
            }

            $this->ledgerService->recordTransaction(
                wallet: $wallet,
                type: LedgerType::LOCK,
                amountChangeBalance: new OperatorAmount('-' . (string) $amountToLock),
                amountChangeLocked: $amountToLock,
                referenceType: $referenceType,
                referenceId: $referenceId
            );
        }, 3);
    }

    public function unlockFunds(int $userId, string $asset, OperatorAmount $amountToUnlock, string $referenceType, int|string $referenceId): void
    {
        DB::transaction(function () use ($userId, $asset, $amountToUnlock, $referenceType, $referenceId) {
            $wallet = $this->getWalletLocked($userId, $asset);

            $this->ledgerService->recordTransaction(
                wallet: $wallet,
                type: LedgerType::UNLOCK,
                amountChangeBalance: $amountToUnlock,
                amountChangeLocked: new OperatorAmount('-' . (string) $amountToUnlock),
                referenceType: $referenceType,
                referenceId: $referenceId
            );
        }, 3);
    }

    public function executeTradeSettlement(
        int $userId,
        string $assetSpent,
        OperatorAmount $amountSpent,
        string $assetReceived,
        OperatorAmount $amountReceived,
        string $referenceType,
        int|string $referenceId
    ): void {
        DB::transaction(function () use ($userId, $assetSpent, $amountSpent, $assetReceived, $amountReceived, $referenceType, $referenceId) {
            $walletSpent = $this->getWalletLocked($userId, $assetSpent);
            $this->ledgerService->recordTransaction(
                wallet: $walletSpent,
                type: LedgerType::DEBIT,
                amountChangeBalance: new OperatorAmount('0'),
                amountChangeLocked: new OperatorAmount('-' . (string) $amountSpent),
                referenceType: $referenceType,
                referenceId: $referenceId
            );

            $walletReceived = $this->getWalletLocked($userId, $assetReceived);
            $this->ledgerService->recordTransaction(
                wallet: $walletReceived,
                type: LedgerType::CREDIT,
                amountChangeBalance: $amountReceived,
                amountChangeLocked: new OperatorAmount('0'),
                referenceType: $referenceType,
                referenceId: $referenceId
            );
        }, 3);
    }
}
