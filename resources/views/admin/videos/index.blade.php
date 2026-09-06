@extends('admin.layouts.app')

@section('title', 'Video Generations Gallery')
@section('breadcrumb', 'Generations / Videos')

@section('content')
<div x-data="{ metaModalOpen: false, selectedVidMeta: null }" style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Video Generations Gallery
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Catalogue of Text-to-Video (Hunyuan) and Image-to-Video (Wan 2.2) creations.
            </p>
        </div>

        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.images.index') }}" class="btn-admin btn-admin-secondary">
                <i data-lucide="image" style="width: 15px; height: 15px;"></i>
                <span>Switch to Images</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.videos.index') }}" class="admin-filter-bar">
        <div class="admin-search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search prompt, prediction ID, or user...">
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <select name="type" class="admin-select" onchange="this.form.submit()">
                <option value="">All Video Engines</option>
                <option value="text-to-video" {{ $type === 'text-to-video' ? 'selected' : '' }}>Text-to-Video (Hunyuan) ({{ $textToVideos }})</option>
                <option value="image-to-video" {{ $type === 'image-to-video' ? 'selected' : '' }}>Image-to-Video (Wan 2.2) ({{ $imageToVideos }})</option>
            </select>

            <select name="status" class="admin-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="succeeded" {{ $status === 'succeeded' ? 'selected' : '' }}>Succeeded ({{ $succeededVideos }})</option>
                <option value="processing" {{ $status === 'processing' ? 'selected' : '' }}>Processing ({{ $processingVideos }})</option>
                <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed ({{ $failedVideos }})</option>
            </select>

            @if ($search || $type || $status)
                <a href="{{ route('admin.videos.index') }}" class="btn-admin btn-admin-secondary" style="padding: 8px 12px; font-size: 0.82rem;">
                    Clear Filters
                </a>
            @endif
        </div>
    </form>

    <!-- Videos Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        @forelse ($videos as $vid)
            @php
                $matchedUser = $userMap->get((string)$vid->user_id) ?? null;
            @endphp
            <div class="admin-card" style="padding: 14px; display: flex; flex-direction: column; gap: 12px; justify-content: space-between;">
                <!-- Video Container -->
                <div>
                    <div style="aspect-ratio: 16/9; border-radius: 8px; overflow: hidden; background: #07090e; position: relative;">
                        @if ($vid->video_url)
                            <video src="{{ $vid->video_url }}" controls preload="none" style="width: 100%; height: 100%; object-fit: cover;"></video>
                        @else
                            <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #64748b; font-size: 0.78rem; gap: 6px;">
                                <i data-lucide="video-off" style="width: 24px; height: 24px;"></i>
                                <span>{{ $vid->status === 'failed' ? 'Generation failed' : 'Processing render...' }}</span>
                            </div>
                        @endif

                        <!-- Status Badge -->
                        <div style="position: absolute; top: 8px; right: 8px; pointer-events: none;">
                            @if ($vid->status === 'succeeded')
                                <span class="badge badge-success" style="font-size: 0.68rem; backdrop-filter: blur(8px);">Ready</span>
                            @elseif (in_array($vid->status, ['starting', 'processing']))
                                <span class="badge badge-warning" style="font-size: 0.68rem; backdrop-filter: blur(8px);">Processing</span>
                            @elseif ($vid->status === 'failed')
                                <span class="badge badge-danger" style="font-size: 0.68rem; backdrop-filter: blur(8px);">Failed</span>
                            @endif
                        </div>

                        <!-- Engine Badge -->
                        <div style="position: absolute; top: 8px; left: 8px; pointer-events: none;">
                            @if ($vid->generation_type === 'image-to-video')
                                <span class="badge" style="background: rgba(6, 182, 212, 0.25); border: 1px solid rgba(6, 182, 212, 0.4); color: #38bdf8; font-size: 0.68rem; backdrop-filter: blur(8px);">
                                    Wan 2.2 I2V
                                </span>
                            @else
                                <span class="badge" style="background: rgba(139, 92, 246, 0.25); border: 1px solid rgba(139, 92, 246, 0.4); color: #c084fc; font-size: 0.68rem; backdrop-filter: blur(8px);">
                                    Hunyuan T2V
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Prompt & Specs -->
                    <div style="margin-top: 10px;">
                        <p style="font-size: 0.82rem; color: #f8fafc; font-weight: 500; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;" title="{{ $vid->prompt }}">
                            {{ $vid->prompt }}
                        </p>

                        <div style="margin-top: 6px; display: flex; align-items: center; gap: 8px; font-size: 0.72rem; color: #64748b; font-family: 'JetBrains Mono', monospace;">
                            <span>{{ $vid->width }}x{{ $vid->height }}</span>
                            <span>&bull;</span>
                            <span>{{ $vid->frame_rate ?? 24 }} FPS</span>
                            @if ($vid->num_frames)
                                <span>&bull;</span>
                                <span>{{ $vid->num_frames }} frames</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Footer details and actions -->
                <div style="border-top: 1px solid var(--admin-border); padding-top: 8px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="font-size: 0.75rem; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 170px;">
                        @if ($matchedUser)
                            <a href="{{ route('admin.users.show', $matchedUser->id) }}" style="color: var(--admin-cyan); text-decoration: none;">{{ $matchedUser->name }}</a>
                        @else
                            <span>{{ Str::limit($vid->user_id ?? 'Guest', 12) }}</span>
                        @endif
                    </div>

                    <div style="display: flex; gap: 4px;">
                        <button type="button" class="btn-admin btn-admin-secondary" style="padding: 4px 8px; font-size: 0.74rem;" @click="selectedVidMeta = {{ json_encode($vid) }}; metaModalOpen = true;" title="Inspect metadata">
                            <i data-lucide="info" style="width: 13px; height: 13px;"></i>
                        </button>

                        <form method="POST" action="{{ route('admin.videos.destroy', $vid->id) }}" onsubmit="return confirm('Permanently delete this video and clean up R2 storage?');" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-admin btn-admin-danger" style="padding: 4px 8px; font-size: 0.74rem;" title="Delete video">
                                <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1;" class="admin-card">
                <div style="text-align: center; padding: 48px; color: #64748b;">
                    <i data-lucide="video" style="width: 36px; height: 36px; margin-bottom: 12px; color: #475569;"></i>
                    <p>No video generations found matching your filters.</p>
                </div>
            </div>
        @endforelse
    </div>

    @if ($videos->hasPages())
        <div style="margin-top: 10px;">
            {{ $videos->links() }}
        </div>
    @endif

    <!-- Metadata Inspector Modal -->
    <div class="admin-modal-backdrop" x-show="metaModalOpen" x-cloak @click.self="metaModalOpen = false" @keydown.escape.window="metaModalOpen = false">
        <div class="admin-modal-content" style="max-width: 650px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; border-bottom: 1px solid var(--admin-border); padding-bottom: 12px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #fff;">Video Metadata Details</h3>
                <button type="button" @click="metaModalOpen = false" style="background: none; border: none; color: #94a3b8; font-size: 1.4rem; cursor: pointer;">&times;</button>
            </div>
            <div style="max-height: 450px; overflow-y: auto;">
                <pre style="background: rgba(0,0,0,0.5); padding: 14px; border-radius: 8px; font-size: 0.8rem; color: #38bdf8; font-family: 'JetBrains Mono', monospace; white-space: pre-wrap; word-break: break-all;" x-text="JSON.stringify(selectedVidMeta, null, 2)"></pre>
            </div>
            <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                <button type="button" class="btn-admin btn-admin-secondary" @click="metaModalOpen = false">Close</button>
            </div>
        </div>
    </div>

</div>
@endsection
