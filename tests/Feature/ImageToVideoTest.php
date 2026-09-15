<?php

namespace Tests\Feature;

use App\Jobs\PollImageToVideoPredictionJob;
use App\Models\User;
use App\Models\VideoGeneration;
use App\Services\AI\SeedanceImageToVideoService;
use App\Services\Storage\R2StorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageToVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'filesystems.disks.r2.key'                            => 'test-key',
            'filesystems.disks.r2.secret'                         => 'test-secret',
            'filesystems.disks.r2.bucket'                         => 'test-bucket',
            'filesystems.disks.r2.endpoint'                       => 'https://test.r2.cloudflarestorage.com',
            'filesystems.disks.r2.url'                            => 'https://pub-test.r2.dev',
            'services.magicapi.key'                               => 'test-api-market-key',
            'services.magicapi.seedance_image_to_video_base_url'  => 'https://prod.api.market/api/v1/byteplus/seedance-image-to-video-pro-fast',
            'services.magicapi.image_to_video_version'            => 'image-to-video-pro-fast',
        ]);
    }

    // =========================================================================
    // UI / Route Tests
    // =========================================================================

    public function test_image_to_video_workspace_renders_successfully(): void
    {
        $response = $this->get(route('tools.image-to-video.index'));
        $response->assertStatus(200);
        $response->assertSee('Image to Video Generator');
        $response->assertSee('Duration');
        $response->assertSee('Watermark');
        $response->assertSee('Seed');
        $response->assertSee('Fixed Camera');
        $response->assertSee('i2v-neural-canvas');
    }

    // =========================================================================
    // Authentication & Validation Tests
    // =========================================================================

    public function test_unauthenticated_user_cannot_generate_image_to_video(): void
    {
        $response = $this->postJson(route('tools.image-to-video.generate'), [
            'prompt' => 'A flying bird',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'requires_auth' => true,
        ]);
    }

    public function test_image_to_video_validation_rules(): void
    {
        $this->withSession(['supabase_user_id' => 'test-user-123']);

        $response = $this->postJson(route('tools.image-to-video.generate'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image', 'prompt']);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $response = $this->postJson(route('tools.image-to-video.generate'), [
            'image'  => $file,
            'prompt' => 'A flying bird',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    // =========================================================================
    // Generation Creation Tests
    // =========================================================================

    public function test_image_to_video_generation_creation_flow(): void
    {
        // Create a user with enough credits
        $user = User::factory()->create([
            'credit_balance' => 100,
        ]);
        $this->withSession(['supabase_user_id' => $user->id]);

        Storage::fake('r2');
        Queue::fake();

        $baseUrl = config('services.magicapi.seedance_image_to_video_base_url');

        Http::fake([
            "{$baseUrl}/image-to-video-pro-fast/run" => Http::response([
                'id'         => 'pred_test_12345',
                'version'    => 'image-to-video-pro-fast',
                'status'     => 'submitted',
                'created_at' => now()->toIso8601String(),
            ], 201),
            'https://pub-test.r2.dev/*' => Http::response([], 200),
        ]);

        $jpegBytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
        $imageFile = UploadedFile::fake()->createWithContent('nature.jpg', $jpegBytes, 'image/jpeg');

        $response = $this->postJson(route('tools.image-to-video.generate'), [
            'image'       => $imageFile,
            'prompt'      => 'Camera glides over a glowing forest with floating fireflies in 4k',
            'ratio'       => '16:9',
            'resolution'  => '720p',
            'duration'    => 5,
            'watermark'   => false,
            'seed'        => -1,
            'camerafixed' => false,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('prediction_id', 'pred_test_12345');
        $response->assertJsonPath('generation.generation_type', 'image-to-video');
        $response->assertJsonPath('generation.status', 'submitted');

        // Generation exists in DB with correct values
        $this->assertDatabaseHas('video_generations', [
            'prediction_id'   => 'pred_test_12345',
            'generation_type' => 'image-to-video',
            'resolution'      => '720p',
            'ratio'           => '16:9',
            'duration'        => 5,
            'watermark'       => 0,
            'seed'            => -1,
            'camerafixed'     => 0,
            'credits_charged' => 5,
            'status'          => 'submitted',
            'job_dispatched'  => 1, // true — job was dispatched after prediction created
        ]);

        // R2 source image was uploaded
        $generation = VideoGeneration::where('prediction_id', 'pred_test_12345')->first();
        $this->assertNotNull($generation->source_image_path);
        Storage::disk('r2')->assertExists($generation->source_image_path);

        // Polling job was dispatched
        Queue::assertPushed(PollImageToVideoPredictionJob::class, fn($job) => $job->generationId === $generation->id);

        Http::assertSent(function ($request) use ($baseUrl) {
            if ($request->url() !== "{$baseUrl}/image-to-video-pro-fast/run") {
                return false;
            }

            return array_keys($request['input']) === [
                'prompt', 'image_url', 'resolution', 'ratio', 'duration', 'watermark', 'seed', 'camerafixed',
            ] && ! array_intersect([
                'num_frames', 'frames_per_second', 'fps', 'video_frames', 'width', 'height', 'frames',
            ], array_keys($request['input']));
        });
    }

    public function test_image_to_video_accepts_true_boolean_options(): void
    {
        $user = User::factory()->create(['credit_balance' => 100]);
        $this->withSession(['supabase_user_id' => $user->id]);

        Storage::fake('r2');
        Queue::fake();

        $baseUrl = config('services.magicapi.seedance_image_to_video_base_url');
        Http::fake([
            "{$baseUrl}/image-to-video-pro-fast/run" => Http::response([
                'id' => 'pred_test_true_options',
                'status' => 'submitted',
            ], 201),
            'https://pub-test.r2.dev/*' => Http::response([], 200),
        ]);

        $jpegBytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
        $imageFile = UploadedFile::fake()->createWithContent('nature.jpg', $jpegBytes, 'image/jpeg');
        $response = $this->postJson(route('tools.image-to-video.generate'), [
            'image' => $imageFile,
            'prompt' => 'Slow natural camera movement',
            'watermark' => true,
            'camerafixed' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('video_generations', [
            'prediction_id' => 'pred_test_true_options',
            'watermark' => 1,
            'camerafixed' => 1,
        ]);
    }

    // =========================================================================
    // Status Endpoint Tests
    // =========================================================================

    public function test_image_to_video_status_reads_exclusively_from_database(): void
    {
        $generation = VideoGeneration::create([
            'user_id'         => 'test-user-session',
            'generation_type' => 'image-to-video',
            'prediction_id'   => 'pred_status_check',
            'prompt'          => 'Hypercar on highway',
            'ratio'           => '16:9',
            'resolution'      => '720p',
            'status'          => 'processing',
            'job_dispatched'  => true, // already dispatched — no self-heal needed
        ]);

        $this->withSession(['supabase_user_id' => 'test-user-session']);

        // Prevent any external HTTP calls to verify zero provider calls
        Http::preventStrayRequests();

        $response = $this->getJson(route('tools.image-to-video.status', $generation->id));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('generation.id', $generation->id);
        $response->assertJsonPath('generation.status', 'processing');
    }

    public function test_status_endpoint_self_heals_orphaned_generation(): void
    {
        Queue::fake();

        $generation = VideoGeneration::create([
            'user_id'         => 'test-user-session',
            'generation_type' => 'image-to-video',
            'prediction_id'   => 'pred_orphaned',
            'prompt'          => 'Orphaned generation',
            'ratio'           => '16:9',
            'resolution'      => '720p',
            'status'          => 'processing',
            'job_dispatched'  => false, // Orphaned — job was lost (e.g. worker restart)
            'expires_at'      => now()->addHours(23),
        ]);

        $this->withSession(['supabase_user_id' => 'test-user-session']);

        Http::preventStrayRequests();

        $response = $this->getJson(route('tools.image-to-video.status', $generation->id));

        $response->assertStatus(200);
        $response->assertJsonPath('generation.status', 'processing');

        // Self-heal: a new polling job should have been dispatched
        Queue::assertPushed(PollImageToVideoPredictionJob::class, fn($job) => $job->generationId === $generation->id);

        // job_dispatched should now be true
        $this->assertDatabaseHas('video_generations', [
            'id'             => $generation->id,
            'job_dispatched' => 1,
        ]);
    }

    // =========================================================================
    // Polling Job Tests
    // =========================================================================

    public function test_poll_job_marks_succeeded_and_uploads_video_to_r2(): void
    {
        Storage::fake('r2');
        Queue::fake();

        $generation = VideoGeneration::create([
            'user_id'         => 'user_123',
            'generation_type' => 'image-to-video',
            'prediction_id'   => 'pred_full_flow',
            'prompt'          => 'A boat sailing through waves at golden hour',
            'ratio'           => '16:9',
            'resolution'      => '720p',
            'status'          => 'starting',
            'job_dispatched'  => true,
        ]);

        $baseUrl = config('services.magicapi.seedance_image_to_video_base_url');

        Http::fake([
            "{$baseUrl}/image-to-video-pro-fast/status/pred_full_flow" => Http::response([
                'id'     => 'pred_full_flow',
                'status' => 'succeeded',
                'output' => ['video_url' => 'https://mock-provider-cdn.com/videos/output_123.mp4'],
            ], 200),
            'https://mock-provider-cdn.com/videos/output_123.mp4' => Http::response(
                'MOCK_MP4_VIDEO_BINARY_STREAM_DATA', 200, ['Content-Type' => 'video/mp4']
            ),
        ]);

        // Run a single poll iteration — it should detect succeeded and finish
        $job = new PollImageToVideoPredictionJob($generation->id, attemptNumber: 1);
        $job->handle(app(SeedanceImageToVideoService::class), app(R2StorageService::class));

        $generation->refresh();

        $this->assertEquals('succeeded', $generation->status);
        $this->assertNotNull($generation->video_path);
        $this->assertStringContainsString('users/user_123/image-to-video/videos/', $generation->video_path);
        $this->assertStringContainsString('https://pub-test.r2.dev/', $generation->video_url);
        $this->assertFalse((bool) $generation->job_dispatched);

        Storage::disk('r2')->assertExists($generation->video_path);

        // No re-dispatch should occur once succeeded
        Queue::assertNotPushed(PollImageToVideoPredictionJob::class);
    }

    public function test_poll_job_maps_completed_status_to_success_and_uploads_video_to_r2(): void
    {
        Storage::fake('r2');
        Queue::fake();

        $generation = VideoGeneration::create([
            'user_id' => 'user_completed',
            'generation_type' => 'image-to-video',
            'prediction_id' => 'pred_completed',
            'prompt' => 'A slow cinematic camera track',
            'ratio' => '16:9',
            'resolution' => '480p',
            'status' => 'processing',
            'job_dispatched' => true,
        ]);

        $baseUrl = config('services.magicapi.seedance_image_to_video_base_url');
        Http::fake([
            "{$baseUrl}/image-to-video-pro-fast/status/pred_completed" => Http::response([
                'id' => 'pred_completed',
                'status' => 'COMPLETED',
                'output' => ['video_url' => 'https://mock-provider-cdn.com/videos/completed.mp4'],
            ], 200),
            'https://mock-provider-cdn.com/videos/completed.mp4' => Http::response(
                'MOCK_COMPLETED_MP4_VIDEO', 200, ['Content-Type' => 'video/mp4']
            ),
        ]);

        (new PollImageToVideoPredictionJob($generation->id, attemptNumber: 15))
            ->handle(app(SeedanceImageToVideoService::class), app(R2StorageService::class));

        $generation->refresh();
        $this->assertSame('succeeded', $generation->status);
        $this->assertNotNull($generation->video_path);
        $this->assertStringContainsString('https://pub-test.r2.dev/', $generation->video_url);
        $this->assertFalse((bool) $generation->job_dispatched);
        Storage::disk('r2')->assertExists($generation->video_path);
        Queue::assertNotPushed(PollImageToVideoPredictionJob::class);
    }

    public function test_poll_job_re_dispatches_when_still_processing(): void
    {
        Queue::fake();
        Storage::fake('r2');

        $generation = VideoGeneration::create([
            'user_id'         => 'user_requeue',
            'generation_type' => 'image-to-video',
            'prediction_id'   => 'pred_still_running',
            'prompt'          => 'Still processing video',
            'ratio'           => '16:9',
            'resolution'      => '720p',
            'status'          => 'starting',
            'job_dispatched'  => true,
        ]);

        $baseUrl = config('services.magicapi.seedance_image_to_video_base_url');

        Http::fake([
            "{$baseUrl}/image-to-video-pro-fast/status/pred_still_running" => Http::response([
                'id'     => 'pred_still_running',
                'status' => 'processing',
            ], 200),
        ]);

        $job = new PollImageToVideoPredictionJob($generation->id, attemptNumber: 1);
        $job->handle(app(SeedanceImageToVideoService::class), app(R2StorageService::class));

        // Should re-dispatch itself for the next poll
        Queue::assertPushed(PollImageToVideoPredictionJob::class, function ($job) use ($generation) {
            return $job->generationId === $generation->id && $job->attemptNumber === 2;
        });

        // DB status should update to processing
        $this->assertEquals('processing', $generation->fresh()->status);
    }

    public function test_poll_job_maps_seedance_in_progress_status_to_processing(): void
    {
        Queue::fake();
        Storage::fake('r2');

        $generation = VideoGeneration::create([
            'user_id' => 'user_in_progress',
            'generation_type' => 'image-to-video',
            'prediction_id' => 'pred_in_progress',
            'prompt' => 'Slow camera movement',
            'ratio' => '16:9',
            'resolution' => '480p',
            'status' => 'starting',
            'job_dispatched' => true,
        ]);

        $baseUrl = config('services.magicapi.seedance_image_to_video_base_url');
        Http::fake([
            "{$baseUrl}/image-to-video-pro-fast/status/pred_in_progress" => Http::response([
                'id' => 'pred_in_progress',
                'status' => 'IN_PROGRESS',
            ], 200),
        ]);

        (new PollImageToVideoPredictionJob($generation->id, attemptNumber: 1))
            ->handle(app(SeedanceImageToVideoService::class), app(R2StorageService::class));

        Queue::assertPushed(PollImageToVideoPredictionJob::class);
        $this->assertSame('processing', $generation->fresh()->status);
    }

    public function test_poll_job_preserves_provider_error_and_marks_generation_failed(): void
    {
        Queue::fake();
        Storage::fake('r2');

        $generation = VideoGeneration::create([
            'user_id'         => 'user_resilience',
            'generation_type' => 'image-to-video',
            'prediction_id'   => 'pred_resilience',
            'prompt'          => 'Drone flying through misty mountains',
            'ratio'           => '16:9',
            'resolution'      => '720p',
            'status'          => 'starting',
            'job_dispatched'  => true,
        ]);

        $baseUrl = config('services.magicapi.seedance_image_to_video_base_url');

        // Simulate a 404 transient error on first poll
        Http::fake([
            "{$baseUrl}/image-to-video-pro-fast/status/pred_resilience" => Http::response(['message' => 'Not found yet'], 404),
        ]);

        $job = new PollImageToVideoPredictionJob($generation->id, attemptNumber: 3);
        $job->handle(app(SeedanceImageToVideoService::class), app(R2StorageService::class));

        Queue::assertNothingPushed();
        $this->assertSame('failed', $generation->fresh()->status);
        $this->assertStringContainsString('Not found yet', $generation->fresh()->error_message);
    }

    public function test_poll_job_marks_failed_after_max_attempts(): void
    {
        Queue::fake();
        Storage::fake('r2');

        $generation = VideoGeneration::create([
            'user_id'         => 'user_timeout',
            'generation_type' => 'image-to-video',
            'prediction_id'   => 'pred_timeout',
            'prompt'          => 'Timeouted video',
            'status'          => 'processing',
            'job_dispatched'  => true,
        ]);

        // Attempt 201 (over MAX_ATTEMPTS = 200): should mark failed and stop
        $job = new PollImageToVideoPredictionJob($generation->id, attemptNumber: 201);
        $job->handle(app(SeedanceImageToVideoService::class), app(R2StorageService::class));

        $this->assertEquals('failed', $generation->fresh()->status);
        Queue::assertNotPushed(PollImageToVideoPredictionJob::class);
    }

    public function test_poll_job_stops_immediately_for_cancelled_generation(): void
    {
        Queue::fake();
        Storage::fake('r2');
        Http::preventStrayRequests(); // No HTTP calls should happen

        $generation = VideoGeneration::create([
            'user_id'         => 'user_cancel_test',
            'generation_type' => 'image-to-video',
            'prediction_id'   => 'pred_cancel_123',
            'prompt'          => 'Cancelled generation',
            'status'          => 'cancelled',
            'job_dispatched'  => false,
        ]);

        Log::info('[CANCELLED]', [
            'Local Generation ID' => $generation->id,
            'Prediction ID'       => $generation->prediction_id,
            'Status'              => $generation->status,
        ]);

        $job = new PollImageToVideoPredictionJob($generation->id, attemptNumber: 1);
        $job->handle(app(SeedanceImageToVideoService::class), app(R2StorageService::class));

        // Should exit immediately — no re-dispatch, still cancelled
        Queue::assertNotPushed(PollImageToVideoPredictionJob::class);
        $this->assertEquals('cancelled', $generation->fresh()->status);
    }

    // =========================================================================
    // Cancellation and Deletion Tests
    // =========================================================================

    public function test_image_to_video_cancellation_and_deletion(): void
    {
        Storage::fake('r2');

        $generation = VideoGeneration::create([
            'user_id'           => 'user_abc',
            'generation_type'   => 'image-to-video',
            'prediction_id'     => 'pred_to_cancel',
            'prompt'            => 'A spaceship warping through space',
            'source_image_path' => 'users/user_abc/image-to-video/images/test.jpg',
            'video_path'        => 'users/user_abc/image-to-video/videos/test.mp4',
            'status'            => 'processing',
            'job_dispatched'    => true,
        ]);

        Storage::disk('r2')->put($generation->source_image_path, 'fake image data');
        Storage::disk('r2')->put($generation->video_path, 'fake video data');

        $this->withSession(['supabase_user_id' => 'user_abc']);

        Http::fake(['*' => Http::response(['success' => true], 200)]);

        // Cancel
        $cancelResponse = $this->postJson(route('tools.image-to-video.cancel', $generation->id));
        $cancelResponse->assertStatus(200);
        $this->assertEquals('cancelled', $generation->fresh()->status);
        $this->assertFalse((bool) $generation->fresh()->job_dispatched);

        // Delete
        $deleteResponse = $this->deleteJson(route('tools.image-to-video.destroy', $generation->id));
        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('video_generations', ['id' => $generation->id]);
    }
}
