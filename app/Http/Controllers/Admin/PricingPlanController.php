<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PricingPlanController extends Controller
{
    /**
     * Display a listing of pricing plans.
     */
    public function index()
    {
        PricingPlan::ensureDefaults();
        $plans = PricingPlan::orderBy('sort_order', 'asc')->get();

        return view('admin.pricing.index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Show form for creating a new pricing plan.
     */
    public function create()
    {
        return view('admin.pricing.form', [
            'plan' => new PricingPlan([
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => PricingPlan::max('sort_order') + 1,
                'button_text' => 'Get Started',
                'features' => [],
            ]),
            'isEdit' => false,
        ]);
    }

    /**
     * Store a newly created pricing plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:pricing_plans,slug',
            'description' => 'nullable|string|max:1000',
            'badge' => 'nullable|string|max:50',
            'monthly_price' => 'nullable|string|max:50',
            'yearly_price' => 'nullable|string|max:50',
            'monthly_price_id' => 'nullable|string|max:100',
            'yearly_price_id' => 'nullable|string|max:100',
            'monthly_credits' => 'nullable|integer|min:0',
            'yearly_credits' => 'nullable|integer|min:0',
            'features_text' => 'nullable|string',
            'button_text' => 'nullable|string|max:50',
            'is_popular' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $features = [];
        if (!empty($request->input('features_text'))) {
            $features = array_values(array_filter(
                array_map('trim', explode("\n", $request->input('features_text'))),
                fn($line) => strlen($line) > 0
            ));
        }

        $slug = !empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        // Ensure unique slug
        $originalSlug = $slug;
        $count = 1;
        while (PricingPlan::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        PricingPlan::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'badge' => $validated['badge'] ?? null,
            'monthly_price' => $validated['monthly_price'] ?? null,
            'yearly_price' => $validated['yearly_price'] ?? null,
            'monthly_price_id' => $validated['monthly_price_id'] ?? null,
            'yearly_price_id' => $validated['yearly_price_id'] ?? null,
            'monthly_credits' => (int) ($validated['monthly_credits'] ?? 0),
            'yearly_credits' => (int) ($validated['yearly_credits'] ?? 0),
            'features' => $features,
            'button_text' => $validated['button_text'] ?: 'Get Started',
            'is_popular' => $request->has('is_popular'),
            'is_active' => $request->has('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.pricing.index')->with('success', 'Pricing plan card created successfully!');
    }

    /**
     * Show form for editing an existing pricing plan.
     */
    public function edit(PricingPlan $plan)
    {
        return view('admin.pricing.form', [
            'plan' => $plan,
            'isEdit' => true,
        ]);
    }

    /**
     * Update an existing pricing plan.
     */
    public function update(Request $request, PricingPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:pricing_plans,slug,' . $plan->id,
            'description' => 'nullable|string|max:1000',
            'badge' => 'nullable|string|max:50',
            'monthly_price' => 'nullable|string|max:50',
            'yearly_price' => 'nullable|string|max:50',
            'monthly_price_id' => 'nullable|string|max:100',
            'yearly_price_id' => 'nullable|string|max:100',
            'monthly_credits' => 'nullable|integer|min:0',
            'yearly_credits' => 'nullable|integer|min:0',
            'features_text' => 'nullable|string',
            'button_text' => 'nullable|string|max:50',
            'is_popular' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $features = [];
        if (!empty($request->input('features_text'))) {
            $features = array_values(array_filter(
                array_map('trim', explode("\n", $request->input('features_text'))),
                fn($line) => strlen($line) > 0
            ));
        }

        $plan->update([
            'name' => $validated['name'],
            'slug' => !empty($validated['slug']) ? Str::slug($validated['slug']) : $plan->slug,
            'description' => $validated['description'] ?? null,
            'badge' => $validated['badge'] ?? null,
            'monthly_price' => $validated['monthly_price'] ?? null,
            'yearly_price' => $validated['yearly_price'] ?? null,
            'monthly_price_id' => $validated['monthly_price_id'] ?? null,
            'yearly_price_id' => $validated['yearly_price_id'] ?? null,
            'monthly_credits' => (int) ($validated['monthly_credits'] ?? 0),
            'yearly_credits' => (int) ($validated['yearly_credits'] ?? 0),
            'features' => $features,
            'button_text' => $validated['button_text'] ?: 'Get Started',
            'is_popular' => $request->has('is_popular'),
            'is_active' => $request->has('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.pricing.index')->with('success', 'Pricing plan updated successfully!');
    }

    /**
     * Delete a pricing plan.
     */
    public function destroy(PricingPlan $plan)
    {
        $name = $plan->name;
        $plan->delete();

        return redirect()->route('admin.pricing.index')->with('success', "Pricing plan '{$name}' deleted successfully.");
    }

    /**
     * Quick toggle plan active status.
     */
    public function toggle(PricingPlan $plan)
    {
        $plan->update([
            'is_active' => !$plan->is_active,
        ]);

        return back()->with('success', "Status for '{$plan->name}' updated.");
    }
}

