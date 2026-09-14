<?php

namespace App\Console\Commands;

use App\Services\AI\SeedanceVideoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestSeedanceApi extends Command
{
    protected $signature = 'seedance:test 
        {--prompt= : Custom prompt (default: test prompt)}
        {--resolution=480p : Resolution (480p|720p|1080p)}
        {--ratio=16:9 : Aspect ratio}
        {--duration=5 : Duration in seconds (4-12)}
        {--generate_audio=false : Generate audio (true|false)}
        {--seed= : Random seed}
        {--camerafixed=false : Fixed camera (true|false)}
        {--watermark=false : Watermark (true|false)}
        {--poll_interval=5 : Poll interval in seconds}
        {--max_polls=120 : Maximum number of polls (default 120 = 10 min at 5s interval)}';

    protected $description = 'Test Seedance 1.5 Pro API directly - submit generation and poll status';

    public function handle(): int
    {
        $prompt = $this->option('prompt') ?: 'A person walking slowly through a beautiful city street at sunset, cinematic camera movement.';
        
        $parameters = [
            'prompt'         => $prompt,
            'resolution'     => $this->option('resolution'),
            'ratio'          => $this->option('ratio'),
            'duration'       => (int) $this->option('duration'),
            'generate_audio' => filter_var($this->option('generate_audio'), FILTER_VALIDATE_BOOLEAN),
            'seed'           => $this->option('seed') ? (int) $this->option('seed') : null,
            'camerafixed'    => filter_var($this->option('camerafixed'), FILTER_VALIDATE_BOOLEAN),
            'watermark'      => filter_var($this->option('watermark'), FILTER_VALIDATE_BOOLEAN),
        ];

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  Seedance 1.5 Pro API Diagnostic Test');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // Check configuration
        $baseUrl = config('services.magicapi.seedance_video_base_url') ?: env('SEEDANCE_VIDEO_BASE_URL');
        $apiKey = config('services.magicapi.key') ?: env('API_MARKET_KEY');

        $this->info('Configuration:');
        $this->line("  Base URL: {$baseUrl}");
        $this->line("  API Key: " . ($apiKey ? 'SET (masked: ' . substr($apiKey, 0, 8) . '...)' : 'NOT SET'));
        $this->newLine();

        if (empty($baseUrl) || empty($apiKey) || str_contains($apiKey, 'YOUR_API_MARKET_KEY')) {
            $this->error('Missing configuration. Set SEEDANCE_VIDEO_BASE_URL and API_MARKET_KEY in .env');
            return 1;
        }

        // Test submit
        $this->info('Step 1: Submitting generation request...');
        $this->line("  Prompt: {$parameters['prompt']}");
        $this->line("  Resolution: {$parameters['resolution']}");
        $this->line("  Ratio: {$parameters['ratio']}");
        $this->line("  Duration: {$parameters['duration']}s");
        $this->line("  Generate Audio: " . ($parameters['generate_audio'] ? 'Yes' : 'No'));
        $this->newLine();

        try {
            $service = new SeedanceVideoService();
            $result = $service->createPrediction($parameters);

            $this->info('✓ Submit Response:');
            $this->line("  HTTP Status: {$result['http_status']}");
            $this->line("  Prediction ID: {$result['prediction_id']}");
            $this->line("  Status: {$result['status']}");
            $this->line("  Raw Response: " . json_encode($result['raw'] ?? []));
            $this->newLine();

            if (!isset($result['prediction_id']) || empty($result['prediction_id'])) {
                $this->error('No job_id returned from submit!');
                return 1;
            }

            $jobId = $result['prediction_id'];
            $this->info("Step 2: Polling status for job_id: {$jobId}");
            $this->newLine();

            $maxPolls = (int) $this->option('max_polls');
            $pollInterval = (int) $this->option('poll_interval');

            for ($poll = 1; $poll <= $maxPolls; $poll++) {
                $this->line("  Poll #{$poll}...");

                $statusResult = $service->getPredictionStatus($jobId);

                $this->line("    HTTP Status: {$statusResult['http_status']}");
                $this->line("    Status: {$statusResult['status']}");
                $this->line("    Output URL: " . ($statusResult['output'] ?? 'null'));
                $this->line("    Error: " . ($statusResult['error'] ?? 'none'));
                $this->line("    Raw: " . json_encode($statusResult['raw'] ?? []));

                if (in_array($statusResult['status'], ['failed', 'error'], true)) {
                    $this->error("Generation FAILED: {$statusResult['error']}");
                    return 1;
                }

                if ($statusResult['status'] === 'succeeded' && !empty($statusResult['output'])) {
                    $this->info("✓ Generation SUCCEEDED!");
                    $this->line("  Video URL: {$statusResult['output']}");
                    $this->newLine();

                    // Test download
                    $this->info('Step 3: Testing video download...');
                    try {
                        $localPath = $service->downloadAndStoreVideo($statusResult['output']);
                        $this->info("  ✓ Downloaded and stored at: {$localPath}");
                    } catch (\Exception $e) {
                        $this->error("  Download failed: {$e->getMessage()}");
                    }
                    return 0;
                }

                if ($poll < $maxPolls) {
                    $this->line("    Waiting {$pollInterval}s before next poll...");
                    sleep($pollInterval);
                }
            }

            $this->error("Timeout: Max polls ({$maxPolls}) reached without completion.");
            return 1;

        } catch (\Exception $e) {
            $this->error("Exception: {$e->getMessage()}");
            $this->line("Trace: {$e->getTraceAsString()}");
            return 1;
        }
    }
}