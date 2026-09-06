<?php

namespace App\Services\AI\Contracts;

interface VideoGenerationInterface
{
    /**
     * Submit an asynchronous text-to-video prediction request.
     *
     * @param array $parameters
     * @return array ['prediction_id' => string, 'status' => string, 'raw' => array]
     */
    public function createPrediction(array $parameters): array;

    /**
     * Fetch status and output of an existing video prediction.
     *
     * @param string $predictionId
     * @return array ['id' => string, 'status' => string, 'output' => ?string, 'error' => ?string, 'raw' => array]
     */
    public function getPredictionStatus(string $predictionId): array;

    /**
     * Download the remote generated video and store it in local storage.
     *
     * @param string $remoteUrl
     * @return string Relative local storage path (e.g. videos/{uuid}.mp4)
     */
    public function downloadAndStoreVideo(string $remoteUrl): string;

    /**
     * Attempt to cancel an active video prediction on the provider.
     *
     * @param string $predictionId
     * @return bool
     */
    public function cancelPrediction(string $predictionId): bool;
}
