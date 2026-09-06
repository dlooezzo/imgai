@extends('layouts.app')

@section('content')
<!-- Left Navigation Sidebar -->
@include('layouts.sidebar')

<!-- Right Workspace: Dedicated Media Library -->
<main class="app-workspace" style="max-width: 1400px; margin: 0 auto; width: 100%;">
    <!-- Header Block -->
    <div class="tool-header-block">
        <div class="tool-title-wrap">
            <h1>Media Library</h1>
            <p>Explore, inspect, and manage all synthesized images and cinematic video renders in one place.</p>
        </div>

        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <div class="model-pill">
                <i data-lucide="layers" style="width: 15px; height: 15px;"></i>
                <span>Total Assets: <strong x-text="stats.total_creations"></strong></span>
            </div>
            <a href="{{ route('tools.image.index') }}" class="btn-action btn-action-primary" style="padding: 7px 14px; font-size: 0.82rem;">
                <i data-lucide="sparkles" style="width: 14px; height: 14px;"></i>
                <span>New Image</span>
            </a>
            <a href="{{ route('tools.video.index') }}" class="btn-action" style="padding: 7px 14px; font-size: 0.82rem;">
                <i data-lucide="video" style="width: 14px; height: 14px;"></i>
                <span>New Video</span>
            </a>
            <a href="{{ route('tools.image-to-video.index') }}" class="btn-action" style="padding: 7px 14px; font-size: 0.82rem;">
                <i data-lucide="clapperboard" style="width: 14px; height: 14px;"></i>
                <span>New Image to Video</span>
            </a>
        </div>
    </div>

    <!-- Library Content Card -->
    <div class="dashboard-section-card" style="padding: 24px;">
        <!-- Toolbar: Live Search + Media Filters -->
        <div class="gallery-toolbar" style="margin-bottom: 24px;">
            <div class="gallery-search-wrap">
                <span class="input-icon-prefix">
                    <i data-lucide="search" style="width: 16px; height: 16px;"></i>
                </span>
                <input type="text" class="gallery-search-input" x-model="historySearch" placeholder="Search creations by prompt keywords...">
                <button type="button" class="input-icon-suffix" x-show="historySearch.length > 0" @click="historySearch = ''" title="Clear search">
                    <i data-lucide="x" style="width: 15px; height: 15px;"></i>
                </button>
            </div>

            <!-- Segmented Filters -->
            <div class="segmented-group" style="width: auto;">
                <button type="button" class="segmented-btn" :class="{ 'active': historyFilter === 'all' }" @click="historyFilter = 'all'">
                    All (<span x-text="allCreations.length"></span>)
                </button>
                <button type="button" class="segmented-btn" :class="{ 'active': historyFilter === 'images' }" @click="historyFilter = 'images'">
                    Images (<span x-text="imageCreations.length"></span>)
                </button>
                <button type="button" class="segmented-btn" :class="{ 'active': historyFilter === 'videos' }" @click="historyFilter = 'videos'">
                    Videos (<span x-text="videoCreations.length"></span>)
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <template x-if="filteredCreations.length === 0">
            <div class="empty-state" style="padding: 64px 20px;">
                <div style="width: 68px; height: 68px; border-radius: 20px; background: rgba(99, 102, 241, 0.12); display: flex; align-items: center; justify-content: center; color: var(--brand-primary); margin-bottom: 14px;">
                    <i data-lucide="film" style="width: 34px; height: 34px;"></i>
                </div>
                <p style="font-weight: 700; font-size: 1.15rem; color: var(--text-primary); margin: 0;">No Media Found</p>
                <p style="font-size: 0.88rem; color: var(--text-muted); max-width: 380px; margin-top: 6px;" x-text="historySearch ? 'No creations match your search term \'' + historySearch + '\'.' : 'You have not generated any media yet. Start generating high-resolution images or videos!'"></p>
                <div style="display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; justify-content: center;">
                    <a href="{{ route('tools.image.index') }}" class="btn-action btn-action-primary">
                        <i data-lucide="sparkles" style="width: 15px; height: 15px;"></i>
                        <span>Generate Image</span>
                    </a>
                    <a href="{{ route('tools.video.index') }}" class="btn-action">
                        <i data-lucide="video" style="width: 15px; height: 15px;"></i>
                        <span>Text to Video</span>
                    </a>
                    <a href="{{ route('tools.image-to-video.index') }}" class="btn-action">
                        <i data-lucide="clapperboard" style="width: 15px; height: 15px;"></i>
                        <span>Image to Video</span>
                    </a>
                </div>
            </div>
        </template>

        <!-- Media Grid (Responsive 3/4 Column Grid) -->
        <div class="gallery-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <template x-for="item in filteredCreations" :key="item.id">
                <div class="gallery-card">
                    <!-- Media Preview Viewport -->
                    <div class="gallery-thumb-wrap" style="height: 200px;">
                        <!-- Video Item -->
                        <template x-if="item.video_url || item.video_path">
                            <div style="width: 100%; height: 100%; position: relative;">
                                <video :src="item.video_url || ('/storage/' + item.video_path)" class="gallery-thumb" muted loop onmouseover="this.play()" onmouseout="this.pause()"></video>
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 5px;">
                                    <i data-lucide="play" style="width: 10px; height: 10px; fill: #fff;"></i>
                                    <span x-text="item.generation_type === 'image-to-video' ? 'I2V' : 'VIDEO'"></span>
                                </div>
                                <template x-if="item.source_image_url">
                                    <div style="position: absolute; bottom: 8px; left: 8px; width: 34px; height: 34px; border-radius: 6px; overflow: hidden; border: 1.5px solid rgba(255,255,255,0.4); cursor: pointer;" @click.stop="openLightbox(item.source_image_url)" title="View source image">
                                        <img :src="item.source_image_url" alt="Source" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Image Item -->
                        <template x-if="!item.video_url && !item.video_path && (item.image_url || item.image_path)">
                            <div style="width: 100%; height: 100%; position: relative;">
                                <img :src="item.image_url || ('/storage/' + item.image_path)" :alt="item.prompt" class="gallery-thumb" @click="openLightbox(item.image_url || ('/storage/' + item.image_path))">
                                <div style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); border-radius: 4px; padding: 3px 7px; font-size: 0.68rem; color: #fff; cursor: pointer; display: flex; align-items: center; gap: 4px;" @click="openLightbox(item.image_url || ('/storage/' + item.image_path))">
                                    <i data-lucide="maximize-2" style="width: 11px; height: 11px;"></i>
                                    <span>2MP</span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Card Info -->
                    <div class="gallery-info" style="padding: 14px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <span class="nav-badge" :style="item.generation_type === 'image-to-video' ? 'background: rgba(236,72,153,0.15); color: #f472b6; border: 1px solid rgba(236,72,153,0.3);' : (item.video_url || item.video_path ? 'background: rgba(168,85,247,0.15); color: #c084fc; border: 1px solid rgba(168,85,247,0.3);' : 'background: rgba(6,182,212,0.15); color: var(--brand-cyan); border: 1px solid rgba(6,182,212,0.3);')" x-text="item.generation_type === 'image-to-video' ? 'IMAGE TO VIDEO' : (item.video_url || item.video_path ? 'TEXT TO VIDEO' : 'IMAGE')"></span>
                            <span style="font-size: 0.74rem; color: var(--text-muted); font-family: var(--font-mono);" x-text="item.aspect_ratio || '16:9'"></span>
                        </div>

                        <p class="gallery-prompt" :title="item.prompt" x-text="item.prompt" style="font-size: 0.84rem; line-height: 1.35; max-height: 2.7em; white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"></p>

                        <!-- Actions Footer -->
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.06);">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <template x-if="item.generation_type === 'image-to-video'">
                                    <a :href="'/tools/image-to-video/download/' + item.id" class="btn-icon-square" title="Download MP4 Video" download>
                                        <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                                    </a>
                                </template>
                                <template x-if="item.generation_type !== 'image-to-video' && (item.video_url || item.video_path)">
                                    <a :href="'/tools/video-generator/download/' + item.id" class="btn-icon-square" title="Download MP4 Video" download>
                                        <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                                    </a>
                                </template>
                                <template x-if="!item.video_url && !item.video_path">
                                    <a :href="'/tools/image-generator/download/' + item.id" class="btn-icon-square" title="Download High-Res Image" download>
                                        <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                                    </a>
                                </template>
                                <button type="button" class="btn-icon-square" @click="copyPrompt(item.prompt)" title="Copy Prompt Text">
                                    <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
                                </button>
                            </div>
                            <button type="button" class="btn-icon-square" @click="deleteItem(item)" title="Delete Creation" style="color: #f87171;">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</main>
@endsection
