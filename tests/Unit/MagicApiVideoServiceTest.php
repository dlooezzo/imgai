<?php

namespace Tests\Unit;

use App\Services\AI\SeedanceVideoService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SeedanceVideoServiceTest extends TestCase
{
    public function test_create_video_prediction_sends_correct_payload_and_headers(): void
    {
        config(['services.magicapi.key' => 'test_video_key_123']);
        config(['services.magicapi.seedance_video_base_url' => 'https://prod.api.market/api/v1/byteplus/seedance-text-to-video-1-5-pro']);

        Http::fake([
            '*/text-to-video-1-5-pro/run' => Http::response([
                'id'    => 'pred_vid_test_123',
                'status' => 'submitted',
                'version' => 'text-to-video-1-5-pro',
            ], 201),
        ]);

        $service = new SeedanceVideoService();
        $result = $service->createPrediction([
            'prompt'         => 'A cinematic drone flight over majestic snowy mountains at sunrise',
            'aspect_ratio'   => '16:9',
            'resolution'     => '720p',
            'ratio'          => '16:9',
            'duration'       => 5,
            'generate_audio' => false,
        ]);

        $this->assertEquals('pred_vid_test_123', $result['prediction_id']);
        $this->assertEquals('submitted', $result['status']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-api-market-key', 'test_video_key_123') &&
                   $request['input']['prompt'] === 'A cinematic drone flight over majestic snowy mountains at sunrise' &&
                   $request['input']['resolution'] === '720p' &&
                   $request['input']['ratio'] === '16:9' &&
                   $request['input']['duration'] === 5 &&
                   $request['input']['generate_audio'] === false;
        });
    }

    public function test_get_video_prediction_status_fetches_output(): void
    {
        config(['services.magicapi.key' => 'test_video_key_123']);
        config(['services.magicapi.seedance_video_base_url' => 'https://prod.api.market/api/v1/byteplus/seedance-text-to-video-1-5-pro']);

        Http::fake([
            '*/text-to-video-1-5-pro/status/pred_vid_test_123' => Http::response([
                'id'     => 'pred_vid_test_123',
                'status' => 'succeeded',
                'output' => ['video_url' => 'https://example.com/generated_video.mp4'],
                'error'  => null,
            ], 200),
        ]);

        $service = new SeedanceVideoService();
        $result = $service->getPredictionStatus('pred_vid_test_123');

        $this->assertEquals('succeeded', $result['status']);
        $this->assertEquals('https://example.com/generated_video.mp4', $result['output']);
        $this->assertNull($result['error']);
    }

    public function test_cancel_video_prediction(): void
    {
        config(['services.magicapi.key' => 'test_video_key_123']);
        config(['services.magicapi.seedance_video_base_url' => 'https://prod.api.market/api/v1/byteplus/seedance-text-to-video-1-5-pro']);

        $service = new SeedanceVideoService();

        // Seedance API does not document a cancel endpoint, so cancelPrediction returns true
        $result = $service->cancelPrediction('pred_vid_test_123');

        $this->assertTrue($result);
    }
}
