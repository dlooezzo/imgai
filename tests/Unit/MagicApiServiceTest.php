<?php

namespace Tests\Unit;

use App\Services\AI\MagicApiImageService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MagicApiServiceTest extends TestCase
{
    public function test_create_prediction_sends_correct_payload_and_headers(): void
    {
        config(['services.magicapi.key' => 'test_api_key_123']);

        Http::fake([
            '*/predictions' => Http::response([
                'id' => 'pred_test_98765',
                'status' => 'starting',
                'version' => '16e15e913fcc71c1a5defb335ea84739f99731fa1ee17995117c7d9adc6d176c',
            ], 201),
        ]);

        $service = new MagicApiImageService();
        $result = $service->createPrediction([
            'prompt' => 'A cinematic cat with white fur',
            'aspect_ratio' => '4:3',
            'megapixels' => 2,
            'output_format' => 'jpg',
            'output_quality' => 80,
            'seed' => 246764,
            'juiced' => false,
        ]);

        $this->assertEquals('pred_test_98765', $result['prediction_id']);
        $this->assertEquals('starting', $result['status']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-api-market-key', 'test_api_key_123') &&
                   $request['input']['prompt'] === 'A cinematic cat with white fur' &&
                   $request['input']['aspect_ratio'] === '4:3' &&
                   $request['input']['megapixels'] === 2 &&
                   $request['input']['seed'] === 246764;
        });
    }

    public function test_get_prediction_status_fetches_output(): void
    {
        config(['services.magicapi.key' => 'test_api_key_123']);

        Http::fake([
            '*/predictions/pred_test_98765' => Http::response([
                'id' => 'pred_test_98765',
                'status' => 'succeeded',
                'output' => 'https://example-link-to-output-image.com/output.jpeg',
                'error' => null,
            ], 200),
        ]);

        $service = new MagicApiImageService();
        $result = $service->getPredictionStatus('pred_test_98765');

        $this->assertEquals('succeeded', $result['status']);
        $this->assertEquals('https://example-link-to-output-image.com/output.jpeg', $result['output']);
        $this->assertNull($result['error']);
    }

    public function test_cancel_prediction_calls_cancel_endpoint(): void
    {
        config(['services.magicapi.key' => 'test_api_key_123']);

        Http::fake([
            '*/predictions/pred_test_98765/cancel' => Http::response(['status' => 'canceled'], 200),
        ]);

        $service = new MagicApiImageService();
        $result = $service->cancelPrediction('pred_test_98765');

        $this->assertTrue($result);
    }
}
