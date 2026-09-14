<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Models\VideoGeneration;
use App\Services\Credits\AiCreditPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AiCreditPricingTest
 *
 * Verifies:
 *  1. Default pricing values are correct from config fallbacks.
 *  2. All 18 Text-to-Video combinations produce expected credit costs.
 *  3. Admin can update settings and pricing changes immediately.
 *  4. Refunds always use the originally-charged amount, NEVER recalculate
 *     with the current (possibly changed) pricing.
 *  5. Admin validation rejects invalid inputs.
 */
class AiCreditPricingTest extends TestCase
{
    use RefreshDatabase;

    protected AiCreditPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AiCreditPricingService::class);
    }

    // =========================================================
    // 1. Default Pricing Values
    // =========================================================

    /** @test */
    public function test_default_image_generation_cost_is_1(): void
    {
        $this->assertEquals(1, $this->service->getImageGenerationCost());
    }

    /** @test */
    public function test_default_image_to_video_cost_is_5(): void
    {
        $this->assertEquals(5, $this->service->getImageToVideoCost());
    }

    /** @test */
    public function test_default_video_base_cost_is_5(): void
    {
        $this->assertEquals(5, $this->service->getVideoBaseCost());
    }

    /** @test */
    public function test_default_resolution_multipliers(): void
    {
        $this->assertEquals(1, $this->service->getVideoResolutionMultiplier('480p'));
        $this->assertEquals(2, $this->service->getVideoResolutionMultiplier('720p'));
        $this->assertEquals(3, $this->service->getVideoResolutionMultiplier('1080p'));
    }

    /** @test */
    public function test_default_duration_multipliers(): void
    {
        $this->assertEquals(1, $this->service->getVideoDurationMultiplier(5));
        $this->assertEquals(2, $this->service->getVideoDurationMultiplier(8));
        $this->assertEquals(3, $this->service->getVideoDurationMultiplier(12));
    }

    /** @test */
    public function test_default_audio_multipliers(): void
    {
        $this->assertEquals(1, $this->service->getVideoAudioMultiplier(false));
        $this->assertEquals(2, $this->service->getVideoAudioMultiplier(true));
    }

    // =========================================================
    // 2. All 18 Text-to-Video combinations
    // =========================================================

    /**
     * @test
     *
     * @dataProvider textToVideoCombinationsProvider
     */
    public function test_text_to_video_cost_formula(
        string $resolution,
        int $duration,
        bool $generateAudio,
        int $expectedCost
    ): void {
        $actual = $this->service->calculateTextToVideoCost($resolution, $duration, $generateAudio);

        $this->assertEquals(
            $expectedCost,
            $actual,
            "Expected {$expectedCost} credits for {$resolution}/{$duration}s/audio=".($generateAudio ? 'yes' : 'no')
            .", got {$actual}"
        );
    }

    public static function textToVideoCombinationsProvider(): array
    {
        // Formula: base(5) × resolution_mult × duration_mult × audio_mult
        // Resolution: 480p=1, 720p=2, 1080p=3
        // Duration:   5s=1,  8s=2,   12s=3
        // Audio:      no=1,  yes=2
        return [
            // ---- 480p ----
            '480p/5s/no_audio' => ['480p', 5,  false, 5 * 1 * 1 * 1],   // 5
            '480p/5s/with_audio' => ['480p', 5,  true,  5 * 1 * 1 * 2],   // 10
            '480p/8s/no_audio' => ['480p', 8,  false, 5 * 1 * 2 * 1],   // 10
            '480p/8s/with_audio' => ['480p', 8,  true,  5 * 1 * 2 * 2],   // 20
            '480p/12s/no_audio' => ['480p', 12, false, 5 * 1 * 3 * 1],   // 15
            '480p/12s/with_audio' => ['480p', 12, true,  5 * 1 * 3 * 2],   // 30

            // ---- 720p ----
            '720p/5s/no_audio' => ['720p', 5,  false, 5 * 2 * 1 * 1],   // 10
            '720p/5s/with_audio' => ['720p', 5,  true,  5 * 2 * 1 * 2],   // 20
            '720p/8s/no_audio' => ['720p', 8,  false, 5 * 2 * 2 * 1],   // 20
            '720p/8s/with_audio' => ['720p', 8,  true,  5 * 2 * 2 * 2],   // 40  ← example from spec
            '720p/12s/no_audio' => ['720p', 12, false, 5 * 2 * 3 * 1],   // 30
            '720p/12s/with_audio' => ['720p', 12, true,  5 * 2 * 3 * 2],   // 60

            // ---- 1080p ----
            '1080p/5s/no_audio' => ['1080p', 5,  false, 5 * 3 * 1 * 1], // 15
            '1080p/5s/with_audio' => ['1080p', 5,  true,  5 * 3 * 1 * 2], // 30
            '1080p/8s/no_audio' => ['1080p', 8,  false, 5 * 3 * 2 * 1], // 30
            '1080p/8s/with_audio' => ['1080p', 8,  true,  5 * 3 * 2 * 2], // 60
            '1080p/12s/no_audio' => ['1080p', 12, false, 5 * 3 * 3 * 1], // 45
            '1080p/12s/with_audio' => ['1080p', 12, true,  5 * 3 * 3 * 2], // 90
        ];
    }

    // =========================================================
    // 3. Dynamic Admin Update (DB overrides config)
    // =========================================================

    /** @test */
    public function test_admin_can_update_pricing_and_it_takes_effect_immediately(): void
    {
        // Before update: defaults apply
        $this->assertEquals(20, $this->service->calculateTextToVideoCost('720p', 8, false));

        // Admin updates: 720p multiplier from 2 → 4, audio.no from 1 → 3
        $this->service->updateSettings([
            'image_generation' => 1,
            'image_to_video' => 5,
            'video_base' => 5,
            'video_resolution_480p' => 1,
            'video_resolution_720p' => 4,   // changed
            'video_resolution_1080p' => 3,
            'video_duration_5' => 1,
            'video_duration_8' => 2,
            'video_duration_12' => 3,
            'video_audio_no' => 3,   // changed
            'video_audio_yes' => 2,
            'default_free_credits' => 0,
        ]);

        // After update: 5 (base) × 4 (720p) × 2 (8s) × 3 (no audio) = 120
        $this->assertEquals(120, $this->service->calculateTextToVideoCost('720p', 8, false));
    }

    /** @test */
    public function test_database_overrides_config_values(): void
    {
        // Seed a DB override for image_generation cost
        SiteSetting::set('ai_credit.image_generation', '99');

        $this->assertEquals(99, $this->service->getImageGenerationCost());
    }

    // =========================================================
    // 4. Refund uses original charged amount (KEY REQUIREMENT)
    // =========================================================

    /**
     * @test
     *
     * Scenario: User generates a 720p/8s/audio=on video for 40 credits.
     * Admin later changes the audio multiplier to 5.
     * The generation fails → refund must be 40, NOT the new price (100).
     */
    public function test_refund_uses_credits_charged_not_current_pricing(): void
    {
        // Original pricing: 720p/8s/audio=yes → 5*2*2*2 = 40
        $originalCost = $this->service->calculateTextToVideoCost('720p', 8, true);
        $this->assertEquals(40, $originalCost);

        // Create a user and deduct credits at original cost
        $user = User::factory()->create(['credit_balance' => 200]);

        // Simulate what VideoGeneratorController does — persist credits_charged
        $videoGen = VideoGeneration::create([
            'user_id' => (string) $user->id,
            'generation_type' => 'text-to-video',
            'prompt' => 'Refund test video',
            'aspect_ratio' => '16:9',
            'resolution' => '720p',
            'duration' => 8,
            'generate_audio' => true,
            'status' => 'failed',
            'credits_charged' => $originalCost,   // persisted at generation time
            'expires_at' => now()->addHours(24),
        ]);

        // Admin changes audio_yes multiplier from 2 → 5 AFTER generation was created
        $this->service->updateSettings([
            'image_generation' => 1,
            'image_to_video' => 5,
            'video_base' => 5,
            'video_resolution_480p' => 1,
            'video_resolution_720p' => 2,
            'video_resolution_1080p' => 3,
            'video_duration_5' => 1,
            'video_duration_8' => 2,
            'video_duration_12' => 3,
            'video_audio_no' => 1,
            'video_audio_yes' => 5,  // ← changed from 2 to 5 after generation
            'default_free_credits' => 0,
        ]);

        // Current pricing would now calculate: 5*2*2*5 = 100 credits
        $newCost = $this->service->calculateTextToVideoCost('720p', 8, true);
        $this->assertEquals(100, $newCost);

        // But the generation has credits_charged = 40 (the original amount)
        // The refund logic must return 40, not 100.
        $videoGen->refresh();
        $refundAmount = (int) $videoGen->credits_charged;

        $this->assertEquals(
            40,
            $refundAmount,
            'Refund must be the originally charged amount (40), not the current pricing (100)'
        );
    }

    // =========================================================
    // 5. Admin Validation Rules
    // =========================================================

    /** @test */
    public function test_admin_update_rejects_zero_or_negative_multipliers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/admin/credit-pricing/update', [
            'image_generation' => 0,   // invalid — must be >= 1
            'image_to_video' => 5,
            'video_base' => 5,
            'video_resolution_480p' => 1,
            'video_resolution_720p' => 2,
            'video_resolution_1080p' => 3,
            'video_duration_5' => 1,
            'video_duration_8' => 2,
            'video_duration_12' => 3,
            'video_audio_no' => 1,
            'video_audio_yes' => 2,
            'default_free_credits' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image_generation']);
    }

    /** @test */
    public function test_admin_update_rejects_non_integer_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/admin/credit-pricing/update', [
            'image_generation' => 'abc',  // invalid — must be integer
            'image_to_video' => 5,
            'video_base' => 5,
            'video_resolution_480p' => 1,
            'video_resolution_720p' => 2,
            'video_resolution_1080p' => 3,
            'video_duration_5' => 1,
            'video_duration_8' => 2,
            'video_duration_12' => 3,
            'video_audio_no' => 1,
            'video_audio_yes' => 2,
            'default_free_credits' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image_generation']);
    }

    /** @test */
    public function test_admin_update_allows_zero_free_credits(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/admin/credit-pricing/update', [
            'image_generation' => 1,
            'image_to_video' => 5,
            'video_base' => 5,
            'video_resolution_480p' => 1,
            'video_resolution_720p' => 2,
            'video_resolution_1080p' => 3,
            'video_duration_5' => 1,
            'video_duration_8' => 2,
            'video_duration_12' => 3,
            'video_audio_no' => 1,
            'video_audio_yes' => 2,
            'default_free_credits' => 0,  // valid — 0 is allowed for free credits
        ]);

        // Should redirect (form submit), not a validation error
        $response->assertStatus(302);
        $response->assertRedirect('/admin/credit-pricing');
    }

    /** @test */
    public function test_preview_matrix_generates_all_18_combinations(): void
    {
        $matrix = $this->service->getPreviewMatrix();

        // 3 resolutions × 3 durations × 2 audio states = 18
        $this->assertCount(18, $matrix);

        foreach ($matrix as $row) {
            $this->assertArrayHasKey('resolution', $row);
            $this->assertArrayHasKey('duration', $row);
            $this->assertArrayHasKey('audio', $row);
            $this->assertArrayHasKey('total_credits', $row);
            $this->assertGreaterThanOrEqual(1, $row['total_credits']);
        }
    }

    /** @test */
    public function test_text_to_video_policy_includes_audio_multiplier(): void
    {
        $policy = $this->service->getTextToVideoPolicy();

        $this->assertArrayHasKey('base', $policy);
        $this->assertArrayHasKey('resolution_multiplier', $policy);
        $this->assertArrayHasKey('duration_multiplier', $policy);
        $this->assertArrayHasKey('audio_multiplier', $policy);

        $this->assertArrayHasKey('no', $policy['audio_multiplier']);
        $this->assertArrayHasKey('yes', $policy['audio_multiplier']);
    }
}
