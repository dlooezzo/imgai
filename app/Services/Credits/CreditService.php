<?php

namespace App\Services\Credits;

use App\Exceptions\InsufficientCreditsException;
use App\Models\CreditTransaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditService
{
    /**
     * Resolve a User instance from model, ID, or email.
     */
    public function resolveUser(User|int|string $user): ?User
    {
        if ($user instanceof User) {
            return $user;
        }

        if (is_numeric($user)) {
            return User::find((int) $user);
        }

        if (is_string($user) && filter_var($user, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $user)->first();
        }

        if (is_string($user)) {
            return User::find($user);
        }

        return null;
    }

    /**
     * Get the current credit balance of a user.
     */
    public function getBalance(User|int|string $user): int
    {
        $userModel = $this->resolveUser($user);
        if (!$userModel) {
            return 0;
        }

        return (int) $userModel->fresh()?->credit_balance ?? 0;
    }

    /**
     * Check if a user has at least the required number of credits.
     */
    public function hasEnoughCredits(User|int|string $user, int $cost): bool
    {
        return $this->getBalance($user) >= $cost;
    }

    /**
     * Add credits to a user atomically within a database transaction.
     */
    public function addCredits(
        User|int|string $user,
        int $amount,
        string $type,
        string $source,
        ?string $referenceId = null,
        string $description = ''
    ): CreditTransaction {
        if ($amount <= 0) {
            throw new Exception("Credit addition amount must be greater than zero. Received: {$amount}");
        }

        return DB::transaction(function () use ($user, $amount, $type, $source, $referenceId, $description) {
            $userModel = $this->resolveUser($user);
            if (!$userModel) {
                throw new Exception("User could not be resolved for credit addition.");
            }

            // Lock user record for update to prevent race conditions
            $lockedUser = User::where('id', $userModel->id)->lockForUpdate()->firstOrFail();

            $balanceAfter = (int) $lockedUser->credit_balance + $amount;
            $lockedUser->credit_balance = $balanceAfter;
            $lockedUser->save();

            $ledgerEntry = CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'amount' => $amount,
                'type' => $type,
                'source' => $source,
                'reference_id' => $referenceId,
                'description' => $description ?: "Granted {$amount} credits via {$source}",
                'balance_after' => $balanceAfter,
            ]);

            Log::info("[CREDITS ADDED] User ID: {$lockedUser->id} | Added: {$amount} | Balance: {$balanceAfter} | Source: {$source} | Ref: {$referenceId}");

            return $ledgerEntry;
        });
    }

    /**
     * Deduct credits from a user atomically within a database transaction.
     * Throws InsufficientCreditsException if the balance is too low.
     */
    public function deductCredits(
        User|int|string $user,
        int $amount,
        string $type,
        string $source,
        ?string $referenceId = null,
        string $description = ''
    ): CreditTransaction {
        if ($amount <= 0) {
            throw new Exception("Credit deduction amount must be greater than zero. Received: {$amount}");
        }

        return DB::transaction(function () use ($user, $amount, $type, $source, $referenceId, $description) {
            $userModel = $this->resolveUser($user);
            if (!$userModel) {
                throw new Exception("User could not be resolved for credit deduction.");
            }

            // Lock user record for update to prevent concurrent deductions causing negative balances
            $lockedUser = User::where('id', $userModel->id)->lockForUpdate()->firstOrFail();

            $currentBalance = (int) $lockedUser->credit_balance;
            if ($currentBalance < $amount) {
                throw new InsufficientCreditsException($amount, $currentBalance);
            }

            $balanceAfter = $currentBalance - $amount;
            $lockedUser->credit_balance = $balanceAfter;
            $lockedUser->save();

            $ledgerEntry = CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'amount' => -$amount,
                'type' => $type,
                'source' => $source,
                'reference_id' => $referenceId,
                'description' => $description ?: "Deducted {$amount} credits for {$source}",
                'balance_after' => $balanceAfter,
            ]);

            Log::info("[CREDITS DEDUCTED] User ID: {$lockedUser->id} | Deducted: {$amount} | Balance: {$balanceAfter} | Source: {$source} | Ref: {$referenceId}");

            return $ledgerEntry;
        });
    }

    /**
     * Refund credits that were deducted (e.g. on failed generation).
     */
    public function refundCredits(
        User|int|string $user,
        int $amount,
        string $source,
        ?string $referenceId = null,
        string $description = 'Refunded credits for failed generation'
    ): CreditTransaction {
        return $this->addCredits(
            user: $user,
            amount: $amount,
            type: 'generation_refund',
            source: $source,
            referenceId: $referenceId,
            description: $description
        );
    }
}
