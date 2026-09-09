<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VideoGeneration extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'generation_type', // 'text-to-video', 'image-to-video'
        'prediction_id',
        'prompt',
        'source_image_url',
        'source_image_path',
        'aspect_ratio',
        'resolution',
        'duration',
        'generate_audio',
        'seed',
        'camerafixed',
        'watermark',
        'width',
        'height',
        'steps',
        'crf',
        'flow_shift',
        'frame_rate',
        'num_frames',
        'guidance_scale',
        'denoise_strength',
        'model_version',
        'status',
        'job_dispatched',
        'poll_attempts',
        'video_path',
        'remote_url',
        'error_message',
        'expires_at',
    ];

    protected $casts = [
        'duration'          => 'integer',
        'generate_audio'    => 'boolean',
        'seed'              => 'integer',
        'camerafixed'       => 'boolean',
        'watermark'         => 'boolean',
        'width'             => 'integer',
        'height'            => 'integer',
        'steps'             => 'integer',
        'crf'               => 'integer',
        'flow_shift'        => 'integer',
        'frame_rate'        => 'integer',
        'num_frames'        => 'integer',
        'guidance_scale'    => 'float',
        'denoise_strength'  => 'float',
        'job_dispatched'    => 'boolean',
        'poll_attempts'     => 'integer',
        'expires_at'        => 'datetime',
    ];

    protected $appends = [
        'video_url',
        'is_expired',
    ];

    /**
     * Get the publicly accessible URL for the generated video.
     */
    public function getVideoUrlAttribute(): ?string
    {
        // 1. If remote_url is an external or R2 HTTPS URL
        if ($this->remote_url && str_starts_with($this->remote_url, 'http')) {
            return $this->remote_url;
        }

        // 2. If stored in Cloudflare R2
        if ($this->video_path && (str_starts_with($this->video_path, 'users/') || str_contains($this->video_path, 'image-to-video/'))) {
            $r2PublicUrl = rtrim(config('filesystems.disks.r2.url', env('R2_PUBLIC_URL', '')), '/');
            if ($r2PublicUrl) {
                return $r2PublicUrl . '/' . ltrim($this->video_path, '/');
            }
        }

        // 3. If stored in local public storage — use request-aware URL so port is correct
        if ($this->video_path) {
            if (app()->runningInConsole() || !request()) {
                return url('storage/' . $this->video_path);
            }
            $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
            return $baseUrl . '/storage/' . $this->video_path;
        }

        return $this->remote_url;
    }

    /**
     * Determine if the generation has expired (>24h).
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Delete storage files when model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (VideoGeneration $videoGen) {
            // Delete R2 or local video file
            if ($videoGen->video_path) {
                try {
                    if (Storage::disk('r2')->exists($videoGen->video_path)) {
                        Storage::disk('r2')->delete($videoGen->video_path);
                    }
                } catch (\Throwable $e) {
                    // Ignore R2 deletion error if offline/unconfigured
                }

                if (Storage::disk('public')->exists($videoGen->video_path)) {
                    Storage::disk('public')->delete($videoGen->video_path);
                }
            }

            // Delete R2 source image file if applicable
            if ($videoGen->source_image_path) {
                try {
                    if (Storage::disk('r2')->exists($videoGen->source_image_path)) {
                        Storage::disk('r2')->delete($videoGen->source_image_path);
                    }
                } catch (\Throwable $e) {
                    // Ignore R2 deletion error
                }
            }
        });
    }
}
