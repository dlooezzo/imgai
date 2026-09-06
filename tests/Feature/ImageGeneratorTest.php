<?php

namespace Tests\Feature;

use App\Models\Generation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImageGeneratorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test root / redirects directly to /tools.
     */
    public function test_root_redirects_to_tools(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/tools');
    }

    /**
     * Test /tools view renders successfully.
     */
    public function test_tools_page_is_accessible(): void
    {
        $response = $this->get('/tools');

        $response->assertStatus(200);
        $response->assertSee('Cinematic Studio');
        $response->assertSee('Text-to-Image Generator');
        $response->assertSee('Wan 2.2 Cinematic');
    }

    /**
     * Test unauthenticated generation request returns 401 unauthorized.
     */
    public function test_unauthenticated_user_cannot_generate_images(): void
    {
        $response = $this->postJson('/tools/image-generator/generate', [
            'prompt' => 'A beautiful cinematic landscape',
            'aspect_ratio' => '16:9',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'requires_auth' => true,
        ]);
    }

    /**
     * Test validation rules for image generation request.
     */
    public function test_generation_validation_fails_on_empty_prompt(): void
    {
        $this->withSession(['supabase_user_id' => 'test-user-123']);

        $response = $this->postJson('/tools/image-generator/generate', [
            'prompt' => '',
            'aspect_ratio' => '1:1',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }

    /**
     * Test invalid aspect ratio is rejected.
     */
    public function test_generation_validation_fails_on_invalid_aspect_ratio(): void
    {
        $this->withSession(['supabase_user_id' => 'test-user-123']);

        $response = $this->postJson('/tools/image-generator/generate', [
            'prompt' => 'A beautiful cinematic landscape',
            'aspect_ratio' => '99:99',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['aspect_ratio']);
    }

    /**
     * Test image generation starts properly and returns record for authenticated user.
     */
    public function test_image_generation_starts_successfully(): void
    {
        $this->withSession(['supabase_user_id' => 'test-user-123']);

        Http::fake([
            '*/predictions' => Http::response([
                'id' => 'pred_test_start_123',
                'status' => 'starting',
            ], 201),
        ]);

        $response = $this->postJson('/tools/image-generator/generate', [
            'prompt' => 'A photorealistic neon cyberpunk city street in 8k',
            'aspect_ratio' => '16:9',
            'megapixels' => 2,
            'output_format' => 'jpg',
            'output_quality' => 85,
            'juiced' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'generation' => [
                'id',
                'prompt',
                'aspect_ratio',
                'status',
                'expires_at',
            ]
        ]);

        $this->assertDatabaseHas('generations', [
            'prompt' => 'A photorealistic neon cyberpunk city street in 8k',
            'aspect_ratio' => '16:9',
        ]);
    }

    /**
     * Test status endpoint retrieves generation record.
     */
    public function test_status_endpoint_returns_generation(): void
    {
        $generation = Generation::create([
            'prompt' => 'A test prompt',
            'aspect_ratio' => '1:1',
            'megapixels' => 2,
            'output_format' => 'jpg',
            'output_quality' => 80,
            'status' => 'succeeded',
            'remote_url' => 'https://example.com/test.jpg',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->getJson("/tools/image-generator/status/{$generation->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'generation' => [
                'id' => $generation->id,
                'status' => 'succeeded',
                'remote_url' => 'https://example.com/test.jpg',
            ]
        ]);
    }

    /**
     * Test cancelling an active generation.
     */
    public function test_generation_can_be_cancelled(): void
    {
        Http::fake([
            '*/predictions/pred_cancel_123/cancel' => Http::response(['status' => 'canceled'], 200),
        ]);

        $userId = 'test-user-uuid-1';

        $generation = Generation::create([
            'user_id' => $userId,
            'prediction_id' => 'pred_cancel_123',
            'prompt' => 'Cancel test prompt',
            'aspect_ratio' => '1:1',
            'status' => 'starting',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->withSession(['supabase_user_id' => $userId])
            ->postJson("/tools/image-generator/cancel/{$generation->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'generation' => [
                'id' => $generation->id,
                'status' => 'cancelled',
            ]
        ]);

        $this->assertDatabaseHas('generations', [
            'id' => $generation->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Test polling does not overwrite cancelled generation.
     */
    public function test_polling_does_not_overwrite_cancelled_generation(): void
    {
        $generation = Generation::create([
            'prediction_id' => 'pred_cancelled_456',
            'prompt' => 'Already cancelled',
            'aspect_ratio' => '1:1',
            'status' => 'cancelled',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->getJson("/tools/image-generator/status/{$generation->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'generation' => [
                'id' => $generation->id,
                'status' => 'cancelled',
            ]
        ]);

        // Status remains cancelled
        $this->assertEquals('cancelled', $generation->fresh()->status);
    }

    /**
     * Test deleting a generation.
     */
    public function test_generation_can_be_deleted(): void
    {
        $userId = 'test-user-uuid-1';

        $generation = Generation::create([
            'user_id' => $userId,
            'prompt' => 'Delete me',
            'aspect_ratio' => '1:1',
            'status' => 'succeeded',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->withSession(['supabase_user_id' => $userId])
            ->deleteJson("/tools/image-generator/delete/{$generation->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('generations', ['id' => $generation->id]);
    }

    /**
     * Test cleanup command deletes expired records.
     */
    public function test_cleanup_command_removes_expired_generations(): void
    {
        $expired = Generation::create([
            'prompt' => 'Expired image',
            'aspect_ratio' => '1:1',
            'status' => 'succeeded',
            'expires_at' => now()->subHours(2),
        ]);

        $fresh = Generation::create([
            'prompt' => 'Active image',
            'aspect_ratio' => '1:1',
            'status' => 'succeeded',
            'expires_at' => now()->addHours(20),
        ]);

        $this->artisan('generations:cleanup')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('generations', ['id' => $expired->id]);
        $this->assertDatabaseHas('generations', ['id' => $fresh->id]);
    }
}
