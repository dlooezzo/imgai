@extends('admin.layouts.app')

@section('title', 'Credit Ledger')
@section('breadcrumb', 'Billing / Credit Ledger')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Credit Transactions Ledger
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Audit immutable credit grants, generation deductions, and balance histories.
            </p>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="padding: 8px 14px; border-radius: 10px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.25); color: #34d399; font-size: 0.84rem; font-weight: 700;">
                Granted: +{{ number_format($stats['total_granted']) }}
            </div>
            <div style="padding: 8px 14px; border-radius: 10px; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.25); color: #f87171; font-size: 0.84rem; font-weight: 700;">
                Deducted: -{{ number_format($stats['total_deducted']) }}
            </div>
            <div style="padding: 8px 14px; border-radius: 10px; background: rgba(148, 163, 184, 0.12); border: 1px solid rgba(148, 163, 184, 0.25); color: #94a3b8; font-size: 0.84rem; font-weight: 700;">
                Entries: {{ $stats['total_entries'] }}
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
    <form method="GET" action="{{ route('admin.credits.index') }}" class="admin-filter-bar">
        <div class="admin-search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by reference ID, description, or user email...">
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <select name="type" class="admin-select" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="subscription_grant" {{ $type === 'subscription_grant' ? 'selected' : '' }}>Subscription Grant</option>
                <option value="generation_deduction" {{ $type === 'generation_deduction' ? 'selected' : '' }}>Generation Deduction</option>
                <option value="generation_refund" {{ $type === 'generation_refund' ? 'selected' : '' }}>Generation Refund</option>
                <option value="admin_adjustment" {{ $type === 'admin_adjustment' ? 'selected' : '' }}>Admin Adjustment</option>
            </select>

            <select name="source" class="admin-select" onchange="this.form.submit()">
                <option value="">All Sources</option>
                <option value="paddle_webhook" {{ $source === 'paddle_webhook' ? 'selected' : '' }}>Paddle Webhook</option>
                <option value="image_generation" {{ $source === 'image_generation' ? 'selected' : '' }}>Image Generation</option>
                <option value="video_generation" {{ $source === 'video_generation' ? 'selected' : '' }}>Video Generation</option>
                <option value="image_to_video" {{ $source === 'image_to_video' ? 'selected' : '' }}>Image to Video</option>
                <option value="admin" {{ $source === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>

            @if ($search || $type || $source || $userId)
                <a href="{{ route('admin.credits.index') }}" class="btn-admin btn-admin-secondary" style="padding: 8px 12px; font-size: 0.82rem;">
                    Clear Filters
                </a>
            @endif
        </div>
    </form>

    <!-- Credit Transactions Table -->
    <div class="admin-card" style="padding: 0; overflow: hidden;">
        <div class="admin-table-container" style="border: none; border-radius: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Type & Source</th>
                        <th>Description</th>
                        <th>Reference</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($creditTransactions as $entry)
                        <tr>
                            <td>
                                @if ($entry->user)
                                    <a href="{{ route('admin.users.show', $entry->user->id) }}" style="font-weight: 700; color: #f8fafc; text-decoration: none;">
                                        {{ $entry->user->name }}
                                    </a>
                                    <div style="font-size: 0.76rem; color: #94a3b8;">{{ $entry->user->email }}</div>
                                @else
                                    <span style="color: #64748b;">User #{{ $entry->user_id }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($entry->amount > 0)
                                    <span style="font-weight: 800; color: #34d399; font-size: 0.95rem;">
                                        +{{ number_format($entry->amount) }}
                                    </span>
                                @else
                                    <span style="font-weight: 800; color: #f87171; font-size: 0.95rem;">
                                        {{ number_format($entry->amount) }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span style="font-weight: 700; color: #e2e8f0;">
                                    {{ number_format($entry->balance_after) }}
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; color: #a855f7;">
                                    {{ str_replace('_', ' ', $entry->type) }}
                                </span>
                                <div style="font-size: 0.72rem; color: #94a3b8;">
                                    Via: {{ str_replace('_', ' ', $entry->source) }}
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 0.82rem; color: #cbd5e1;">
                                    {{ $entry->description }}
                                </span>
                            </td>
                            <td>
                                @if ($entry->reference_id)
                                    <code style="font-size: 0.74rem; color: #818cf8; background: rgba(0,0,0,0.3); padding: 2px 5px; border-radius: 4px;">
                                        {{ Str::limit($entry->reference_id, 24) }}
                                    </code>
                                @else
                                    <span style="color: #64748b;">—</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-size: 0.78rem; color: #94a3b8;">
                                    {{ $entry->created_at->format('M d, Y H:i:s') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 36px; color: #94a3b8;">
                                No credit ledger entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($creditTransactions->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid rgba(255, 255, 255, 0.06);">
                {{ $creditTransactions->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
