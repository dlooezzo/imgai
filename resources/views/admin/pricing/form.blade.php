@extends('admin.layouts.app')

@section('title', $isEdit ? 'Edit Pricing Plan — ' . $plan->name : 'Create New Pricing Plan')
@section('breadcrumb', 'Billing & Plans / ' . ($isEdit ? 'Edit Plan' : 'New Plan'))

@section('content')
<div style="max-width: 960px; margin: 0 auto; display: flex; flex-direction: column; gap: 28px;">

    <!-- Header Section -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                {{ $isEdit ? 'Edit Pricing Plan: ' . $plan->name : 'Create New Pricing Plan' }}
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Configure the plan presentation, features, and display pricing.
            </p>
        </div>

        <a href="{{ route('admin.pricing.index') }}" class="btn-admin btn-admin-secondary">
            <i data-lucide="arrow-left" style="width: 15px; height: 15px;"></i>
            <span>Back to Plans</span>
        </a>
    </div>

    <!-- Errors -->
    @if (isset($errors) && $errors->any())
        <div style="padding: 14px 18px; border-radius: 10px; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; font-size: 0.88rem;">
            <strong>Please correct the following errors:</strong>
            <ul style="margin: 8px 0 0 16px; padding: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Card -->
    <form method="POST" action="{{ $isEdit ? route('admin.pricing.update', $plan->id) : route('admin.pricing.store') }}" class="admin-card" style="display: flex; flex-direction: column; gap: 24px;">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
            <!-- Plan Name -->
            <div class="admin-form-group">
                <label class="admin-label" for="name">Plan Title *</label>
                <input type="text" id="name" name="name" class="admin-input" value="{{ old('name', $plan->name) }}" placeholder="e.g. Starter, Pro, Enterprise" required>
            </div>

            <!-- Slug -->
            <div class="admin-form-group">
                <label class="admin-label" for="slug">URL Slug / Identifier</label>
                <input type="text" id="slug" name="slug" class="admin-input" value="{{ old('slug', $plan->slug) }}" placeholder="e.g. starter, pro-studio (auto-generated if blank)">
            </div>
        </div>

        <!-- Tagline / Description -->
        <div class="admin-form-group">
            <label class="admin-label" for="description">Short Description / Subtitle</label>
            <textarea id="description" name="description" class="admin-textarea" rows="2" placeholder="Brief summary of who this plan is for and core benefits...">{{ old('description', $plan->description) }}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
            <!-- Badge -->
            <div class="admin-form-group">
                <label class="admin-label" for="badge">Accent Badge (Optional)</label>
                <input type="text" id="badge" name="badge" class="admin-input" value="{{ old('badge', $plan->badge) }}" placeholder="e.g. Studio Choice, Most Popular, Best Value">
            </div>

            <!-- Button text -->
            <div class="admin-form-group">
                <label class="admin-label" for="button_text">Button Label</label>
                <input type="text" id="button_text" name="button_text" class="admin-input" value="{{ old('button_text', $plan->button_text ?: 'Get Started') }}" placeholder="e.g. Get Started, Upgrade Now">
            </div>
        </div>

        <!-- Pricing Amounts -->
        <div style="padding: 18px; border-radius: 12px; background: rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.06); display: flex; flex-direction: column; gap: 18px;">
            <div style="font-weight: 700; font-size: 0.95rem; color: #f8fafc; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="tag" style="width: 16px; height: 16px; color: #818cf8;"></i>
                <span>Plan Pricing</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                <!-- Monthly Price Display -->
                <div class="admin-form-group">
                    <label class="admin-label" for="monthly_price">Monthly Display Price</label>
                    <input type="text" id="monthly_price" name="monthly_price" class="admin-input" value="{{ old('monthly_price', $plan->monthly_price) }}" placeholder="e.g. $19 or 19">
                    <span style="font-size: 0.74rem; color: #94a3b8; margin-top: 4px; display: block;">Shown when monthly billing is selected.</span>
                </div>

                <!-- Yearly Price Display -->
                <div class="admin-form-group">
                    <label class="admin-label" for="yearly_price">Yearly Display Price</label>
                    <input type="text" id="yearly_price" name="yearly_price" class="admin-input" value="{{ old('yearly_price', $plan->yearly_price) }}" placeholder="e.g. $190 or 190">
                    <span style="font-size: 0.74rem; color: #94a3b8; margin-top: 4px; display: block;">Shown when annual billing is selected.</span>
                </div>
            </div>
        </div>

        <!-- Paddle Billing & Credits Configuration -->
        <div style="padding: 18px; border-radius: 12px; background: rgba(88, 28, 135, 0.15); border: 1px solid rgba(168, 85, 247, 0.3); display: flex; flex-direction: column; gap: 18px;">
            <div style="font-weight: 700; font-size: 0.95rem; color: #f8fafc; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="credit-card" style="width: 16px; height: 16px; color: #c084fc;"></i>
                <span>Paddle Billing & Generation Credits</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                <!-- Monthly Paddle Price ID -->
                <div class="admin-form-group">
                    <label class="admin-label" for="monthly_price_id">Monthly Paddle Price ID</label>
                    <input type="text" id="monthly_price_id" name="monthly_price_id" class="admin-input" value="{{ old('monthly_price_id', $plan->monthly_price_id) }}" placeholder="e.g. pri_01h8abc..." style="font-family: monospace;">
                    <span style="font-size: 0.74rem; color: #94a3b8; margin-top: 4px; display: block;">From your Paddle Catalog (Price ID for monthly recurring).</span>
                </div>

                <!-- Yearly Paddle Price ID -->
                <div class="admin-form-group">
                    <label class="admin-label" for="yearly_price_id">Yearly Paddle Price ID</label>
                    <input type="text" id="yearly_price_id" name="yearly_price_id" class="admin-input" value="{{ old('yearly_price_id', $plan->yearly_price_id) }}" placeholder="e.g. pri_01h8xyz..." style="font-family: monospace;">
                    <span style="font-size: 0.74rem; color: #94a3b8; margin-top: 4px; display: block;">From your Paddle Catalog (Price ID for annual recurring).</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                <!-- Monthly Credits -->
                <div class="admin-form-group">
                    <label class="admin-label" for="monthly_credits">Monthly Credits Granted *</label>
                    <input type="number" id="monthly_credits" name="monthly_credits" class="admin-input" value="{{ old('monthly_credits', $plan->monthly_credits ?: 0) }}" min="0" required>
                    <span style="font-size: 0.74rem; color: #94a3b8; margin-top: 4px; display: block;">Credits allocated to the user per monthly billing cycle.</span>
                </div>

                <!-- Yearly Credits -->
                <div class="admin-form-group">
                    <label class="admin-label" for="yearly_credits">Yearly Credits Granted *</label>
                    <input type="number" id="yearly_credits" name="yearly_credits" class="admin-input" value="{{ old('yearly_credits', $plan->yearly_credits ?: 0) }}" min="0" required>
                    <span style="font-size: 0.74rem; color: #94a3b8; margin-top: 4px; display: block;">Credits allocated to the user for annual subscriptions.</span>
                </div>
            </div>
        </div>


        <!-- Features List -->
        <div class="admin-form-group">
            <label class="admin-label" for="features_text">Plan Features (One feature per line)</label>
            <textarea id="features_text" name="features_text" class="admin-textarea" rows="7" placeholder="500 monthly fast generation credits&#10;Text-to-Image & Image-to-Video models&#10;Standard GPU cloud queue priority&#10;Up to 1080p Full-HD rendering&#10;Commercial project license">{{ old('features_text', is_array($plan->features) ? implode("\n", $plan->features) : '') }}</textarea>
            <span style="font-size: 0.76rem; color: #94a3b8; margin-top: 4px; display: block;">Enter each feature item on a new line. These are rendered with checkmarks on the card.</span>
        </div>

        <!-- Display & Toggle Options -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: center; padding: 14px 0; border-top: 1px solid rgba(255, 255, 255, 0.06); border-bottom: 1px solid rgba(255, 255, 255, 0.06);">
            <!-- Sort Order -->
            <div class="admin-form-group">
                <label class="admin-label" for="sort_order">Display Sort Order</label>
                <input type="number" id="sort_order" name="sort_order" class="admin-input" value="{{ old('sort_order', $plan->sort_order ?: 0) }}" style="width: 120px;">
            </div>

            <!-- Popular Choice Toggle -->
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none;">
                <input type="checkbox" name="is_popular" value="1" {{ old('is_popular', $plan->is_popular) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: #818cf8;">
                <div>
                    <div style="font-weight: 700; font-size: 0.88rem; color: #f8fafc;">Featured / Studio Choice</div>
                    <div style="font-size: 0.74rem; color: #94a3b8;">Highlights card with purple glow effect</div>
                </div>
            </label>

            <!-- Active Status Toggle -->
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none;">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: #34d399;">
                <div>
                    <div style="font-weight: 700; font-size: 0.88rem; color: #f8fafc;">Active on Public Pricing Page</div>
                    <div style="font-size: 0.74rem; color: #94a3b8;">Uncheck to hide without deleting</div>
                </div>
            </label>
        </div>

        <!-- Submit & Actions -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; margin-top: 10px;">
            <a href="{{ route('admin.pricing.index') }}" class="btn-admin btn-admin-secondary">
                Cancel
            </a>
            <button type="submit" class="btn-admin btn-admin-primary">
                <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                <span>{{ $isEdit ? 'Save Changes' : 'Create Pricing Card' }}</span>
            </button>
        </div>

    </form>

</div>
@endsection
