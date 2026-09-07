<?php

namespace Tests\Feature;

use App\Jobs\PollImageToVideoPredictionJob;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Models\VideoGeneration;
use App\Services\AI\Contracts\ImageGenerationInterface;
use App\Services\AI\Contracts\VideoGenerationInterface;
use App\Services\AI\WanImageToVideoService;
use App\Services\Storage\R2StorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AiCreditRefundOnFailureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['credits.costs.image_generation' => 1]);
        config(['credits.costs.video_generation' => 5]);
        config(['credits.costs.image_to_video' => 5]);
    }

    /**
     * Test 1: When image generation fails during polling, credits are refunded idempotently.
     */
    public function test_image_generation_polling_failure_refunds_credits_idempotently(): void
    {
        $user = User::create([
            'name' => 'Image User',
            'email' => 'img_refund@example.com',
            'password' => bcrypt('password'),
            'credit_balance' => 10,
        ]);

        $generation = Generation::create([
            'user_id' => (string) $user->id,
            'prediction_id' => 'pred_img_fail_123',
            'prompt' => 'Test prompt',
            'status' => 'processing',
        ]);

        // Record initial deduction
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => -1,
            'type' => 'generation_deduction',
            'source' => 'image_generation',
            'reference_id' => $generation->id,
            'balance_after' => 9,
        ]);
        $user->update(['credit_balance' => 9]);

        // Mock AI service to return failed
        $mockAi = Mockery::mock(ImageGenerationInterface::class);
        $mockAi->shouldReceive('getPredictionStatus')
            ->with('pred_img_fail_123')
            ->andReturn([
                'status' => 'failed',
                'error' => 'GPU out of memory on cluster',
                'http_status' => 200,
            ]);
        $this->app->instance(ImageGenerationInterface::class, $mockAi);

        // Call status endpoint
        $response = $this->getJson("/tools/image-generator/status/{$generation->id}");
        $response->assertStatus(200);

        // Assert status updated to failed
        $this->assertEquals('failed', $generation->fresh()->status);

        // Assert 1 credit refunded
        $this->assertEquals(10, $user->fresh()->credit_balance);

        $refundTxn = CreditTransaction::where('reference_id', $generation->id)
            ->where('type', 'generation_refund')
            ->first();
        $this->assertNotNull($refundTxn);
        $this->assertEquals(1, $refundTxn->amount);

        // Idempotency: call status again, should NOT double-refund
        $response2 = $this->getJson("/tools/image-generator/status/{$generation->id}");
        $response2->assertStatus(200);
        $this->assertEquals(10, $user->fresh()->credit_balance);
        $this->assertEquals(1, CreditTransaction::where('reference_id', $generation->id)->where('type', 'generation_refund')->count());
    }

    /**
     * Test 2: When video generation fails during polling, credits are refunded idempotently.
     */
    public function test_video_generation_polling_failure_refunds_credits_idempotently(): void
    {
        $user = User::create([
            'name' => 'Video User',
            'email' => 'vid_refund@example.com',
            'password' => bcrypt('password'),
            'credit_balance' => 20,
        ]);

        $videoGen = VideoGeneration::create([
            'user_id' => (string) $user->id,
            'prediction_id' => 'pred_vid_fail_456',
            'prompt' => 'Test video prompt',
            'status' => 'processing',
        ]);

        // Record initial deduction
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => -5,
            'type' => 'generation_deduction',
            'source' => 'video_generation',
            'reference_id' => $videoGen->id,
            'balance_after' => 15,
        ]);
        $user->update(['credit_balance' => 15]);

        // Mock video service to return failed
        $mockVideo = Mockery::mock(VideoGenerationInterface::class);
        $mockVideo->shouldReceive('getPredictionStatus')
            ->with('pred_vid_fail_456')
            ->andReturn([
                'status' => 'failed',
                'error' => 'Provider rendering engine timeout',
                'http_status' => 200,
            ]);
        $this->app->instance(VideoGenerationInterface::class, $mockVideo);

        // Call status endpoint
        $response = $this->getJson("/tools/video-generator/status/{$videoGen->id}");
        $response->assertStatus(200);

        // Assert status updated to failed
        $this->assertEquals('failed', $videoGen->fresh()->status);

        // Assert 5 credits refunded
        $this->assertEquals(20, $user->fresh()->credit_balance);

        $refundTxn = CreditTransaction::where('reference_id', $videoGen->id)
            ->where('type', 'generation_refund')
            ->first();
        $this->assertNotNull($refundTxn);
        $this->assertEquals(5, $refundTxn->amount);

        // Idempotency: call status again, should NOT double-refund
        $response2 = $this->getJson("/tools/video-generator/status/{$videoGen->id}");
        $response2->assertStatus(200);
        $this->assertEquals(20, $user->fresh()->credit_balance);
        $this->assertEquals(1, CreditTransaction::where('reference_id', $videoGen->id)->where('type', 'generation_refund')->count());
    }

    /**
     * Test 3: When Image-to-Video polling job encounters failure, credits are refunded idempotently.
     */
    public function test_image_to_video_job_failure_refunds_credits_idempotently(): void
    {
        $user = User::create([
            'name' => 'I2V User',
            'email' => 'i2v_refund@example.com',
            'password' => bcrypt('password'),
            'credit_balance' => 50,
        ]);

        $i2vGen = VideoGeneration::create([
            'user_id' => (string) $user->id,
            'generation_type' => 'image-to-video',
            'prediction_id' => 'pred_i2v_fail_789',
            'prompt' => 'Animate ocean',
            'status' => 'processing',
            'job_dispatched' => true,
        ]);

        // Record initial deduction
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => -5,
            'type' => 'generation_deduction',
            'source' => 'image_to_video',
            'reference_id' => $i2vGen->id,
            'balance_after' => 45,
        ]);
        $user->update(['credit_balance' => 45]);

        // Mock AI and R2 services
        $mockWan = Mockery::mock(WanImageToVideoService::class);
        $mockWan->shouldReceive('getPredictionStatus')
            ->with('pred_i2v_fail_789')
            ->andReturn([
                'status' => 'failed',
                'error' => 'Input image dimension incompatible',
            ]);

        $mockR2 = Mockery::mock(R2StorageService::class);

        // Execute job
        $job = new PollImageToVideoPredictionJob($i2vGen->id, 1, 0);
        $job->handle($mockWan, $mockR2);

        // Assert record is marked failed
        $this->assertEquals('failed', $i2vGen->fresh()->status);

        // Assert 5 credits refunded
        $this->assertEquals(50, $user->fresh()->credit_balance);

        $refundCount = CreditTransaction::where('reference_id', $i2vGen->id)
            ->where('type', 'generation_refund')
            ->count();
        $this->assertEquals(1, $refundCount);

        // Running job again on already failed record should NOT double-refund
        $job2 = new PollImageToVideoPredictionJob($i2vGen->id, 2, 0);
        $job2->handle($mockWan, $mockR2);
        $this->assertEquals(50, $user->fresh()->credit_balance);
        $this->assertEquals(1, CreditTransaction::where('reference_id', $i2vGen->id)->where('type', 'generation_refund')->count());
    }

    /**
     * Test 4: When Image-to-Video polling job times out after max attempts, credits are refunded idempotently.
     */
    public function test_image_to_video_job_timeout_refunds_credits_idempotently(): void
    {
        $user = User::create([
            'name' => 'Timeout User',
            'email' => 'timeout_refund@example.com',
            'password' => bcrypt('password'),
            'credit_balance' => 50,
        ]);

        $i2vGen = VideoGeneration::create([
            'user_id' => (string) $user->id,
            'generation_type' => 'image-to-video',
            'prediction_id' => 'pred_timeout_111',
            'prompt' => 'Animate sky',
            'status' => 'processing',
            'job_dispatched' => true,
        ]);

        $user->update(['credit_balance' => 45]);

        $mockWan = Mockery::mock(WanImageToVideoService::class);
        $mockR2 = Mockery::mock(R2StorageService::class);

        // Dispatch with attempt > MAX_ATTEMPTS (201 > 200)
        $job = new PollImageToVideoPredictionJob($i2vGen->id, 201, 0);
        $job->handle($mockWan, $mockR2);

        $this->assertEquals('failed', $i2vGen->fresh()->status);
        $this->assertStringContainsString('timed out', $i2vGen->fresh()->error_message);

        // Assert 5 credits refunded
        $this->assertEquals(50, $user->fresh()->credit_balance);
    }
}
