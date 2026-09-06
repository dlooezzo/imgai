<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Generation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'user_id',
        'prediction_id',
        'prompt',
        'aspect_ratio',
        'megapixels',
        'output_format',
        'output_quality',
        'seed',
        'juiced',
        'model_version',
        'status',
        'image_path',
        'remote_url',
        'error_message',
        'expires_at',
    ];

    protected $casts = [
        'megapixels' => 'integer',
        'output_quality' => 'integer',
        'seed' => 'integer',
        'juiced' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $appends = [
        'image_url',
        'is_expired',
    ];

    /**
     * Get the publicly accessible URL for the generated image.
     * Uses Storage::url() which correctly uses APP_URL, with a request-aware
     * fallback so the image always loads regardless of port/domain differences.
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image_path) {
            // Build a URL using the request's own scheme+host so it works on any port
            if (app()->runningInConsole() || !request()) {
                return url('storage/' . $this->image_path);
            }
            // In a real HTTP context: use the actual request origin so localhost:8000 works
            $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
            return $baseUrl . '/storage/' . $this->image_path;
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
     * Delete local storage file when model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (Generation $generation) {
            if ($generation->image_path && Storage::disk('public')->exists($generation->image_path)) {
                Storage::disk('public')->delete($generation->image_path);
            }
        });
    }
}
