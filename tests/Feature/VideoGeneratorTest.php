<?php

namespace Tests\Feature;

use App\Models\VideoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VideoGeneratorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test video generator page view renders.
     */
    public function test_video_generator_page_is_accessible(): void
    {
        $response = $this->get('/tools/video-generator');

        $response->assertStatus(200);
        $response->assertSee('Text-to-Video Generator');
        $response->assertSee('video-neural-canvas');
    }

    /**
     * Test unauthenticated video generation request returns 401.
     */
    public function test_unauthenticated_user_cannot_generate_videos(): void
    {
        $response = $this->postJson('/tools/video-generator/generate', [
            'prompt' => 'A cinematic video of ocean waves',
            'aspect_ratio' => '16:9',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'requires_auth' => true,
        ]);
    }

    /**
     * Test validation fails when prompt is empty.
     */
    public function test_video_generation_fails_on_empty_prompt(): void
    {
        $this->withSession(['supabase_user_id' => 'test-user-123']);

        $response = $this->postJson('/tools/video-generator/generate', [
            'prompt' => '',
            'aspect_ratio' => '16:9',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }

    /**
     * Test video generation starts successfully for authenticated user.
     */
    public function test_video_generation_starts_successfully(): void
    {
        $this->withSession(['supabase_user_id' => 'test-user-123']);

        Http::fake([
            '*/predictions' => Http::response([
                'id' => 'pred_vid_test_999',
                'status' => 'starting',
            ], 201),
        ]);

        $response = $this->postJson('/tools/video-generator/generate', [
            'prompt' => 'A cinematic video of ocean waves crashing on black basalt cliffs',
            'aspect_ratio' => '16:9',
            'frame_rate' => 24,
            'steps' => 30,
            'denoise_strength' => 0.85,
            'guidance_scale' => 6.0,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'generation' => [
                'id',
                'prompt',
                'aspect_ratio',
                'width',
                'height',
                'status',
                'expires_at',
            ]
        ]);

        $this->assertDatabaseHas('video_generations', [
            'prompt' => 'A cinematic video of ocean waves crashing on black basalt cliffs',
            'aspect_ratio' => '16:9',
        ]);
    }

    /**
     * Test status endpoint returns video generation.
     */
    public function test_video_status_endpoint_returns_generation(): void
    {
        $videoGen = VideoGeneration::create([
            'prompt' => 'A video prompt',
            'aspect_ratio' => '16:9',
            'width' => 1024,
            'height' => 576,
            'status' => 'succeeded',
            'remote_url' => 'https://example.com/video.mp4',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->getJson("/tools/video-generator/status/{$videoGen->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'generation' => [
                'id' => $videoGen->id,
                'status' => 'succeeded',
                'remote_url' => 'https://example.com/video.mp4',
            ]
        ]);
    }

    /**
     * Test cancelling an active video generation.
     */
    public function test_video_generation_can_be_cancelled(): void
    {
        Http::fake([
            '*/predictions/pred_vid_cancel_123/cancel' => Http::response(['status' => 'canceled'], 200),
        ]);

        $userId = 'test-vid-user-1';

        $videoGen = VideoGeneration::create([
            'user_id' => $userId,
            'prediction_id' => 'pred_vid_cancel_123',
            'prompt' => 'Video to cancel',
            'aspect_ratio' => '16:9',
            'status' => 'starting',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->withSession(['supabase_user_id' => $userId])
            ->postJson("/tools/video-generator/cancel/{$videoGen->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'generation' => [
                'id' => $videoGen->id,
                'status' => 'cancelled',
            ]
        ]);

        $this->assertDatabaseHas('video_generations', [
            'id' => $videoGen->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Test deleting a video generation.
     */
    public function test_video_generation_can_be_deleted(): void
    {
        $userId = 'test-vid-user-1';

        $videoGen = VideoGeneration::create([
            'user_id' => $userId,
            'prompt' => 'Delete video test',
            'aspect_ratio' => '16:9',
            'status' => 'succeeded',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->withSession(['supabase_user_id' => $userId])
            ->deleteJson("/tools/video-generator/delete/{$videoGen->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('video_generations', ['id' => $videoGen->id]);
    }
}
