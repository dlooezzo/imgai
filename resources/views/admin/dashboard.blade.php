@extends('admin.layouts.app')

@section('title', 'Dashboard Overview')
@section('breadcrumb', 'Overview')

@section('content')
<div style="display: flex; flex-direction: column; gap: 28px;">

    <!-- Welcome & Quick Stats Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                AI Studio Platform Overview
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Real-time operational metrics, infrastructure health, and generation activity.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="{{ route('admin.system-status.index') }}" class="btn-admin btn-admin-secondary">
                <i data-lucide="activity" style="width: 16px; height: 16px;"></i>
                <span>Live System Health</span>
            </a>
            <a href="{{ route('admin.generations.index') }}" class="btn-admin btn-admin-primary">
                <i data-lucide="layers" style="width: 16px; height: 16px;"></i>
                <span>Inspect Generations</span>
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <!-- Total Users -->
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Total Users</span>
                <span class="stat-value">{{ number_format($stats['total_users']) }}</span>
                <span class="stat-hint">{{ number_format($stats['total_admins']) }} Administrators</span>
            </div>
            <div class="stat-icon-wrapper" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border-color: rgba(99, 102, 241, 0.3);">
                <i data-lucide="users"></i>
            </div>
        </div>

        <!-- Total Image Generations -->
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Image Generations</span>
                <span class="stat-value">{{ number_format($stats['total_images']) }}</span>
                <span class="stat-hint">Cinematic 2MP Engine</span>
            </div>
            <div class="stat-icon-wrapper" style="background: rgba(6, 182, 212, 0.15); color: #38bdf8; border-color: rgba(6, 182, 212, 0.3);">
                <i data-lucide="image"></i>
            </div>
        </div>

        <!-- Total Video Generations -->
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Video Generations</span>
                <span class="stat-value">{{ number_format($stats['total_videos']) }}</span>
                <span class="stat-hint">Hunyuan & Wan 2.2</span>
            </div>
            <div class="stat-icon-wrapper" style="background: rgba(139, 92, 246, 0.15); color: #c084fc; border-color: rgba(139, 92, 246, 0.3);">
                <i data-lucide="video"></i>
            </div>
        </div>

        <!-- Generations Today -->
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Generations Today</span>
                <span class="stat-value">{{ number_format($stats['generations_today']) }}</span>
                <span class="stat-hint">Past 24 Hours</span>
            </div>
            <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.15); color: #34d399; border-color: rgba(16, 185, 129, 0.3);">
                <i data-lucide="trending-up"></i>
            </div>
        </div>

        <!-- Active Generations -->
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Active Queue</span>
                <span class="stat-value" style="color: {{ $stats['active_generations'] > 0 ? '#38bdf8' : '#f8fafc' }};">{{ number_format($stats['active_generations']) }}</span>
                <span class="stat-hint">Starting / Processing</span>
            </div>
            <div class="stat-icon-wrapper" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; border-color: rgba(245, 158, 11, 0.3);">
                <i data-lucide="clock"></i>
            </div>
        </div>

        <!-- Failed Generations -->
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Failed Tasks</span>
                <span class="stat-value" style="color: {{ $stats['failed_generations'] > 0 ? '#f87171' : '#f8fafc' }};">{{ number_format($stats['failed_generations']) }}</span>
                <span class="stat-hint">Need Inspection</span>
            </div>
            <div class="stat-icon-wrapper" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border-color: rgba(239, 68, 68, 0.3);">
                <i data-lucide="alert-triangle"></i>
            </div>
        </div>

        <!-- R2 Storage Objects -->
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">R2 Objects</span>
                <span class="stat-value">{{ number_format($stats['r2_storage_count']) }}</span>
                <span class="stat-hint">Cloudflare Stored Videos</span>
            </div>
            <div class="stat-icon-wrapper" style="background: rgba(217, 70, 239, 0.15); color: #f472b6; border-color: rgba(217, 70, 239, 0.3);">
                <i data-lucide="hard-drive"></i>
            </div>
        </div>
    </div>

    <!-- Infrastructure Status Bar -->
    <div class="admin-card" style="padding: 16px 22px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.92rem;">
                <i data-lucide="shield-check" style="width: 18px; height: 18px; color: var(--admin-emerald);"></i>
                <span>System Connectivity Status</span>
            </div>

            <div style="display: flex; align-items: center; gap: 18px; flex-wrap: wrap; font-size: 0.82rem;">
                <!-- MySQL -->
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);"></span>
                    <span style="color: #94a3b8;">MySQL:</span>
                    <span style="color: #fff; font-weight: 600;">Connected</span>
                </div>

                <!-- Supabase -->
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $healthSummary['supabase']['status'] === 'configured' ? '#10b981' : '#f59e0b' }}; box-shadow: 0 0 8px {{ $healthSummary['supabase']['status'] === 'configured' ? 'rgba(16, 185, 129, 0.6)' : 'rgba(245, 158, 11, 0.6)' }};"></span>
                    <span style="color: #94a3b8;">Supabase Auth:</span>
                    <span style="color: #fff; font-weight: 600;">{{ $healthSummary['supabase']['label'] }}</span>
                </div>

                <!-- R2 -->
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $healthSummary['r2']['status'] === 'configured' ? '#10b981' : '#f59e0b' }}; box-shadow: 0 0 8px {{ $healthSummary['r2']['status'] === 'configured' ? 'rgba(16, 185, 129, 0.6)' : 'rgba(245, 158, 11, 0.6)' }};"></span>
                    <span style="color: #94a3b8;">Cloudflare R2:</span>
                    <span style="color: #fff; font-weight: 600;">{{ $healthSummary['r2']['label'] }}</span>
                </div>

                <!-- API Market -->
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $healthSummary['api_market']['status'] === 'configured' ? '#10b981' : '#ef4444' }}; box-shadow: 0 0 8px {{ $healthSummary['api_market']['status'] === 'configured' ? 'rgba(16, 185, 129, 0.6)' : 'rgba(239, 68, 68, 0.6)' }};"></span>
                    <span style="color: #94a3b8;">API Market:</span>
                    <span style="color: #fff; font-weight: 600;">{{ $healthSummary['api_market']['label'] }}</span>
                </div>
            </div>

            <a href="{{ route('admin.system-status.index') }}" style="font-size: 0.8rem; color: var(--admin-cyan); text-decoration: none; font-weight: 600;">
                Full Health Matrix &rarr;
            </a>
        </div>
    </div>

    <!-- Main Grid: Recent Generations & Recent Users -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">

        <!-- Recent Generations Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="layers" style="width: 18px; height: 18px; color: var(--admin-primary);"></i>
                        <span>Recent Generations</span>
                    </h2>
                    <p class="admin-card-subtitle">Live stream of latest image and video creation jobs</p>
                </div>

                <a href="{{ route('admin.generations.index') }}" class="btn-admin btn-admin-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                    View All
                </a>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Prompt</th>
                            <th>User</th>
                            <th>Model</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentGenerations as $gen)
                            @php
                                $matchedUser = $userMap->get((string)$gen['user_id']) ?? null;
                            @endphp
                            <tr>
                                <td>
                                    @if ($gen['type'] === 'image')
                                        <span class="badge badge-info"><i data-lucide="image" style="width: 12px; height: 12px;"></i> Image</span>
                                    @else
                                        <span class="badge" style="background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.35); color: #c084fc;">
                                            <i data-lucide="video" style="width: 12px; height: 12px;"></i> Video
                                        </span>
                                    @endif
                                </td>
                                <td style="max-width: 200px;">
                                    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #f8fafc;" title="{{ $gen['prompt'] }}">
                                        {{ $gen['prompt'] }}
                                    </div>
                                </td>
                                <td>
                                    @if ($matchedUser)
                                        <a href="{{ route('admin.users.show', $matchedUser->id) }}" style="color: var(--admin-cyan); text-decoration: none; font-weight: 500;">
                                            {{ $matchedUser->name }}
                                        </a>
                                    @else
                                        <span style="color: #64748b; font-size: 0.8rem;">{{ Str::limit($gen['user_id'] ?? 'Anonymous', 12) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-size: 0.78rem; color: #94a3b8;">{{ $gen['model'] }}</span>
                                </td>
                                <td>
                                    @if ($gen['status'] === 'succeeded')
                                        <span class="badge badge-success">Completed</span>
                                    @elseif (in_array($gen['status'], ['starting', 'processing']))
                                        <span class="badge badge-warning">Processing</span>
                                    @elseif ($gen['status'] === 'failed')
                                        <span class="badge badge-danger">Failed</span>
                                    @else
                                        <span class="badge badge-user">{{ ucfirst($gen['status']) }}</span>
                                    @endif
                                </td>
                                <td style="font-size: 0.78rem; color: #64748b; white-space: nowrap;">
                                    {{ $gen['created_at'] ? $gen['created_at']->diffForHumans() : 'Just now' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 32px; color: #64748b;">
                                    No generations created yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Registered Users -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="user-plus" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                        <span>Recent Users</span>
                    </h2>
                    <p class="admin-card-subtitle">Latest accounts registered</p>
                </div>

                <a href="{{ route('admin.users.index') }}" class="btn-admin btn-admin-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                    All Users
                </a>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                @forelse ($recentUsers as $user)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 8px; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--admin-border);">
                        <div style="display: flex; align-items: center; gap: 10px; overflow: hidden;">
                            <div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #06b6d4); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.82rem; color: #fff; flex-shrink: 0;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div style="display: flex; flex-direction: column; overflow: hidden;">
                                <a href="{{ route('admin.users.show', $user->id) }}" style="color: #f8fafc; font-weight: 600; font-size: 0.84rem; text-decoration: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $user->name }}
                                </a>
                                <span style="color: #64748b; font-size: 0.74rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $user->email }}
                                </span>
                            </div>
                        </div>

                        <div>
                            @if ($user->role === 'admin')
                                <span class="badge badge-admin">Admin</span>
                            @else
                                <span class="badge badge-user">User</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 24px; color: #64748b; font-size: 0.88rem;">
                        No users registered yet.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
