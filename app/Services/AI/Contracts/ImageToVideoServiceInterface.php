<?php

namespace App\Services\AI\Contracts;

interface ImageToVideoServiceInterface
{
    /**
     * Create an asynchronous Image-to-Video prediction on the external provider.
     *
     * @param array $parameters [image, prompt, resolution, aspect_ratio, num_frames, frames_per_second, etc.]
     * @return array [id, status, version, created_at, ...]
     */
    public function createPrediction(array $parameters): array;

    /**
     * Get prediction details and status from the provider.
     *
     * @param string $predictionId
     * @return array [id, status, output, error, created_at, started_at, completed_at]
     */
    public function getPredictionStatus(string $predictionId): array;

    /**
     * Cancel an ongoing prediction if supported.
     *
     * @param string $predictionId
     * @return bool
     */
    public function cancelPrediction(string $predictionId): bool;
}
