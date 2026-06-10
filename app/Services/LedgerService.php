<?php

namespace App\Services;

use App\Classes\OperatorAmount;
use App\Enums\LedgerType;
use App\Models\LedgerEntry;
use App\Models\Wallet;

class LedgerService
{
    public function recordTransaction(
        Wallet $wallet,
        LedgerType $type,
        OperatorAmount $amountChangeBalance,
        OperatorAmount $amountChangeLocked,
        string $referenceType,
        int|string $referenceId
    ): void {
        $currentBalance = new OperatorAmount($wallet->balance);
        $currentLocked = new OperatorAmount($wallet->locked_balance);

        $newBalance = $currentBalance->add($amountChangeBalance);
        $newLocked = $currentLocked->add($amountChangeLocked);

        if ($newBalance->isNegative() || $newLocked->isNegative()) {
            throw new \RuntimeException("Transaction would result in negative balance for wallet ID: {$wallet->id}");
        }

        $wallet->balance = (string) $newBalance;
        $wallet->locked_balance = (string) $newLocked;
        $wallet->save();

        LedgerEntry::create([
            'wallet_id' => $wallet->id,
            'type' => $type->value,
            'amount' => (string) $amountChangeBalance->add($amountChangeLocked),
            'balance_after' => (string) $newBalance,
            'locked_after' => (string) $newLocked,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_at' => now(),
        ]);
    }
}
