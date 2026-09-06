<?php

namespace App\Http\Controllers;

use App\Models\PricingPlan;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PricingController extends Controller
{
    /**
     * Display the pricing page with dynamic plans.
     */
    public function index(Request $request)
    {
        // 1. Prefill customer email and user ID if signed in
        $authUser = Auth::user();
        $userEmail = $authUser?->email ?: session('supabase_user.email') ?: null;
        $userId = $authUser ? (string) $authUser->id : (session('supabase_user_id') ?: null);
        $isLoggedIn = !empty($userId);

        // 2. Load dynamic pricing plans from the database (auto-seeding defaults if empty)
        PricingPlan::ensureDefaults();
        $dbPlans = PricingPlan::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        $tiers = $dbPlans->map(function ($plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description ?? '',
                'badge' => $plan->badge,
                'monthly_price' => $plan->monthly_price ?: '$0',
                'yearly_price' => $plan->yearly_price ?: '$0',
                'monthly_price_id' => $plan->monthly_price_id ?? '',
                'yearly_price_id' => $plan->yearly_price_id ?? '',
                'monthly_credits' => (int) ($plan->monthly_credits ?: 0),
                'yearly_credits' => (int) ($plan->yearly_credits ?: 0),
                'features' => is_array($plan->features) ? $plan->features : [],
                'isPopular' => (bool) $plan->is_popular,
                'buttonText' => $plan->button_text ?: 'Subscribe',
            ];
        })->toArray();

        return view('pricing.index', [
            'userEmail' => $userEmail,
            'userId' => $userId,
            'isLoggedIn' => $isLoggedIn,
            'paddleClientToken' => config('services.paddle.client_token'),
            'paddleEnv' => config('services.paddle.environment', 'sandbox'),
            'tiers' => $tiers,
        ]);

    }

    /**
     * Display the post-checkout / confirmation page.
     */
    public function welcome(Request $request)
    {
        $siteTitle = SiteSetting::get('site_title', config('app.name', 'IMGAI'));
        $userEmail = Auth::user()?->email ?: session('supabase_user.email') ?: null;

        return view('pages.welcome', [
            'siteTitle' => $siteTitle,
            'userEmail' => $userEmail,
        ]);
    }
}

