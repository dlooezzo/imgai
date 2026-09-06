@extends('admin.layouts.app')

@section('title', 'All Generations')
@section('breadcrumb', 'Generations / All')

@section('content')
<div x-data="{ metadataModalOpen: false, selectedGen: null }" style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Header & Metric Cards -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                AI Generation Management
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Unified stream of text-to-image, text-to-video, and image-to-video jobs across all users.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="{{ route('admin.images.index') }}" class="btn-admin btn-admin-secondary">
                <i data-lucide="image" style="width: 15px; height: 15px;"></i>
                <span>Image Gallery</span>
            </a>
            <a href="{{ route('admin.videos.index') }}" class="btn-admin btn-admin-secondary">
                <i data-lucide="video" style="width: 15px; height: 15px;"></i>
                <span>Video Gallery</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="stats-grid" style="margin-bottom: 0;">
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Total Creations</span>
                <span class="stat-value">{{ number_format($totalGenerations) }}</span>
                <span class="stat-hint">{{ number_format($totalImages) }} Images &bull; {{ number_format($totalVideos) }} Videos</span>
            </div>
            <div class="stat-icon-wrapper">
                <i data-lucide="layers"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Succeeded</span>
                <span class="stat-value" style="color: #34d399;">{{ number_format($succeededGenerations) }}</span>
                <span class="stat-hint">Delivered to users</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #34d399; background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.25);">
                <i data-lucide="check-circle-2"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Failed / Errored</span>
                <span class="stat-value" style="color: {{ $failedGenerations > 0 ? '#f87171' : '#f8fafc' }};">{{ number_format($failedGenerations) }}</span>
                <span class="stat-hint">Errors & timeouts</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #f87171; background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.25);">
                <i data-lucide="alert-circle"></i>
            </div>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <form method="GET" action="{{ route('admin.generations.index') }}" class="admin-filter-bar">
        <div class="admin-search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by prompt, user ID, or prediction ID...">
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <select name="type" class="admin-select" onchange="this.form.submit()">
                <option value="">All Types (Images & Videos)</option>
                <option value="image" {{ $type === 'image' ? 'selected' : '' }}>Images Only</option>
                <option value="video" {{ $type === 'video' ? 'selected' : '' }}>Videos Only</option>
            </select>

            <select name="status" class="admin-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="succeeded" {{ $status === 'succeeded' ? 'selected' : '' }}>Succeeded</option>
                <option value="processing" {{ $status === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="starting" {{ $status === 'starting' ? 'selected' : '' }}>Starting</option>
                <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>

            @if ($search || $type || $status)
                <a href="{{ route('admin.generations.index') }}" class="btn-admin btn-admin-secondary" style="padding: 8px 12px; font-size: 0.82rem;">
                    Clear Filters
                </a>
            @endif
        </div>
    </form>

    <!-- Unified Generations Table -->
    <div class="admin-card" style="padding: 0; overflow: hidden;">
        <div class="admin-table-container" style="border: none; border-radius: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Preview</th>
                        <th>Type</th>
                        <th>Prompt</th>
                        <th>User</th>
                        <th>Model</th>
                        <th>Ratio</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($generations as $gen)
                        @php
                            $matchedUser = $userMap->get((string)$gen['user_id']) ?? null;
                        @endphp
                        <tr>
                            <td style="width: 50px;">
                                <div style="width: 44px; height: 44px; border-radius: 6px; overflow: hidden; background: #07090e; display: flex; align-items: center; justify-content: center;">
                                    @if ($gen['preview_url'])
                                        @if ($gen['type'] === 'image')
                                            <img src="{{ $gen['preview_url'] }}" alt="preview" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                                        @else
                                            <video src="{{ $gen['preview_url'] }}" style="width: 100%; height: 100%; object-fit: cover;" muted></video>
                                        @endif
                                    @else
                                        <i data-lucide="{{ $gen['type'] === 'image' ? 'image' : 'video' }}" style="width: 16px; height: 16px; color: #64748b;"></i>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($gen['type'] === 'image')
                                    <span class="badge badge-info"><i data-lucide="image" style="width: 11px; height: 11px;"></i> Image</span>
                                @else
                                    <span class="badge" style="background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.3); color: #c084fc;">
                                        <i data-lucide="video" style="width: 11px; height: 11px;"></i> Video
                                    </span>
                                @endif
                            </td>
                            <td style="max-width: 240px;">
                                <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #f8fafc; font-weight: 500;" title="{{ $gen['prompt'] }}">
                                    {{ $gen['prompt'] }}
                                </div>
                                <div style="font-size: 0.72rem; color: #64748b; font-family: 'JetBrains Mono', monospace;">
                                    {{ Str::limit($gen['id'], 16) }}
                                </div>
                            </td>
                            <td>
                                @if ($matchedUser)
                                    <a href="{{ route('admin.users.show', $matchedUser->id) }}" style="color: var(--admin-cyan); font-weight: 500; text-decoration: none; font-size: 0.84rem;">
                                        {{ $matchedUser->name }}
                                    </a>
                                @else
                                    <span style="color: #64748b; font-size: 0.78rem;">{{ Str::limit($gen['user_id'] ?? 'Anonymous', 12) }}</span>
                                @endif
                            </td>
                            <td style="font-size: 0.8rem; color: #94a3b8;">
                                {{ $gen['model'] }}
                            </td>
                            <td style="font-size: 0.8rem; color: #94a3b8; font-family: 'JetBrains Mono', monospace;">
                                {{ $gen['aspect_ratio'] ?? '1:1' }}
                            </td>
                            <td>
                                @if ($gen['status'] === 'succeeded')
                                    <span class="badge badge-success">Succeeded</span>
                                @elseif (in_array($gen['status'], ['starting', 'processing']))
                                    <span class="badge badge-warning">Processing</span>
                                @elseif ($gen['status'] === 'failed')
                                    <span class="badge badge-danger" title="{{ $gen['error_message'] }}">Failed</span>
                                @else
                                    <span class="badge badge-user">{{ ucfirst($gen['status']) }}</span>
                                @endif
                            </td>
                            <td style="font-size: 0.78rem; color: #64748b; white-space: nowrap;">
                                {{ $gen['created_at'] ? $gen['created_at']->format('M d, H:i') : '' }}
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <button type="button" class="btn-admin btn-admin-secondary" style="padding: 5px 9px; font-size: 0.76rem;" @click="selectedGen = {{ json_encode($gen['raw']) }}; metadataModalOpen = true;">
                                    <i data-lucide="info" style="width: 13px; height: 13px;"></i>
                                    <span>Inspect</span>
                                </button>

                                <form method="POST" action="{{ route('admin.generations.destroy', $gen['id']) }}" onsubmit="return confirm('Permanently delete this generation record and its storage files?');" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="type" value="{{ $gen['type'] }}">
                                    <button type="submit" class="btn-admin btn-admin-danger" style="padding: 5px 9px; font-size: 0.76rem;" title="Delete generation">
                                        <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">
                                No generations found matching your query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($generations->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--admin-border);">
                {{ $generations->links() }}
            </div>
        @endif
    </div>

    <!-- Metadata Inspection Modal -->
    <div class="admin-modal-backdrop" x-show="metadataModalOpen" x-cloak @click.self="metadataModalOpen = false" @keydown.escape.window="metadataModalOpen = false">
        <div class="admin-modal-content" style="max-width: 680px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; border-bottom: 1px solid var(--admin-border); padding-bottom: 12px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="code" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                    <span>Generation Metadata Inspector</span>
                </h3>
                <button type="button" @click="metadataModalOpen = false" style="background: none; border: none; color: #94a3b8; font-size: 1.4rem; cursor: pointer;">&times;</button>
            </div>

            <div style="max-height: 480px; overflow-y: auto;">
                <pre style="background: rgba(0,0,0,0.5); padding: 14px; border-radius: 8px; font-size: 0.8rem; color: #38bdf8; font-family: 'JetBrains Mono', monospace; white-space: pre-wrap; word-break: break-all;" x-text="JSON.stringify(selectedGen, null, 2)"></pre>
            </div>

            <div style="margin-top: 18px; display: flex; justify-content: flex-end;">
                <button type="button" class="btn-admin btn-admin-secondary" @click="metadataModalOpen = false">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
