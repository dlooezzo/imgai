<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Credits\AiCreditPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiCreditPricingController extends Controller
{
    protected AiCreditPricingService $pricingService;

    public function __construct(AiCreditPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Display the central AI Credit Pricing management panel with live preview.
     */
    public function index(): View
    {
        $settings = $this->pricingService->getAllSettings();
        $previewMatrix = $this->pricingService->getPreviewMatrix();

        return view('admin.credit-pricing.index', [
            'settings' => $settings,
            'previewMatrix' => $previewMatrix,
        ]);
    }

    /**
     * Validate and update AI Credit Pricing in database (authoritative source of truth).
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Fixed Credit Costs
            'image_generation' => 'required|integer|min:1|max:1000',
            'image_to_video' => 'required|integer|min:1|max:1000',
            'video_base' => 'required|integer|min:1|max:1000',

            // Resolution Multipliers
            'video_resolution_480p' => 'required|integer|min:1|max:50',
            'video_resolution_720p' => 'required|integer|min:1|max:50',
            'video_resolution_1080p' => 'required|integer|min:1|max:50',

            // Duration Multipliers
            'video_duration_5' => 'required|integer|min:1|max:50',
            'video_duration_8' => 'required|integer|min:1|max:50',
            'video_duration_12' => 'required|integer|min:1|max:50',

            // Audio Multipliers
            'video_audio_no' => 'required|integer|min:1|max:50',
            'video_audio_yes' => 'required|integer|min:1|max:50',

            // Default Free Starting Credits
            'default_free_credits' => 'required|integer|min:0|max:100000',
        ], [
            'image_generation.min' => 'Image generation cost must be at least 1 credit.',
            'image_to_video.min' => 'Image-to-Video cost must be at least 1 credit.',
            'video_base.min' => 'Video base cost must be at least 1 credit.',
            'video_resolution_*.min' => 'Resolution multipliers must be at least 1.',
            'video_duration_*.min' => 'Duration multipliers must be at least 1.',
            'video_audio_*.min' => 'Audio multipliers must be at least 1.',
            'default_free_credits.min' => 'Default free credits cannot be negative.',
        ]);

        $this->pricingService->updateSettings($validated);

        return redirect()->route('admin.credit-pricing.index')
            ->with('success', 'AI Credit Pricing updated and persistent cache invalidated successfully.');
    }
}
