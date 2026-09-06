<?php

namespace App\Services\Storage;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class R2StorageException extends Exception {}

class R2StorageService
{
    protected string $publicUrl;
    protected ?string $bucket;

    public function __construct()
    {
        $this->publicUrl = rtrim((string) config('filesystems.disks.r2.url', env('R2_PUBLIC_URL', '')), '/');
        $this->bucket = config('filesystems.disks.r2.bucket', env('R2_BUCKET'));
    }

    /**
     * Ensure R2 is properly configured in the environment.
     *
     * @throws R2StorageException
     */
    protected function ensureConfigured(): void
    {
        $key = config('filesystems.disks.r2.key', env('R2_ACCESS_KEY_ID'));
        $secret = config('filesystems.disks.r2.secret', env('R2_SECRET_ACCESS_KEY'));
        $endpoint = config('filesystems.disks.r2.endpoint', env('R2_ENDPOINT'));

        // If Storage::fake('r2') is used in testing, bypass credential verification
        if (app()->environment('testing')) {
            return;
        }

        if (empty($key) || empty($secret) || empty($this->bucket) || empty($endpoint) || empty($this->publicUrl)) {
            Log::error('R2StorageService: Missing Cloudflare R2 credentials or configuration in .env');
            throw new R2StorageException('Cloudflare R2 storage is not properly configured on the server. Please check your R2 environment settings.');
        }
    }

    /**
     * Validate an uploaded image file for security, size, and format.
     *
     * @throws R2StorageException
     */
    public function validateImage(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new R2StorageException('The uploaded file was corrupted or incomplete. Please try again.');
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $mime = $file->getMimeType();

        if (!in_array($mime, $allowedMimes, true)) {
            throw new R2StorageException('Invalid image format. Only JPEG, PNG, and WebP images are supported.');
        }

        // Max size 15 MB (15 * 1024 * 1024 bytes)
        $maxBytes = 15 * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            throw new R2StorageException('The image file size exceeds the maximum limit of 15 MB.');
        }

