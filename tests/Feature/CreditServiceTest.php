<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientCreditsException;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Credits\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CreditService $creditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->creditService = app(CreditService::class);
    }

    /**
     * Test adding credits updates user balance and writes to credit ledger.
     */
    public function test_add_credits_updates_balance_and_ledger(): void
    {
        $user = User::factory()->create(['credit_balance' => 50]);

        $ledger = $this->creditService->addCredits(
            user: $user,
            amount: 500,
            type: 'subscription_grant',
            source: 'paddle_webhook',
            referenceId: 'txn_123',
            description: 'Starter plan allocation'
        );

        $this->assertEquals(550, $this->creditService->getBalance($user));
        $this->assertEquals(550, $ledger->balance_after);
        $this->assertEquals(500, $ledger->amount);
        $this->assertDatabaseHas('credit_transactions', [
            'id' => $ledger->id,
            'user_id' => $user->id,
            'amount' => 500,
            'balance_after' => 550,
        ]);
    }

    /**
     * Test deducting credits updates user balance and writes negative amount to ledger.
     */
    public function test_deduct_credits_updates_balance_and_ledger(): void
    {
        $user = User::factory()->create(['credit_balance' => 10]);

        $ledger = $this->creditService->deductCredits(
            user: $user,
            amount: 1,
            type: 'generation_deduction',
            source: 'image_generation',
            description: 'Text-to-Image creation'
        );

        $this->assertEquals(9, $this->creditService->getBalance($user));
        $this->assertEquals(9, $ledger->balance_after);
        $this->assertEquals(-1, $ledger->amount);
        $this->assertDatabaseHas('credit_transactions', [
            'id' => $ledger->id,
            'user_id' => $user->id,
            'amount' => -1,
            'balance_after' => 9,
        ]);
    }

    /**
     * Test insufficient credits throws InsufficientCreditsException and prevents negative balance.
     */
    public function test_insufficient_credits_throws_exception_and_preserves_balance(): void
    {
        $user = User::factory()->create(['credit_balance' => 2]);

        $this->expectException(InsufficientCreditsException::class);

        // Attempt to deduct 5 credits when balance is 2
        $this->creditService->deductCredits(
            user: $user,
            amount: 5,
            type: 'generation_deduction',
            source: 'video_generation'
        );

        // Balance must remain 2, not -3!
        $this->assertEquals(2, $this->creditService->getBalance($user));
        $this->assertEquals(0, CreditTransaction::count());
    }

    /**
     * Test credit refund restores deducted credits.
     */
    public function test_refund_credits_restores_user_balance(): void
    {
        $user = User::factory()->create(['credit_balance' => 20]);

        // Deduct 5 credits for video
        $this->creditService->deductCredits($user, 5, 'generation_deduction', 'video_generation');
        $this->assertEquals(15, $this->creditService->getBalance($user));

        // Refund 5 credits
        $refundLedger = $this->creditService->refundCredits(
            user: $user,
            amount: 5,
            source: 'video_generation',
            referenceId: 'gen_uuid_999',
            description: 'Refund due to provider error'
        );

        $this->assertEquals(20, $this->creditService->getBalance($user));
        $this->assertEquals(20, $refundLedger->balance_after);
        $this->assertEquals(5, $refundLedger->amount);
        $this->assertEquals('generation_refund', $refundLedger->type);
    }

    /**
     * Test image generation endpoint rejects request with 402 if user has insufficient credits.
     */
    public function test_image_generation_returns_402_when_credits_are_insufficient(): void
    {
        $user = User::factory()->create(['credit_balance' => 0]);

        $response = $this->actingAs($user)
            ->withSession(['supabase_user_id' => (string) $user->id])
            ->postJson(route('tools.image.generate'), [
                'prompt' => 'Cinematic cybernetic landscape',
                'aspect_ratio' => '16:9',
            ]);

        $response->assertStatus(402);
        $response->assertJson([
            'success' => false,
            'error' => 'insufficient_credits',
            'required_credits' => 1,
            'current_balance' => 0,
        ]);
    }

    /**
     * Test video generation endpoint rejects request with 402 if user has insufficient credits.
     */
    public function test_video_generation_returns_402_when_credits_are_insufficient(): void
    {
        $user = User::factory()->create(['credit_balance' => 2]); // Requires 5 credits

        $response = $this->actingAs($user)
            ->withSession(['supabase_user_id' => (string) $user->id])
            ->postJson(route('tools.video.generate'), [
                'prompt' => 'A drone flying through neon metropolis',
                'aspect_ratio' => '16:9',
            ]);

        $response->assertStatus(402);
        $response->assertJson([
            'success' => false,
            'error' => 'insufficient_credits',
            'required_credits' => 5,
            'current_balance' => 2,
        ]);
    }
}
