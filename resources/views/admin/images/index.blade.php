@extends('admin.layouts.app')

@section('title', 'Image Generations Gallery')
@section('breadcrumb', 'Generations / Images')

@section('content')
<div x-data="{ lightboxOpen: false, activeImg: null, metaModalOpen: false, selectedImgMeta: null }" style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Image Generations Gallery
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Visual catalogue of all cinematic text-to-image outputs across the platform.
            </p>
        </div>

        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.videos.index') }}" class="btn-admin btn-admin-secondary">
                <i data-lucide="video" style="width: 15px; height: 15px;"></i>
                <span>Switch to Videos</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.images.index') }}" class="admin-filter-bar">
        <div class="admin-search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by prompt or user ID...">
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <select name="status" class="admin-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="succeeded" {{ $status === 'succeeded' ? 'selected' : '' }}>Succeeded ({{ $succeededImages }})</option>
                <option value="processing" {{ $status === 'processing' ? 'selected' : '' }}>Processing ({{ $processingImages }})</option>
                <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed ({{ $failedImages }})</option>
            </select>

            <select name="aspect_ratio" class="admin-select" onchange="this.form.submit()">
                <option value="">All Aspect Ratios</option>
                <option value="1:1" {{ $aspectRatio === '1:1' ? 'selected' : '' }}>Square (1:1)</option>
                <option value="16:9" {{ $aspectRatio === '16:9' ? 'selected' : '' }}>Landscape (16:9)</option>
                <option value="9:16" {{ $aspectRatio === '9:16' ? 'selected' : '' }}>Portrait (9:16)</option>
                <option value="4:3" {{ $aspectRatio === '4:3' ? 'selected' : '' }}>Classic (4:3)</option>
            </select>

            @if ($search || $status || $aspectRatio)
                <a href="{{ route('admin.images.index') }}" class="btn-admin btn-admin-secondary" style="padding: 8px 12px; font-size: 0.82rem;">
                    Clear Filters
                </a>
            @endif
        </div>
    </form>

    <!-- Images Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 18px;">
        @forelse ($images as $img)
            @php
                $matchedUser = $userMap->get((string)$img->user_id) ?? null;
            @endphp
            <div class="admin-card" style="padding: 12px; display: flex; flex-direction: column; gap: 10px; justify-content: space-between;">
                <!-- Thumbnail Card with Lightbox Trigger -->
                <div>
                    <div style="aspect-ratio: 1; border-radius: 8px; overflow: hidden; background: #07090e; position: relative; cursor: pointer;" @click="activeImg = '{{ $img->image_url }}'; lightboxOpen = true;">
                        @if ($img->image_url)
                            <img src="{{ $img->image_url }}" alt="{{ $img->prompt }}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" loading="lazy">
                        @else
                            <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #64748b; font-size: 0.78rem; gap: 6px;">
                                <i data-lucide="image-off" style="width: 24px; height: 24px;"></i>
                                <span>Preview unavailable</span>
                            </div>
                        @endif

                        <!-- Status badge on image -->
                        <div style="position: absolute; top: 8px; right: 8px;">
                            @if ($img->status === 'succeeded')
                                <span class="badge badge-success" style="font-size: 0.68rem; backdrop-filter: blur(8px);">Succeeded</span>
                            @elseif (in_array($img->status, ['starting', 'processing']))
                                <span class="badge badge-warning" style="font-size: 0.68rem; backdrop-filter: blur(8px);">Processing</span>
                            @elseif ($img->status === 'failed')
                                <span class="badge badge-danger" style="font-size: 0.68rem; backdrop-filter: blur(8px);">Failed</span>
                            @endif
                        </div>

                        <!-- Aspect ratio badge -->
                        <div style="position: absolute; bottom: 8px; left: 8px; background: rgba(0,0,0,0.65); backdrop-filter: blur(6px); border-radius: 4px; padding: 2px 6px; font-size: 0.7rem; font-family: 'JetBrains Mono', monospace; color: #cbd5e1;">
                            {{ $img->aspect_ratio }} &bull; {{ $img->megapixels }}MP
                        </div>
                    </div>

                    <!-- Prompt & User info -->
                    <div style="margin-top: 10px;">
                        <p style="font-size: 0.82rem; color: #f8fafc; font-weight: 500; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;" title="{{ $img->prompt }}">
                            {{ $img->prompt }}
                        </p>
                    </div>
                </div>

                <!-- Footer details and actions -->
                <div style="border-top: 1px solid var(--admin-border); padding-top: 8px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="font-size: 0.75rem; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 140px;">
                        @if ($matchedUser)
                            <a href="{{ route('admin.users.show', $matchedUser->id) }}" style="color: var(--admin-cyan); text-decoration: none;">{{ $matchedUser->name }}</a>
                        @else
                            <span>{{ Str::limit($img->user_id ?? 'Guest', 12) }}</span>
                        @endif
                    </div>

                    <div style="display: flex; gap: 4px;">
                        <button type="button" class="btn-admin btn-admin-secondary" style="padding: 4px 8px; font-size: 0.74rem;" @click="selectedImgMeta = {{ json_encode($img) }}; metaModalOpen = true;" title="Inspect metadata">
                            <i data-lucide="info" style="width: 13px; height: 13px;"></i>
                        </button>

                        <form method="POST" action="{{ route('admin.images.destroy', $img->id) }}" onsubmit="return confirm('Permanently delete this image?');" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-admin btn-admin-danger" style="padding: 4px 8px; font-size: 0.74rem;" title="Delete image">
                                <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1;" class="admin-card">
                <div style="text-align: center; padding: 48px; color: #64748b;">
                    <i data-lucide="image" style="width: 36px; height: 36px; margin-bottom: 12px; color: #475569;"></i>
                    <p>No image generations found matching your filters.</p>
                </div>
            </div>
        @endforelse
    </div>

    @if ($images->hasPages())
        <div style="margin-top: 10px;">
            {{ $images->links() }}
        </div>
    @endif

    <!-- Full Image Lightbox -->
    <div class="admin-modal-backdrop" x-show="lightboxOpen" x-cloak @click.self="lightboxOpen = false" @keydown.escape.window="lightboxOpen = false">
        <div style="position: relative; max-width: 90vw; max-height: 90vh;">
            <button type="button" @click="lightboxOpen = false" style="position: absolute; top: -38px; right: 0; background: none; border: none; color: #fff; font-size: 1.8rem; cursor: pointer;">&times;</button>
            <img :src="activeImg" alt="Full resolution preview" style="max-width: 90vw; max-height: 85vh; border-radius: 12px; object-fit: contain; box-shadow: 0 20px 60px rgba(0,0,0,0.8);">
        </div>
    </div>

    <!-- Metadata Inspector Modal -->
    <div class="admin-modal-backdrop" x-show="metaModalOpen" x-cloak @click.self="metaModalOpen = false" @keydown.escape.window="metaModalOpen = false">
        <div class="admin-modal-content" style="max-width: 650px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; border-bottom: 1px solid var(--admin-border); padding-bottom: 12px;">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #fff;">Image Metadata Details</h3>
                <button type="button" @click="metaModalOpen = false" style="background: none; border: none; color: #94a3b8; font-size: 1.4rem; cursor: pointer;">&times;</button>
            </div>
            <div style="max-height: 450px; overflow-y: auto;">
                <pre style="background: rgba(0,0,0,0.5); padding: 14px; border-radius: 8px; font-size: 0.8rem; color: #38bdf8; font-family: 'JetBrains Mono', monospace; white-space: pre-wrap; word-break: break-all;" x-text="JSON.stringify(selectedImgMeta, null, 2)"></pre>
            </div>
            <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                <button type="button" class="btn-admin btn-admin-secondary" @click="metaModalOpen = false">Close</button>
            </div>
        </div>
    </div>

</div>
@endsection