        // Validate that the file is indeed an image using getimagesize
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw new R2StorageException('The uploaded file is not a valid image.');
        }
    }

    /**
     * Upload an image to Cloudflare R2 and return its path and public HTTPS URL.
     *
     * @throws R2StorageException
     */
    public function uploadImage(UploadedFile $file, string $userId): array
    {
        $this->ensureConfigured();
        $this->validateImage($file);

        $sanitizedUserId = preg_replace('/[^a-zA-Z0-9_-]/', '', $userId) ?: 'guest';
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            $extension = 'jpg';
        }

        $uniqueId = (string) Str::uuid();
        $path = "users/{$sanitizedUserId}/image-to-video/images/{$uniqueId}.{$extension}";

        try {
            $contents = file_get_contents($file->getRealPath());
            if ($contents === false) {
                throw new Exception('Failed to read uploaded image data from disk.');
            }

            $disk = Storage::disk('r2');
            $uploaded = $disk->put($path, $contents, 'public');

            if (!$uploaded) {
                throw new Exception('R2 storage put operation returned false.');
            }

            $publicUrl = $this->buildPublicUrl($path);

            Log::info("R2StorageService: Image uploaded successfully to R2 path: {$path}");

            return [
                'path' => $path,
                'url' => $publicUrl,
                'filename' => "{$uniqueId}.{$extension}",
            ];
        } catch (\Throwable $e) {
            Log::error("R2StorageService uploadImage failed: {$e->getMessage()}", [
                'user_id' => $userId,
                'path' => $path,
                'exception' => $e,
            ]);

            throw new R2StorageException('Unable to store source image in Cloudflare R2. Please try again.');
        }
    }

    /**
     * Download the generated video from the provider output URL and store it permanently in Cloudflare R2.
     *
     * @throws R2StorageException
     */
    public function uploadVideoFromUrl(string $remoteUrl, string $userId, string $generationId): array
    {
        $this->ensureConfigured();

        $sanitizedUserId = preg_replace('/[^a-zA-Z0-9_-]/', '', $userId) ?: 'guest';
        $sanitizedGenId = preg_replace('/[^a-zA-Z0-9_-]/', '', $generationId) ?: (string) Str::uuid();
        $path = "users/{$sanitizedUserId}/image-to-video/videos/{$sanitizedGenId}.mp4";

        try {
            Log::info("R2StorageService: Downloading generated video from provider: {$remoteUrl}");

            $response = Http::timeout(120)->connectTimeout(15)->get($remoteUrl);

            if (!$response->successful()) {
                throw new Exception("Failed to fetch video from provider output URL. HTTP Status: {$response->status()}");
            }

            $videoData = $response->body();
            if (empty($videoData)) {
                throw new Exception('Provider returned empty video content.');
            }

            $disk = Storage::disk('r2');
            $uploaded = $disk->put($path, $videoData, 'public');

            if (!$uploaded) {
                throw new Exception('R2 storage put operation for video returned false.');
            }

            $publicUrl = $this->buildPublicUrl($path);

            Log::info("R2StorageService: Video uploaded successfully to R2 path: {$path}");

            return [
                'path' => $path,
                'url' => $publicUrl,
            ];
        } catch (\Throwable $e) {
            Log::error("R2StorageService uploadVideoFromUrl failed: {$e->getMessage()}", [
                'remote_url' => $remoteUrl,
                'generation_id' => $generationId,
                'path' => $path,
                'exception' => $e,
            ]);

            throw new R2StorageException('Unable to download and store the generated video in Cloudflare R2.');
        }
    }

    /**
     * Upload an SEO article image to Cloudflare R2 (or public storage fallback).
     *
     * @throws R2StorageException
     */
    public function uploadArticleImage(UploadedFile $file): array
    {
        $this->validateImage($file);

        $origName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($origName) ?: 'article-image';
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $extension = 'jpg';
        }

        $uniqueId = Str::random(8);
        $fileName = "{$slug}-{$uniqueId}.{$extension}";
        $path = "seo/articles/{$fileName}";

        try {
            $contents = file_get_contents($file->getRealPath());
            if ($contents === false) {
                throw new Exception('Failed to read uploaded image data.');
            }

            // Check if R2 disk is configured and attempt upload
            $hasR2 = !empty(config('filesystems.disks.r2.key')) && !empty(config('filesystems.disks.r2.bucket'));

            if ($hasR2) {
                try {
                    $disk = Storage::disk('r2');
                    $uploaded = $disk->put($path, $contents, 'public');

                    if ($uploaded) {
                        $publicUrl = $this->buildPublicUrl($path);
                        Log::info("R2StorageService: Article image uploaded to Cloudflare R2: {$publicUrl}");

                        return [
                            'path' => $path,
                            'url' => $publicUrl,
                            'filename' => $fileName,
                        ];
                    }
                } catch (\Throwable $r2Ex) {
                    Log::warning("R2StorageService: R2 upload failed, falling back to local public storage: " . $r2Ex->getMessage());
                }
            }

            // Fallback to local public disk storage
            $storedPath = Storage::disk('public')->putFileAs('seo/articles', $file, $fileName);
            $publicUrl = asset('storage/' . $storedPath);

            Log::info("R2StorageService: Article image stored in local public disk: {$publicUrl}");

            return [
                'path' => 'seo/articles/' . $fileName,
                'url' => $publicUrl,
                'filename' => $fileName,
            ];
        } catch (\Throwable $e) {
            Log::error("R2StorageService uploadArticleImage failed: {$e->getMessage()}", [
                'filename' => $fileName ?? null,
                'exception' => $e,
            ]);

            throw new R2StorageException('Unable to upload article image: ' . $e->getMessage());
        }
    }

    /**
     * Upload branding and logo assets (logos, favicons, OG images) supporting SVG, ICO, and standard images.
     *
     * @throws R2StorageException
     */
    public function uploadBrandingAsset(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            throw new R2StorageException('The uploaded file was corrupted or incomplete. Please try again.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico'])) {
            $extension = 'png';
        }

        $origName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($origName) ?: 'brand-asset';
        $uniqueId = Str::random(8);
        $fileName = "{$slug}-{$uniqueId}.{$extension}";
        $path = "branding/{$fileName}";

        try {
            $contents = file_get_contents($file->getRealPath());
            if ($contents === false) {
                throw new Exception('Failed to read uploaded image data.');
            }

            // Check if R2 disk is configured and attempt upload
            $hasR2 = !empty(config('filesystems.disks.r2.key')) && !empty(config('filesystems.disks.r2.bucket'));

            if ($hasR2) {
                try {
                    $disk = Storage::disk('r2');
                    $uploaded = $disk->put($path, $contents, 'public');

                    if ($uploaded) {
                        $publicUrl = $this->buildPublicUrl($path);
                        Log::info("R2StorageService: Branding asset uploaded to Cloudflare R2: {$publicUrl}");

                        return [
                            'path' => $path,
                            'url' => $publicUrl,
                            'filename' => $fileName,
                        ];
                    }
                } catch (\Throwable $r2Ex) {
                    Log::warning("R2StorageService: R2 branding upload failed, falling back to local public storage: " . $r2Ex->getMessage());
                }
            }

            // Fallback to local public disk storage
            $storedPath = Storage::disk('public')->putFileAs('branding', $file, $fileName);
            $publicUrl = asset('storage/' . $storedPath);

            Log::info("R2StorageService: Branding asset stored in local public disk: {$publicUrl}");

            return [
                'path' => 'branding/' . $fileName,
                'url' => $publicUrl,
                'filename' => $fileName,
            ];
        } catch (\Throwable $e) {
            Log::error("R2StorageService uploadBrandingAsset failed: {$e->getMessage()}", [
                'filename' => $fileName ?? null,
                'exception' => $e,
            ]);

            throw new R2StorageException('Unable to upload branding asset: ' . $e->getMessage());
        }
    }

    /**
     * Delete a file from Cloudflare R2.
     */
    public function deleteFile(string $path): bool
    {
        try {
            if (Storage::disk('r2')->exists($path)) {
                return Storage::disk('r2')->delete($path);
            }
            return false;
        } catch (\Throwable $e) {
            Log::warning("R2StorageService deleteFile failed: {$e->getMessage()}", ['path' => $path]);
            return false;
        }
    }

    /**
     * Construct the public HTTPS URL for an R2 storage path.
     */
    public function buildPublicUrl(string $path): string
    {
        if (!empty($this->publicUrl)) {
            return $this->publicUrl . '/' . ltrim($path, '/');
        }

        return Storage::disk('r2')->url($path);
    }
}
