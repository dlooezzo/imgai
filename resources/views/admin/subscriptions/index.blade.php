@extends('admin.layouts.app')

@section('title', 'Subscriptions Management')
@section('breadcrumb', 'Billing / Subscriptions')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Customer Subscriptions
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Monitor active recurring subscriptions, Paddle customer IDs, and renewal statuses.
            </p>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="padding: 8px 14px; border-radius: 10px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.25); color: #34d399; font-size: 0.84rem; font-weight: 700;">
                Active: {{ $stats['active'] }}
            </div>
            <div style="padding: 8px 14px; border-radius: 10px; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.25); color: #f87171; font-size: 0.84rem; font-weight: 700;">
                Canceled: {{ $stats['canceled'] }}
            </div>
            <div style="padding: 8px 14px; border-radius: 10px; background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.25); color: #fbbf24; font-size: 0.84rem; font-weight: 700;">
                Total: {{ $stats['total'] }}
            </div>
        </div>
    </div>

    @if (!empty($migrationWarning))
        <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 12px; padding: 14px 18px; color: #fbbf24; font-size: 0.88rem; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i data-lucide="alert-triangle" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
                <span>{{ $migrationWarning }}</span>
            </div>
            <form method="POST" action="{{ route('admin.settings.migrate') }}">
                @csrf
                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 6px 14px; font-size: 0.82rem;">
                    <i data-lucide="database" style="width: 14px; height: 14px;"></i>
                    <span>Run Migrations Now</span>
                </button>
            </form>
        </div>
    @endif

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="admin-filter-bar">
        <div class="admin-search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by Subscription ID, Customer ID, or user email...">
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <select name="status" class="admin-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="trialing" {{ $status === 'trialing' ? 'selected' : '' }}>Trialing</option>
                <option value="past_due" {{ $status === 'past_due' ? 'selected' : '' }}>Past Due</option>
                <option value="paused" {{ $status === 'paused' ? 'selected' : '' }}>Paused</option>
                <option value="canceled" {{ $status === 'canceled' ? 'selected' : '' }}>Canceled</option>
            </select>

            @if ($search || $status)
                <a href="{{ route('admin.subscriptions.index') }}" class="btn-admin btn-admin-secondary" style="padding: 8px 12px; font-size: 0.82rem;">
                    Clear Filters
                </a>
            @endif
        </div>
    </form>

    <!-- Subscriptions Table -->
    <div class="admin-card" style="padding: 0; overflow: hidden;">
        <div class="admin-table-container" style="border: none; border-radius: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Plan & Cycle</th>
                        <th>Status</th>
                        <th>Paddle Subscription ID</th>
                        <th>Paddle Customer ID</th>
                        <th>Next Billing</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subscriptions as $sub)
                        <tr>
                            <td>
                                @if ($sub->user)
                                    <div>
                                        <a href="{{ route('admin.users.show', $sub->user->id) }}" style="font-weight: 700; color: #f8fafc; text-decoration: none;">
                                            {{ $sub->user->name }}
                                        </a>
                                        <div style="font-size: 0.76rem; color: #94a3b8;">{{ $sub->user->email }}</div>
                                    </div>
                                @else
                                    <span style="color: #64748b; font-style: italic;">Unassigned User</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-weight: 700; color: #c084fc;">
                                    {{ $sub->pricingPlan?->name ?? 'Custom Plan' }}
                                </span>
                                <span style="display: block; font-size: 0.74rem; color: #94a3b8; text-transform: uppercase;">
                                    {{ $sub->billing_period }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $badgeStyle = match($sub->status) {
                                        'active' => 'background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);',
                                        'trialing' => 'background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);',
                                        'past_due' => 'background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3);',
                                        'canceled' => 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);',
                                        default => 'background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.3);',
                                    };
                                @endphp
                                <span style="padding: 3px 10px; border-radius: 9999px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; {{ $badgeStyle }}">
                                    {{ $sub->status }}
                                </span>
                            </td>
                            <td>
                                <code style="font-size: 0.78rem; color: #cbd5e1; background: rgba(0,0,0,0.3); padding: 3px 6px; border-radius: 4px;">
                                    {{ $sub->paddle_subscription_id }}
                                </code>
                            </td>
                            <td>
                                <code style="font-size: 0.78rem; color: #94a3b8;">
                                    {{ $sub->paddle_customer_id ?: '—' }}
                                </code>
                            </td>
                            <td>
                                <span style="font-size: 0.82rem; color: #cbd5e1;">
                                    {{ $sub->next_billed_at ? $sub->next_billed_at->format('M d, Y') : ($sub->canceled_at ? 'Canceled' : '—') }}
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.78rem; color: #94a3b8;">
                                    {{ $sub->created_at->format('M d, Y') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 36px; color: #94a3b8;">
                                No subscriptions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subscriptions->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid rgba(255, 255, 255, 0.06);">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
