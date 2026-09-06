<?php

namespace App\Services\AI\Contracts;

interface ImageGenerationInterface
{
    /**
     * Submit an asynchronous text-to-image prediction request.
     *
     * @param array $parameters
     * @return array ['prediction_id' => string, 'status' => string, 'raw' => array]
     */
    public function createPrediction(array $parameters): array;

    /**
     * Fetch status and output of an existing prediction.
     *
     * @param string $predictionId
     * @return array ['id' => string, 'status' => string, 'output' => ?string, 'error' => ?string, 'raw' => array]
     */
    public function getPredictionStatus(string $predictionId): array;

    /**
     * Download the remote image and store it in server local storage.
     *
     * @param string $remoteUrl
     * @param string $format
     * @return string Relative local storage path (e.g. generations/{uuid}.jpg)
     */
    public function downloadAndStoreImage(string $remoteUrl, string $format = 'jpg'): string;

    /**
     * Attempt to cancel an active prediction on the provider.
     *
     * @param string $predictionId
     * @return bool
     */
    public function cancelPrediction(string $predictionId): bool;
}
