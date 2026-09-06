@extends('admin.layouts.app')

@section('title', 'Transactions History')
@section('breadcrumb', 'Billing / Transactions')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Paddle Transactions
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Audit financial payment records, gross transaction values, and plan fulfillments.
            </p>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="padding: 8px 14px; border-radius: 10px; background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.25); color: #c084fc; font-size: 0.84rem; font-weight: 700;">
                Volume: ${{ number_format($stats['total_volume'], 2) }}
            </div>
            <div style="padding: 8px 14px; border-radius: 10px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.25); color: #34d399; font-size: 0.84rem; font-weight: 700;">
                Completed: {{ $stats['completed_count'] }}
            </div>
            <div style="padding: 8px 14px; border-radius: 10px; background: rgba(148, 163, 184, 0.12); border: 1px solid rgba(148, 163, 184, 0.25); color: #94a3b8; font-size: 0.84rem; font-weight: 700;">
                Total: {{ $stats['total_count'] }}
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
    <form method="GET" action="{{ route('admin.transactions.index') }}" class="admin-filter-bar">
        <div class="admin-search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by Transaction ID, Sub ID, or user email...">
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <select name="status" class="admin-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="billed" {{ $status === 'billed' ? 'selected' : '' }}>Billed</option>
                <option value="past_due" {{ $status === 'past_due' ? 'selected' : '' }}>Past Due</option>
                <option value="canceled" {{ $status === 'canceled' ? 'selected' : '' }}>Canceled</option>
            </select>

            @if ($search || $status)
                <a href="{{ route('admin.transactions.index') }}" class="btn-admin btn-admin-secondary" style="padding: 8px 12px; font-size: 0.82rem;">
                    Clear Filters
                </a>
            @endif
        </div>
    </form>

    <!-- Transactions Table -->
    <div class="admin-card" style="padding: 0; overflow: hidden;">
        <div class="admin-table-container" style="border: none; border-radius: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Type</th>
                        <th>Processed Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $txn)
                        <tr>
                            <td>
                                <code style="font-size: 0.78rem; color: #818cf8; background: rgba(0,0,0,0.3); padding: 3px 6px; border-radius: 4px;">
                                    {{ $txn->paddle_transaction_id }}
                                </code>
                                @if ($txn->paddle_subscription_id)
                                    <div style="font-size: 0.72rem; color: #64748b; margin-top: 2px;">
                                        Sub: {{ $txn->paddle_subscription_id }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($txn->user)
                                    <a href="{{ route('admin.users.show', $txn->user->id) }}" style="font-weight: 700; color: #f8fafc; text-decoration: none;">
                                        {{ $txn->user->name }}
                                    </a>
                                    <div style="font-size: 0.76rem; color: #94a3b8;">{{ $txn->user->email }}</div>
                                @else
                                    <span style="color: #64748b; font-style: italic;">Unassigned User</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #cbd5e1;">
                                    {{ $txn->pricingPlan?->name ?? ($txn->paddle_price_id ?: '—') }}
                                </span>
                            </td>
                            <td>
                                <span style="font-weight: 800; color: #34d399; font-size: 0.95rem;">
                                    ${{ number_format((float) $txn->amount, 2) }}
                                </span>
                                <span style="font-size: 0.74rem; color: #94a3b8;">{{ $txn->currency }}</span>
                            </td>
                            <td>
                                @php
                                    $statusStyle = match($txn->status) {
                                        'completed', 'paid' => 'background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);',
                                        'billed' => 'background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);',
                                        default => 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);',
                                    };
                                @endphp
                                <span style="padding: 3px 8px; border-radius: 9999px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; {{ $statusStyle }}">
                                    {{ $txn->status }}
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.78rem; color: #94a3b8; text-transform: capitalize;">
                                    {{ str_replace('_', ' ', $txn->type) }}
                                </span>
                            </td>
                            <td>
                                <span style="font-size: 0.8rem; color: #cbd5e1;">
                                    {{ $txn->processed_at ? $txn->processed_at->format('M d, Y H:i') : $txn->created_at->format('M d, Y H:i') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 36px; color: #94a3b8;">
                                No transactions recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transactions->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid rgba(255, 255, 255, 0.06);">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
