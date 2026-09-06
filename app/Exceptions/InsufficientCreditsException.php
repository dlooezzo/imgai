<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientCreditsException extends Exception
{
    protected int $requiredCredits;
    protected int $currentBalance;

    public function __construct(int $requiredCredits = 0, int $currentBalance = 0, string $message = 'Insufficient credits.')
    {
        $this->requiredCredits = $requiredCredits;
        $this->currentBalance = $currentBalance;
        parent::__construct($message, 402);
    }

    public function getRequiredCredits(): int
    {
        return $this->requiredCredits;
    }

    public function getCurrentBalance(): int
    {
        return $this->currentBalance;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'insufficient_credits',
            'message' => 'Insufficient credits. Please upgrade your plan or purchase credits to continue.',
            'required_credits' => $this->requiredCredits,
            'current_balance' => $this->currentBalance,
        ], 402);
    }
}
