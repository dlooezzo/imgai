@extends('admin.layouts.app')

@section('title', 'System Logs Viewer')
@section('breadcrumb', 'System / System Logs')

@section('content')
<div x-data="{ expandedIndex: null }" style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Header & Metric Cards -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                System Event Logs
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Sanitized application and infrastructure error traces. Active log file size: {{ $logFileSize }}.
            </p>
        </div>
    </div>

    <!-- Log Severity Counts -->
    <div class="stats-grid" style="margin-bottom: 0;">
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Total Logged Events</span>
                <span class="stat-value">{{ number_format($counts['total']) }}</span>
                <span class="stat-hint">Active buffer</span>
            </div>
            <div class="stat-icon-wrapper">
                <i data-lucide="file-text"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Errors</span>
                <span class="stat-value" style="color: #f87171;">{{ number_format($counts['error']) }}</span>
                <span class="stat-hint">System & API exceptions</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #f87171; background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.25);">
                <i data-lucide="alert-octagon"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Warnings</span>
                <span class="stat-value" style="color: #fbbf24;">{{ number_format($counts['warning']) }}</span>
                <span class="stat-hint">Recoverable warnings</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #fbbf24; background: rgba(245, 158, 11, 0.12); border-color: rgba(245, 158, 11, 0.25);">
                <i data-lucide="alert-triangle"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Info Events</span>
                <span class="stat-value" style="color: #38bdf8;">{{ number_format($counts['info']) }}</span>
                <span class="stat-hint">Operational events</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #38bdf8; background: rgba(6, 182, 212, 0.12); border-color: rgba(6, 182, 212, 0.25);">
                <i data-lucide="info"></i>
            </div>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <form method="GET" action="{{ route('admin.logs.index') }}" class="admin-filter-bar">
        <div class="admin-search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search log messages or stack traces...">
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <select name="level" class="admin-select" onchange="this.form.submit()">
                <option value="">All Log Levels</option>
                <option value="error" {{ $level === 'error' ? 'selected' : '' }}>ERROR</option>
                <option value="warning" {{ $level === 'warning' ? 'selected' : '' }}>WARNING</option>
                <option value="info" {{ $level === 'info' ? 'selected' : '' }}>INFO</option>
            </select>

            @if ($search || $level)
                <a href="{{ route('admin.logs.index') }}" class="btn-admin btn-admin-secondary" style="padding: 8px 12px; font-size: 0.82rem;">
                    Clear Filter
                </a>
            @endif
        </div>
    </form>

    <!-- Logs List Card -->
    <div class="admin-card" style="padding: 0; overflow: hidden;">
        <div style="display: flex; flex-direction: column;">
            @forelse ($logs as $index => $log)
                <div style="border-bottom: 1px solid var(--admin-border); padding: 14px 18px; transition: background 0.2s;" :style="expandedIndex === {{ $index }} ? 'background: rgba(255, 255, 255, 0.03);' : ''">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;">
                        <div style="display: flex; align-items: flex-start; gap: 12px; flex: 1;">
                            <!-- Severity Badge -->
                            <div>
                                @if ($log['level'] === 'ERROR' || $log['level'] === 'CRITICAL' || $log['level'] === 'EMERGENCY')
                                    <span class="badge badge-danger">{{ $log['level'] }}</span>
                                @elseif ($log['level'] === 'WARNING')
                                    <span class="badge badge-warning">{{ $log['level'] }}</span>
                                @elseif ($log['level'] === 'INFO')
                                    <span class="badge badge-info">{{ $log['level'] }}</span>
                                @else
                                    <span class="badge badge-user">{{ $log['level'] }}</span>
                                @endif
                            </div>

                            <!-- Log Message & Timestamp -->
                            <div style="flex: 1; overflow: hidden;">
                                <div style="font-size: 0.86rem; color: #f8fafc; font-weight: 600; line-height: 1.4; word-break: break-all;">
                                    {{ $log['message'] }}
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px; margin-top: 4px; font-size: 0.75rem; color: #64748b;">
                                    <span><i data-lucide="clock" style="width: 12px; height: 12px; display: inline; vertical-align: middle;"></i> {{ $log['timestamp'] }}</span>
                                    <span>&bull;</span>
                                    <span style="text-transform: uppercase;">{{ $log['environment'] }}</span>
                                </div>
                            </div>
                        </div>

                        @if (!empty($log['context']))
                            <button type="button" class="btn-admin btn-admin-secondary" style="padding: 4px 10px; font-size: 0.72rem; flex-shrink: 0;" @click="expandedIndex = (expandedIndex === {{ $index }} ? null : {{ $index }})">
                                <span x-text="expandedIndex === {{ $index }} ? 'Hide Stack' : 'View Stack'"></span>
                            </button>
                        @endif
                    </div>

                    <!-- Collapsible Context / Stack Trace -->
                    @if (!empty($log['context']))
                        <div x-show="expandedIndex === {{ $index }}" x-cloak style="margin-top: 12px; padding: 12px; background: rgba(0, 0, 0, 0.4); border-radius: 8px; border: 1px solid var(--admin-border);">
                            <pre class="font-mono" style="font-size: 0.75rem; color: #cbd5e1; white-space: pre-wrap; word-break: break-all; margin: 0;">{{ $log['context'] }}</pre>
                        </div>
                    @endif
                </div>
            @empty
                <div style="text-align: center; padding: 48px; color: #64748b;">
                    <i data-lucide="file-check" style="width: 36px; height: 36px; margin-bottom: 10px; color: #34d399;"></i>
                    <p>No log events found matching your criteria.</p>
                </div>
            @endforelse
        </div>

        @if ($logs->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--admin-border);">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
