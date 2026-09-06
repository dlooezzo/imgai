<?php

namespace App\Console\Commands;

use App\Models\Generation;
use App\Models\VideoGeneration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredGenerations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generations:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up AI generated images, videos, and database records older than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting 24-hour cleanup of expired AI generations...');

        // Cleanup expired images
        $expiredGenerations = Generation::where('expires_at', '<=', now())
            ->orWhere(function ($query) {
                $query->whereNull('expires_at')
                      ->where('created_at', '<=', now()->subHours(24));
            })
            ->get();

        $imageCount = 0;
        foreach ($expiredGenerations as $generation) {
            if ($generation->image_path && Storage::disk('public')->exists($generation->image_path)) {
                Storage::disk('public')->delete($generation->image_path);
            }
            $generation->delete();
            $imageCount++;
        }

        // Cleanup expired videos
        $expiredVideos = VideoGeneration::where('expires_at', '<=', now())
            ->orWhere(function ($query) {
                $query->whereNull('expires_at')
                      ->where('created_at', '<=', now()->subHours(24));
            })
            ->get();

        $videoCount = 0;
        foreach ($expiredVideos as $videoGen) {
            if ($videoGen->video_path && Storage::disk('public')->exists($videoGen->video_path)) {
                Storage::disk('public')->delete($videoGen->video_path);
            }
            $videoGen->delete();
            $videoCount++;
        }

        $this->info("Cleaned up {$imageCount} expired image(s) and {$videoCount} expired video(s).");

        return Command::SUCCESS;
    }
}
