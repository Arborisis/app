<?php

declare(strict_types=1);

namespace App\Services\Echo;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    public function ensureWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );
    }

    public function getBalance(User $user): float
    {
        $wallet = $this->ensureWallet($user);

        return (float) $wallet->balance;
    }

    public function credit(User $user, float $amount, ?string $description = null): Wallet
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif.');
        }

        return DB::transaction(function () use ($user, $amount, $description) {
            $wallet = $this->ensureWallet($user);
            $wallet->balance += $amount;
            $wallet->save();

            if ($description !== null) {
                Log::info('ECHO wallet credited', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'new_balance' => $wallet->balance,
                    'description' => $description,
                ]);
            }

            return $wallet;
        });
    }

    public function debit(User $user, float $amount, ?string $description = null): Wallet
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif.');
        }

        return DB::transaction(function () use ($user, $amount, $description) {
            $wallet = $this->ensureWallet($user);

            if ($wallet->balance < $amount) {
                throw new \RuntimeException('Solde ECHO insuffisant.');
            }

            $wallet->balance -= $amount;
            $wallet->save();

            if ($description !== null) {
                Log::info('ECHO wallet debited', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'new_balance' => $wallet->balance,
                    'description' => $description,
                ]);
            }

            return $wallet;
        });
    }
}
