<?php

namespace Tests\Unit;

use App\Services\AI\MagicApiVideoService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MagicApiVideoServiceTest extends TestCase
{
    public function test_create_video_prediction_sends_correct_payload_and_headers(): void
    {
        config(['services.magicapi.key' => 'test_video_key_123']);

        Http::fake([
            '*/predictions' => Http::response([
                'id' => 'video_pred_test_123',
                'status' => 'starting',
                'version' => '6c9132aee14409cd6568d030453f1ba50f5f3412b844fe67f78a9eb62d55664f',
            ], 201),
        ]);

        $service = new MagicApiVideoService();
        $result = $service->createPrediction([
            'prompt' => 'A cinematic drone flight over majestic snowy mountains at sunrise',
            'aspect_ratio' => '16:9',
            'frame_rate' => 24,
            'steps' => 30,
            'guidance_scale' => 6.0,
        ]);

        $this->assertEquals('video_pred_test_123', $result['prediction_id']);
        $this->assertEquals('starting', $result['status']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-api-market-key', 'test_video_key_123') &&
                   $request['input']['prompt'] === 'A cinematic drone flight over majestic snowy mountains at sunrise' &&
                   $request['input']['width'] === 864 &&
                   $request['input']['height'] === 480 &&
                   $request['input']['fps'] === 24 &&
                   $request['input']['infer_steps'] === 30 &&
                   !isset($request['input']['video']); // Strictly NO video input
        });
    }

    public function test_get_video_prediction_status_fetches_output(): void
    {
        config(['services.magicapi.key' => 'test_video_key_123']);

        Http::fake([
            '*/predictions/video_pred_test_123' => Http::response([
                'id' => 'video_pred_test_123',
                'status' => 'succeeded',
                'output' => 'https://example.com/generated_video.mp4',
                'error' => null,
            ], 200),
        ]);

        $service = new MagicApiVideoService();
        $result = $service->getPredictionStatus('video_pred_test_123');

        $this->assertEquals('succeeded', $result['status']);
        $this->assertEquals('https://example.com/generated_video.mp4', $result['output']);
        $this->assertNull($result['error']);
    }

    public function test_cancel_video_prediction(): void
    {
        config(['services.magicapi.key' => 'test_video_key_123']);

        Http::fake([
            '*/predictions/video_pred_test_123/cancel' => Http::response(['status' => 'canceled'], 200),
        ]);

        $service = new MagicApiVideoService();
        $result = $service->cancelPrediction('video_pred_test_123');

        $this->assertTrue($result);
    }
}
