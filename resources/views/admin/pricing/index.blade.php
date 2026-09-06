@extends('admin.layouts.app')

@section('title', 'Pricing Plans & Cards Management')
@section('breadcrumb', 'Billing & Plans / Pricing Plans')

@section('content')
<div style="display: flex; flex-direction: column; gap: 28px;">

    <!-- Alerts -->
    @if(session('success'))
        <div style="padding: 14px 18px; border-radius: 10px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
            <i data-lucide="check-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Header Section -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Pricing Plans & Cards
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Manage the tiers displayed on your public pricing page (<a href="{{ route('pricing') }}" target="_blank" style="color: #818cf8; text-decoration: underline;">/pricing</a>), adjust display prices, and customize features.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="{{ route('admin.pricing.create') }}" class="btn-admin btn-admin-primary">
                <i data-lucide="plus" style="width: 15px; height: 15px;"></i>
                <span>Add New Plan Card</span>
            </a>
        </div>
    </div>

    <!-- Plans Grid Showcase Preview -->
    <div>
        <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 14px; color: #f8fafc;">Public Cards Preview</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            @foreach($plans as $plan)
                <div class="admin-card" style="position: relative; display: flex; flex-direction: column; justify-content: space-between; border-color: {{ $plan->is_popular ? 'rgba(168, 85, 247, 0.4)' : 'rgba(255, 255, 255, 0.08)' }}; {{ $plan->is_popular ? 'background: linear-gradient(180deg, rgba(30, 27, 75, 0.3) 0%, rgba(15, 23, 42, 0.8) 100%);' : '' }}">
                    
                    @if($plan->badge || $plan->is_popular)
                        <div style="position: absolute; top: -12px; right: 20px;">
                            <span style="background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%); color: #fff; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 4px 12px; border-radius: 999px; box-shadow: 0 4px 10px rgba(168, 85, 247, 0.3);">
                                {{ $plan->badge ?: 'Studio Choice' }}
                            </span>
                        </div>
                    @endif

                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <h3 style="font-size: 1.35rem; font-weight: 800; color: #fff;">{{ $plan->name }}</h3>
                            <span class="badge" style="background: {{ $plan->is_active ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' }}; color: {{ $plan->is_active ? '#34d399' : '#f87171' }}; border: 1px solid {{ $plan->is_active ? 'rgba(16, 185, 129, 0.3)' : 'rgba(239, 68, 68, 0.3)' }};">
                                {{ $plan->is_active ? 'Active' : 'Hidden' }}
                            </span>
                        </div>

                        <p style="color: #94a3b8; font-size: 0.85rem; line-height: 1.5; min-height: 40px; margin-bottom: 16px;">
                            {{ $plan->description }}
                        </p>

                        <!-- Price display -->
                        <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.06);">
                            <span style="font-size: 1.7rem; font-weight: 800; color: #fff;">{{ $plan->monthly_price ?: '$0' }}</span>
                            <span style="font-size: 0.85rem; color: #94a3b8;">/month</span>
                            @if($plan->yearly_price)
                                <span style="font-size: 0.78rem; color: #38bdf8; margin-left: auto;">(Yearly: {{ $plan->yearly_price }})</span>
                            @endif
                        </div>

                        <!-- Features list -->
                        <div style="margin-bottom: 20px;">
                            <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; margin-bottom: 8px;">
                                Plan Features ({{ count($plan->features ?? []) }})
                            </div>
                            <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 7px;">
                                @foreach(array_slice($plan->features ?? [], 0, 4) as $feat)
                                    <li style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.82rem; color: #cbd5e1;">
                                        <i data-lucide="check" style="width: 14px; height: 14px; color: #34d399; flex-shrink: 0; margin-top: 2px;"></i>
                                        <span>{{ $feat }}</span>
                                    </li>
                                @endforeach
                                @if(count($plan->features ?? []) > 4)
                                    <li style="font-size: 0.75rem; color: #94a3b8; padding-left: 22px;">
                                        + {{ count($plan->features) - 4 }} more features...
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; align-items: center; gap: 8px; padding-top: 14px; border-top: 1px solid rgba(255, 255, 255, 0.06);">
                        <a href="{{ route('admin.pricing.edit', $plan->id) }}" class="btn-admin btn-admin-secondary" style="flex: 1; justify-content: center;">
                            <i data-lucide="edit-2" style="width: 14px; height: 14px;"></i>
                            <span>Edit Card</span>
                        </a>

                        <form method="POST" action="{{ route('admin.pricing.toggle', $plan->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-admin btn-admin-secondary" title="{{ $plan->is_active ? 'Hide Card' : 'Show Card' }}" style="padding: 8px 10px;">
                                <i data-lucide="{{ $plan->is_active ? 'eye-off' : 'eye' }}" style="width: 14px; height: 14px; color: {{ $plan->is_active ? '#fbbf24' : '#34d399' }};"></i>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.pricing.destroy', $plan->id) }}" onsubmit="return confirm('Are you sure you want to delete this pricing plan?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-admin btn-admin-danger" title="Delete Plan" style="padding: 8px 10px;">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
