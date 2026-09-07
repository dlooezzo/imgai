@extends('admin.layouts.app')

@section('title', 'SEO Management & Technical Audit')
@section('breadcrumb', 'Content & SEO / SEO')

@section('content')
<div x-data="{ activeTab: '{{ request()->query('tab', 'overview') }}' }" style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                SEO Management & Technical Optimization
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Control search engine crawlability, dynamic metadata, Open Graph social previews, and automated XML sitemaps.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="{{ url('sitemap.xml') }}" target="_blank" class="btn-admin btn-admin-secondary" style="font-size: 0.82rem;">
                <i data-lucide="map" style="width: 14px; height: 14px;"></i>
                <span>View XML Sitemap</span>
            </a>
            <a href="{{ url('robots.txt') }}" target="_blank" class="btn-admin btn-admin-secondary" style="font-size: 0.82rem;">
                <i data-lucide="bot" style="width: 14px; height: 14px;"></i>
                <span>View Robots.txt</span>
            </a>
            <form method="POST" action="{{ route('admin.seo.clear-cache') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn-admin btn-admin-primary" style="font-size: 0.82rem;">
                    <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                    <span>Flush SEO Cache</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 8px; border-bottom: 1px solid var(--admin-border); padding-bottom: 12px; overflow-x: auto;">
        <button type="button" class="btn-admin" :class="activeTab === 'overview' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="activeTab = 'overview'">
            <i data-lucide="bar-chart-3" style="width: 14px; height: 14px;"></i>
            <span>Overview & Health</span>
        </button>
        <button type="button" class="btn-admin" :class="activeTab === 'global' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="activeTab = 'global'">
            <i data-lucide="globe" style="width: 14px; height: 14px;"></i>
            <span>Global SEO & Social</span>
        </button>
        <button type="button" class="btn-admin" :class="activeTab === 'homepage' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="activeTab = 'homepage'">
            <i data-lucide="home" style="width: 14px; height: 14px;"></i>
            <span>Homepage & Tools</span>
        </button>
        <button type="button" class="btn-admin" :class="activeTab === 'verification' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="activeTab = 'verification'">
            <i data-lucide="check-shield" style="width: 14px; height: 14px;"></i>
            <span>Search Engine Verification</span>
        </button>
        <button type="button" class="btn-admin" :class="activeTab === 'robots' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="activeTab = 'robots'">
            <i data-lucide="file-text" style="width: 14px; height: 14px;"></i>
            <span>Sitemap & Robots.txt</span>
        </button>
        <button type="button" class="btn-admin" :class="activeTab === 'audit' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="activeTab = 'audit'">
            <i data-lucide="activity" style="width: 14px; height: 14px;"></i>
            <span>Technical SEO Audit</span>
        </button>
    </div>

    <!-- Tab 1: Overview & Health Statistics -->
    <div x-show="activeTab === 'overview'" style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Stats Matrix -->
        <div class="stats-grid" style="margin-bottom: 0;">
            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-label">Published Pages</span>
                    <span class="stat-value" style="color: #38bdf8;">{{ $stats['total_published'] }}</span>
                    <span class="stat-hint">Active CMS pages</span>
                </div>
                <div class="stat-icon-wrapper" style="color: #38bdf8; background: rgba(56, 189, 248, 0.12); border-color: rgba(56, 189, 248, 0.25);">
                    <i data-lucide="file-check"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-label">Indexable URLs</span>
                    <span class="stat-value" style="color: #34d399;">{{ $stats['total_indexable'] }}</span>
                    <span class="stat-hint">Crawlable by Google / Bing</span>
                </div>
                <div class="stat-icon-wrapper" style="color: #34d399; background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.25);">
                    <i data-lucide="search"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-label">Noindex Pages</span>
                    <span class="stat-value" style="color: #fbbf24;">{{ $stats['total_noindex'] }}</span>
                    <span class="stat-hint">Excluded from search</span>
                </div>
                <div class="stat-icon-wrapper" style="color: #fbbf24; background: rgba(245, 158, 11, 0.12); border-color: rgba(245, 158, 11, 0.25);">
                    <i data-lucide="eye-off"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-label">XML Sitemap</span>
                    <span class="stat-value" style="color: #c084fc; font-size: 1.15rem;">{{ $stats['sitemap_status'] }}</span>
                    <span class="stat-hint">Auto-updating endpoint</span>
                </div>
                <div class="stat-icon-wrapper" style="color: #c084fc; background: rgba(139, 92, 246, 0.12); border-color: rgba(139, 92, 246, 0.25);">
                    <i data-lucide="map"></i>
                </div>
            </div>
        </div>

        <!-- SEO Health Checklist -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="shield-check" style="width: 18px; height: 18px; color: var(--admin-emerald);"></i>
                        <span>Technical SEO Completeness Checklist</span>
                    </h2>
                    <p class="admin-card-subtitle">Real-time status of critical on-page and technical ranking parameters</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                <div style="background: rgba(15, 20, 34, 0.7); border: 1px solid var(--admin-border); border-radius: 10px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="{{ $stats['missing_title'] === 0 ? 'check-circle-2' : 'alert-triangle' }}" style="width: 18px; height: 18px; color: {{ $stats['missing_title'] === 0 ? '#34d399' : '#fbbf24' }};"></i>
                        <div>
                            <div style="font-size: 0.88rem; font-weight: 700; color: #f8fafc;">Custom Title Tags</div>
                            <div style="font-size: 0.75rem; color: #94a3b8;">{{ $stats['missing_title'] }} pages using default fallback</div>
                        </div>
                    </div>
                    <span class="badge {{ $stats['missing_title'] === 0 ? 'badge-success' : 'badge-warning' }}" style="font-size: 0.72rem;">
                        {{ $stats['missing_title'] === 0 ? 'Optimal' : 'Needs Review' }}
                    </span>
                </div>

                <div style="background: rgba(15, 20, 34, 0.7); border: 1px solid var(--admin-border); border-radius: 10px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="{{ $stats['missing_desc'] === 0 ? 'check-circle-2' : 'alert-triangle' }}" style="width: 18px; height: 18px; color: {{ $stats['missing_desc'] === 0 ? '#34d399' : '#fbbf24' }};"></i>
                        <div>
                            <div style="font-size: 0.88rem; font-weight: 700; color: #f8fafc;">Meta Descriptions</div>
                            <div style="font-size: 0.75rem; color: #94a3b8;">{{ $stats['missing_desc'] }} pages using content excerpts</div>
                        </div>
                    </div>
                    <span class="badge {{ $stats['missing_desc'] === 0 ? 'badge-success' : 'badge-warning' }}" style="font-size: 0.72rem;">
                        {{ $stats['missing_desc'] === 0 ? 'Optimal' : 'Review' }}
                    </span>
                </div>

                <div style="background: rgba(15, 20, 34, 0.7); border: 1px solid var(--admin-border); border-radius: 10px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="{{ $stats['missing_canonical'] === 0 ? 'check-circle-2' : 'check-circle' }}" style="width: 18px; height: 18px; color: #34d399;"></i>
                        <div>
                            <div style="font-size: 0.88rem; font-weight: 700; color: #f8fafc;">Canonical Tags</div>
                            <div style="font-size: 0.75rem; color: #94a3b8;">Auto-derived from APP_URL</div>
                        </div>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.72rem;">Active</span>
                </div>

                <div style="background: rgba(15, 20, 34, 0.7); border: 1px solid var(--admin-border); border-radius: 10px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="{{ $stats['google_verified'] ? 'check-circle-2' : 'alert-circle' }}" style="width: 18px; height: 18px; color: {{ $stats['google_verified'] ? '#34d399' : '#64748b' }};"></i>
                        <div>
                            <div style="font-size: 0.88rem; font-weight: 700; color: #f8fafc;">Google Search Console</div>
                            <div style="font-size: 0.75rem; color: #94a3b8;">{{ $stats['google_verified'] ? 'Verification token active' : 'Not configured' }}</div>
                        </div>
                    </div>
                    <span class="badge {{ $stats['google_verified'] ? 'badge-success' : 'badge-user' }}" style="font-size: 0.72rem;">
                        {{ $stats['google_verified'] ? 'Configured' : 'Optional' }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    <!-- Tab 2: Global SEO & Social Identity -->
    <div x-show="activeTab === 'global'" class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">
                    <i data-lucide="globe" style="width: 18px; height: 18px; color: var(--admin-primary);"></i>
                    <span>Global Site Identity & Default Social Previews</span>
                </h2>
                <p class="admin-card-subtitle">These values apply globally across all public pages as default fallbacks.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.seo.global') }}" style="display: flex; flex-direction: column; gap: 18px;">
            @csrf

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                        Site Name <span style="color: #f87171;">*</span>
                    </label>
                    <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required
                           style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 10px 14px; color: #f8fafc; font-size: 0.88rem; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                        Title Tag Template
                    </label>
                    <input type="text" name="title_format" value="{{ old('title_format', $settings['title_format']) }}" placeholder="%title% | %site_name%"
                           style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 10px 14px; color: #f8fafc; font-size: 0.88rem; outline: none;" class="font-mono">
                    <span style="font-size: 0.72rem; color: #64748b; margin-top: 4px; display: block;">Use <code>%title%</code> and <code>%site_name%</code> as dynamic placeholders.</span>
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                    Default Fallback SEO Title <span style="color: #f87171;">*</span>
                </label>
                <input type="text" name="default_title" value="{{ old('default_title', $settings['default_title']) }}" required
                       style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 10px 14px; color: #f8fafc; font-size: 0.88rem; outline: none;">
                <span style="font-size: 0.72rem; color: #64748b; margin-top: 4px; display: block;">Recommended length: 50–60 characters.</span>
            </div>

            <div>
                <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                    Default Meta Description <span style="color: #f87171;">*</span>
                </label>
                <textarea name="default_description" rows="3" required
                          style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 10px 14px; color: #cbd5e1; font-size: 0.85rem; outline: none; line-height: 1.4;">{{ old('default_description', $settings['default_description']) }}</textarea>
                <span style="font-size: 0.72rem; color: #64748b; margin-top: 4px; display: block;">Recommended length: 140–160 characters.</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                <!-- Default Open Graph Image -->
                <div x-data="seoImageUploader('{{ old('default_og_image', $settings['default_og_image']) }}', 'default_og_image')">
                    <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                        Default Open Graph Social Image
                    </label>
                    <input type="file" x-ref="fileInput" @change="handleFileChange($event)" accept="image/png,image/jpeg,image/webp,image/gif" style="display: none;">
                    <input type="hidden" name="default_og_image" :value="url">

                    <div style="display: flex; align-items: center; gap: 12px; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px 12px;">
                        <template x-if="url">
                            <div style="position: relative; width: 56px; height: 36px; border-radius: 6px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.1); flex-shrink: 0;">
                                <img :src="url" alt="OG Preview" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        </template>
                        <template x-if="!url">
                            <div style="width: 56px; height: 36px; border-radius: 6px; background: rgba(255,255,255,0.04); border: 1px dashed rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; color: #64748b; flex-shrink: 0;">
                                <i data-lucide="image" style="width: 16px; height: 16px;"></i>
                            </div>
                        </template>

                        <div style="flex-grow: 1; overflow: hidden;">
                            <input type="url" x-model="url" placeholder="https://..." class="font-mono"
                                   style="width: 100%; background: transparent; border: none; color: #f8fafc; font-size: 0.8rem; outline: none; text-overflow: ellipsis;">
                            <span x-show="errorMessage" x-text="errorMessage" style="color: #f87171; font-size: 0.72rem; display: block;"></span>
                        </div>

                        <div style="display: flex; gap: 6px; flex-shrink: 0;">
                            <button type="button" class="btn-micro" @click="triggerUpload()" :disabled="uploading" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border-color: rgba(99, 102, 241, 0.35); font-weight: 600;">
                                <span x-show="!uploading" style="display: flex; align-items: center; gap: 4px;">
                                    <i data-lucide="upload-cloud" style="width: 12px; height: 12px;"></i>
                                    <span x-text="url ? 'Replace' : 'Upload (R2)'"></span>
                                </span>
                                <span x-show="uploading">...</span>
                            </button>
                            <button type="button" class="btn-micro" @click="clearImage()" x-show="url" style="color: #f87171; border-color: rgba(239, 68, 68, 0.3);">
                                <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                            </button>
                        </div>
                    </div>
                    <span style="font-size: 0.72rem; color: #64748b; margin-top: 4px; display: block;">Used for Facebook, Discord, LinkedIn (1200x630px).</span>
                </div>

                <!-- Default Twitter Image -->
                <div x-data="seoImageUploader('{{ old('default_twitter_image', $settings['default_twitter_image']) }}', 'default_twitter_image')">
                    <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                        Default Twitter / X Card Image
                    </label>
                    <input type="file" x-ref="fileInput" @change="handleFileChange($event)" accept="image/png,image/jpeg,image/webp,image/gif" style="display: none;">
                    <input type="hidden" name="default_twitter_image" :value="url">

                    <div style="display: flex; align-items: center; gap: 12px; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px 12px;">
                        <template x-if="url">
                            <div style="position: relative; width: 56px; height: 36px; border-radius: 6px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.1); flex-shrink: 0;">
                                <img :src="url" alt="Twitter Preview" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        </template>
                        <template x-if="!url">
                            <div style="width: 56px; height: 36px; border-radius: 6px; background: rgba(255,255,255,0.04); border: 1px dashed rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; color: #64748b; flex-shrink: 0;">
                                <i data-lucide="image" style="width: 16px; height: 16px;"></i>
                            </div>
                        </template>

                        <div style="flex-grow: 1; overflow: hidden;">
                            <input type="url" x-model="url" placeholder="https://..." class="font-mono"
                                   style="width: 100%; background: transparent; border: none; color: #f8fafc; font-size: 0.8rem; outline: none; text-overflow: ellipsis;">
                            <span x-show="errorMessage" x-text="errorMessage" style="color: #f87171; font-size: 0.72rem; display: block;"></span>
                        </div>

                        <div style="display: flex; gap: 6px; flex-shrink: 0;">
                            <button type="button" class="btn-micro" @click="triggerUpload()" :disabled="uploading" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border-color: rgba(99, 102, 241, 0.35); font-weight: 600;">
                                <span x-show="!uploading" style="display: flex; align-items: center; gap: 4px;">
                                    <i data-lucide="upload-cloud" style="width: 12px; height: 12px;"></i>
                                    <span x-text="url ? 'Replace' : 'Upload (R2)'"></span>
                                </span>
                                <span x-show="uploading">...</span>
                            </button>
                            <button type="button" class="btn-micro" @click="clearImage()" x-show="url" style="color: #f87171; border-color: rgba(239, 68, 68, 0.3);">
                                <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                            </button>
                        </div>
                    </div>
                    <span style="font-size: 0.72rem; color: #64748b; margin-top: 4px; display: block;">Rendered with <code>summary_large_image</code> card.</span>
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                    Default Robots Directive
                </label>
                <select name="default_robots" class="admin-select" style="width: 100%; max-width: 320px;">
                    <option value="index, follow" {{ $settings['default_robots'] === 'index, follow' ? 'selected' : '' }}>index, follow (Standard Public Indexing)</option>
                    <option value="noindex, follow" {{ $settings['default_robots'] === 'noindex, follow' ? 'selected' : '' }}>noindex, follow (Crawl links, do not index)</option>
                    <option value="index, nofollow" {{ $settings['default_robots'] === 'index, nofollow' ? 'selected' : '' }}>index, nofollow (Index page, do not follow links)</option>
                    <option value="noindex, nofollow" {{ $settings['default_robots'] === 'noindex, nofollow' ? 'selected' : '' }}>noindex, nofollow (Completely Disallow)</option>
                </select>
            </div>

            <div style="margin-top: 8px;">
                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 22px;">
                    <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                    <span>Save Global Settings</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Tab 3: Homepage & Tool Specific SEO -->
    <div x-show="activeTab === 'homepage'" style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Homepage SEO Card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="home" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                        <span>Homepage (Studio Overview) SEO Metadata</span>
                    </h2>
                    <p class="admin-card-subtitle">Custom metadata rendered on root URL (/) and /tools/overview</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.seo.homepage') }}" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf

                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                        Homepage SEO Title
                    </label>
                    <input type="text" name="homepage_title" value="{{ old('homepage_title', $settings['homepage_title']) }}" required
                           style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 10px 14px; color: #f8fafc; font-size: 0.88rem; outline: none;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                        Homepage Meta Description
                    </label>
                    <textarea name="homepage_description" rows="2" required
                              style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 10px 14px; color: #cbd5e1; font-size: 0.85rem; outline: none; line-height: 1.4;">{{ old('homepage_description', $settings['homepage_description']) }}</textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 18px;">
                    <!-- Homepage OG Image Uploader -->
                    <div x-data="seoImageUploader('{{ old('homepage_og_image', $settings['homepage_og_image']) }}', 'homepage_og_image')">
                        <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                            Homepage Open Graph Image
                        </label>
                        <input type="file" x-ref="fileInput" @change="handleFileChange($event)" accept="image/png,image/jpeg,image/webp,image/gif" style="display: none;">
                        <input type="hidden" name="homepage_og_image" :value="url">

                        <div style="display: flex; align-items: center; gap: 12px; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px 12px;">
                            <template x-if="url">
                                <div style="position: relative; width: 56px; height: 36px; border-radius: 6px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.1); flex-shrink: 0;">
                                    <img :src="url" alt="Homepage OG Preview" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            </template>
                            <template x-if="!url">
                                <div style="width: 56px; height: 36px; border-radius: 6px; background: rgba(255,255,255,0.04); border: 1px dashed rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; color: #64748b; flex-shrink: 0;">
                                    <i data-lucide="image" style="width: 16px; height: 16px;"></i>
                                </div>
                            </template>

                            <div style="flex-grow: 1; overflow: hidden;">
                                <input type="url" x-model="url" placeholder="Leave blank to use default global social image" class="font-mono"
                                       style="width: 100%; background: transparent; border: none; color: #f8fafc; font-size: 0.8rem; outline: none; text-overflow: ellipsis;">
                                <span x-show="errorMessage" x-text="errorMessage" style="color: #f87171; font-size: 0.72rem; display: block;"></span>
                            </div>

                            <div style="display: flex; gap: 6px; flex-shrink: 0;">
                                <button type="button" class="btn-micro" @click="triggerUpload()" :disabled="uploading" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border-color: rgba(99, 102, 241, 0.35); font-weight: 600;">
                                    <span x-show="!uploading" style="display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="upload-cloud" style="width: 12px; height: 12px;"></i>
                                        <span x-text="url ? 'Replace' : 'Upload (R2)'"></span>
                                    </span>
                                    <span x-show="uploading">...</span>
                                </button>
                                <button type="button" class="btn-micro" @click="clearImage()" x-show="url" style="color: #f87171; border-color: rgba(239, 68, 68, 0.3);">
                                    <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                            Robots Directive
                        </label>
                        <select name="homepage_robots" class="admin-select" style="width: 100%;">
                            <option value="index, follow" {{ $settings['homepage_robots'] === 'index, follow' ? 'selected' : '' }}>index, follow</option>
                            <option value="noindex, nofollow" {{ $settings['homepage_robots'] === 'noindex, nofollow' ? 'selected' : '' }}>noindex, nofollow</option>
                        </select>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn-admin btn-admin-primary" style="padding: 9px 18px;">
                        <i data-lucide="save" style="width: 14px; height: 14px;"></i>
                        <span>Save Homepage SEO</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- AI Tools SEO Form -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="sparkles" style="width: 18px; height: 18px; color: var(--admin-purple);"></i>
                        <span>AI Creation Tools SEO Metadata</span>
                    </h2>
                    <p class="admin-card-subtitle">Dedicated ranking parameters for individual public generator interfaces</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.seo.tools') }}" style="display: flex; flex-direction: column; gap: 24px;">
                @csrf

                <!-- Tool 1: Text to Image -->
                <div style="background: rgba(15, 20, 34, 0.6); border: 1px solid var(--admin-border); border-radius: 10px; padding: 18px;">
                    <div style="font-weight: 700; color: #38bdf8; font-size: 0.95rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="image" style="width: 16px; height: 16px;"></i>
                        <span>Text-to-Image Generator (/tools/image-generator)</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">SEO Title</label>
                            <input type="text" name="tool_image_title" value="{{ old('tool_image_title', $settings['tool_image_title']) }}" required
                                   style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px 12px; color: #f8fafc; font-size: 0.86rem; outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">Meta Description</label>
                            <textarea name="tool_image_description" rows="2" required
                                      style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px 12px; color: #cbd5e1; font-size: 0.84rem; outline: none;">{{ old('tool_image_description', $settings['tool_image_description']) }}</textarea>
                        </div>
                        <div x-data="seoImageUploader('{{ old('tool_image_og_image', $settings['tool_image_og_image']) }}', 'tool_image_og_image')">
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">Custom Social Image</label>
                            <input type="file" x-ref="fileInput" @change="handleFileChange($event)" accept="image/png,image/jpeg,image/webp,image/gif" style="display: none;">
                            <input type="hidden" name="tool_image_og_image" :value="url">

                            <div style="display: flex; align-items: center; gap: 10px; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 6px 10px;">
                                <template x-if="url">
                                    <div style="width: 48px; height: 30px; border-radius: 4px; overflow: hidden; background: #000; flex-shrink: 0;">
                                        <img :src="url" alt="Tool Preview" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                </template>
                                <input type="url" x-model="url" placeholder="Leave blank to use global image" class="font-mono"
                                       style="flex-grow: 1; background: transparent; border: none; color: #f8fafc; font-size: 0.8rem; outline: none;">
                                <button type="button" class="btn-micro" @click="triggerUpload()" :disabled="uploading">
                                    <i data-lucide="upload-cloud" style="width: 12px; height: 12px;"></i>
                                    <span x-text="url ? 'Replace' : 'Upload (R2)'"></span>
                                </button>
                                <button type="button" class="btn-micro" @click="clearImage()" x-show="url" style="color: #f87171;">
                                    <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tool 2: Text to Video -->
                <div style="background: rgba(15, 20, 34, 0.6); border: 1px solid var(--admin-border); border-radius: 10px; padding: 18px;">
                    <div style="font-weight: 700; color: #c084fc; font-size: 0.95rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="video" style="width: 16px; height: 16px;"></i>
                        <span>Text-to-Video Generator (/tools/video-generator)</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">SEO Title</label>
                            <input type="text" name="tool_video_title" value="{{ old('tool_video_title', $settings['tool_video_title']) }}" required
                                   style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px 12px; color: #f8fafc; font-size: 0.86rem; outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">Meta Description</label>
                            <textarea name="tool_video_description" rows="2" required
                                      style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px 12px; color: #cbd5e1; font-size: 0.84rem; outline: none;">{{ old('tool_video_description', $settings['tool_video_description']) }}</textarea>
                        </div>
                        <div x-data="seoImageUploader('{{ old('tool_video_og_image', $settings['tool_video_og_image']) }}', 'tool_video_og_image')">
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">Custom Social Image</label>
                            <input type="file" x-ref="fileInput" @change="handleFileChange($event)" accept="image/png,image/jpeg,image/webp,image/gif" style="display: none;">
                            <input type="hidden" name="tool_video_og_image" :value="url">

                            <div style="display: flex; align-items: center; gap: 10px; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 6px 10px;">
                                <template x-if="url">
                                    <div style="width: 48px; height: 30px; border-radius: 4px; overflow: hidden; background: #000; flex-shrink: 0;">
                                        <img :src="url" alt="Tool Preview" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                </template>
                                <input type="url" x-model="url" placeholder="Leave blank to use global image" class="font-mono"
                                       style="flex-grow: 1; background: transparent; border: none; color: #f8fafc; font-size: 0.8rem; outline: none;">
                                <button type="button" class="btn-micro" @click="triggerUpload()" :disabled="uploading">
                                    <i data-lucide="upload-cloud" style="width: 12px; height: 12px;"></i>
                                    <span x-text="url ? 'Replace' : 'Upload (R2)'"></span>
                                </button>
                                <button type="button" class="btn-micro" @click="clearImage()" x-show="url" style="color: #f87171;">
                                    <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tool 3: Image to Video -->
                <div style="background: rgba(15, 20, 34, 0.6); border: 1px solid var(--admin-border); border-radius: 10px; padding: 18px;">
                    <div style="font-weight: 700; color: #f472b6; font-size: 0.95rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="clapperboard" style="width: 16px; height: 16px;"></i>
                        <span>Image-to-Video Generator (/tools/image-to-video)</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">SEO Title</label>
                            <input type="text" name="tool_i2v_title" value="{{ old('tool_i2v_title', $settings['tool_i2v_title']) }}" required
                                   style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px 12px; color: #f8fafc; font-size: 0.86rem; outline: none;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">Meta Description</label>
                            <textarea name="tool_i2v_description" rows="2" required
                                      style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px 12px; color: #cbd5e1; font-size: 0.84rem; outline: none;">{{ old('tool_i2v_description', $settings['tool_i2v_description']) }}</textarea>
                        </div>
                        <div x-data="seoImageUploader('{{ old('tool_i2v_og_image', $settings['tool_i2v_og_image']) }}', 'tool_i2v_og_image')">
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">Custom Social Image</label>
                            <input type="file" x-ref="fileInput" @change="handleFileChange($event)" accept="image/png,image/jpeg,image/webp,image/gif" style="display: none;">
                            <input type="hidden" name="tool_i2v_og_image" :value="url">

                            <div style="display: flex; align-items: center; gap: 10px; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 6px 10px;">
                                <template x-if="url">
                                    <div style="width: 48px; height: 30px; border-radius: 4px; overflow: hidden; background: #000; flex-shrink: 0;">
                                        <img :src="url" alt="Tool Preview" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                </template>
                                <input type="url" x-model="url" placeholder="Leave blank to use global image" class="font-mono"
                                       style="flex-grow: 1; background: transparent; border: none; color: #f8fafc; font-size: 0.8rem; outline: none;">
                                <button type="button" class="btn-micro" @click="triggerUpload()" :disabled="uploading">
                                    <i data-lucide="upload-cloud" style="width: 12px; height: 12px;"></i>
                                    <span x-text="url ? 'Replace' : 'Upload (R2)'"></span>
                                </button>
                                <button type="button" class="btn-micro" @click="clearImage()" x-show="url" style="color: #f87171;">
                                    <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                                </button>
                            </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 22px;">
                        <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                        <span>Save AI Tools SEO</span>
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- Tab 4: Search Engine Verification -->
    <div x-show="activeTab === 'verification'" class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">
                    <i data-lucide="check-shield" style="width: 18px; height: 18px; color: var(--admin-emerald);"></i>
                    <span>Search Engine Webmaster Verification</span>
                </h2>
                <p class="admin-card-subtitle">Connect and claim domain ownership in Google Search Console and Bing Webmaster Tools</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.seo.verification') }}" style="display: flex; flex-direction: column; gap: 20px;">
            @csrf

            <div>
                <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                    Google Search Console Verification Token
                </label>
                <input type="text" name="google_verification" value="{{ old('google_verification', $settings['google_verification']) }}" placeholder="e.g. google-site-verification=abc123xyz456 or just the verification string"
                       style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 10px 14px; color: #f8fafc; font-size: 0.88rem; outline: none;" class="font-mono">
                <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">
                    Renders <code>&lt;meta name="google-site-verification" content="..."&gt;</code> in <code>&lt;head&gt;</code>.
                </span>
            </div>

            <div>
                <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                    Bing Webmaster Tools Verification Token
                </label>
                <input type="text" name="bing_verification" value="{{ old('bing_verification', $settings['bing_verification']) }}" placeholder="e.g. 16-character hexadecimal authentication token"
                       style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 10px 14px; color: #f8fafc; font-size: 0.88rem; outline: none;" class="font-mono">
                <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">
                    Renders <code>&lt;meta name="msvalidate.01" content="..."&gt;</code> in <code>&lt;head&gt;</code>.
                </span>
            </div>

            <div>
                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 22px;">
                    <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                    <span>Save Verification Settings</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Tab 5: Sitemap & Robots.txt -->
    <div x-show="activeTab === 'robots'" style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Sitemap Overview -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="map" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                        <span>XML Sitemap Details</span>
                    </h2>
                    <p class="admin-card-subtitle">Auto-generated XML sitemap compatible with Google, Bing, and major search engine crawlers</p>
                </div>
                <a href="{{ url('sitemap.xml') }}" target="_blank" class="btn-admin btn-admin-secondary" style="font-size: 0.82rem;">
                    <i data-lucide="external-link" style="width: 13px; height: 13px;"></i>
                    <span>Open Live XML</span>
                </a>
            </div>

            <div style="background: rgba(10, 14, 26, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 16px; font-size: 0.84rem; color: #94a3b8; display: flex; flex-direction: column; gap: 8px;">
                <div><strong>Endpoint:</strong> <code style="color: var(--admin-cyan);">{{ url('sitemap.xml') }}</code></div>
                <div><strong>Protocol:</strong> Sitemaps.org 0.9 Schema Standard</div>
                <div><strong>Inclusions:</strong> Homepage (Priority 1.0), AI Tool Landers (Priority 0.9), Published CMS Pages (Priority 0.8)</div>
                <div><strong>Exclusions:</strong> Draft Pages, Noindex Pages, Admin Dashboard, Profile, User Media Libraries</div>
            </div>
        </div>

        <!-- Robots.txt Configurator -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="bot" style="width: 18px; height: 18px; color: var(--admin-emerald);"></i>
                        <span>Robots.txt Directives</span>
                    </h2>
                    <p class="admin-card-subtitle">Customize search engine crawler access rules and disallow directives</p>
                </div>
                <a href="{{ url('robots.txt') }}" target="_blank" class="btn-admin btn-admin-secondary" style="font-size: 0.82rem;">
                    <i data-lucide="external-link" style="width: 13px; height: 13px;"></i>
                    <span>Open Live Robots.txt</span>
                </a>
            </div>

            <form method="POST" action="{{ route('admin.seo.robots') }}" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf

                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                        Custom Robots.txt Content (Leave empty to use auto-optimized system defaults)
                    </label>
                    <textarea name="robots_txt_custom" rows="12" placeholder="Leave empty for auto-generated directives"
                              style="width: 100%; background: rgba(8, 11, 20, 0.9); border: 1px solid var(--admin-border); border-radius: 8px; padding: 14px; color: #f8fafc; font-size: 0.86rem; outline: none; line-height: 1.5;" class="font-mono">{{ old('robots_txt_custom', $settings['robots_txt_custom']) }}</textarea>
                </div>

                <div>
                    <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 22px;">
                        <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                        <span>Update Robots.txt</span>
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- Tab 6: Technical SEO Audit -->
    <div x-show="activeTab === 'audit'" class="admin-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 18px 24px; border-bottom: 1px solid var(--admin-border); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h2 class="admin-card-title">Technical SEO Audit Matrix</h2>
                <p class="admin-card-subtitle">Automated diagnosis of Title length (50-60 chars), Description length (140-160 chars), Canonical tags, and Social previews</p>
            </div>
            <span class="badge badge-success" style="font-size: 0.72rem;">{{ count($auditReport) }} Pages Analyzed</span>
        </div>

        <div class="admin-table-container" style="border: none; border-radius: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Page Name / Target</th>
                        <th>Status</th>
                        <th>SEO Health</th>
                        <th>Title Tag</th>
                        <th>Meta Description</th>
                        <th>Robots</th>
                        <th>Social Image</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($auditReport as $item)
                        <tr>
                            <td>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 700; color: #f8fafc;">{{ $item['name'] }}</span>
                                    <a href="{{ $item['url'] }}" target="_blank" class="font-mono" style="font-size: 0.74rem; color: var(--admin-cyan); text-decoration: none;">
                                        {{ $item['url'] }}
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $item['status'] === 'published' ? 'badge-success' : 'badge-warning' }}" style="font-size: 0.68rem;">
                                    {{ ucfirst($item['status']) }}
                                </span>
                            </td>
                            <td>
                                @if ($item['health'] === 'Good')
                                    <span class="badge badge-success" style="font-size: 0.7rem;">
                                        <i data-lucide="check" style="width: 11px; height: 11px;"></i> Good
                                    </span>
                                @elseif ($item['health'] === 'Warning')
                                    <span class="badge badge-warning" style="font-size: 0.7rem;">
                                        <i data-lucide="alert-triangle" style="width: 11px; height: 11px;"></i> Warning
                                    </span>
                                @else
                                    <span class="badge badge-admin" style="font-size: 0.7rem;">
                                        <i data-lucide="alert-circle" style="width: 11px; height: 11px;"></i> Attention
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 0.8rem; color: #cbd5e1; max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $item['title'] ?: 'Missing' }}
                                    </span>
                                    <span style="font-size: 0.7rem; color: {{ ($item['title_len'] >= 30 && $item['title_len'] <= 70) ? '#34d399' : '#fbbf24' }};">
                                        {{ $item['title_len'] }} chars
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 0.8rem; color: #cbd5e1; max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $item['description'] ?: 'Missing' }}
                                    </span>
                                    <span style="font-size: 0.7rem; color: {{ ($item['desc_len'] >= 80 && $item['desc_len'] <= 180) ? '#34d399' : '#fbbf24' }};">
                                        {{ $item['desc_len'] }} chars
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background: rgba(255,255,255,0.05); color: #cbd5e1; font-size: 0.68rem; font-family: monospace;">
                                    {{ $item['robots'] }}
                                </span>
                            </td>
                            <td>
                                @if (!empty($item['og_image']))
                                    <span class="badge badge-success" style="font-size: 0.68rem;">Configured</span>
                                @else
                                    <span class="badge badge-user" style="font-size: 0.68rem;">Fallback</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
window.seoImageUploader = function(initialUrl, fieldName) {
    return {
        url: initialUrl || '',
        uploading: false,
        errorMessage: '',
        triggerUpload() {
            if (this.$refs.fileInput) {
                this.$refs.fileInput.click();
            }
        },
        async handleFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 10 * 1024 * 1024) {
                this.errorMessage = 'File size exceeds 10MB limit.';
                return;
            }

            this.uploading = true;
            this.errorMessage = '';

            const formData = new FormData();
            formData.append('image', file);

            try {
                const tokenElem = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = tokenElem ? tokenElem.getAttribute('content') : '';

                const response = await fetch("{{ route('admin.seo.upload-image') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();
                if (data.success && data.url) {
                    this.url = data.url;
                    this.errorMessage = '';
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                } else {
                    this.errorMessage = data.message || 'Image upload to Cloudflare R2 / Storage failed.';
                }
            } catch (err) {
                this.errorMessage = 'Network error during image upload: ' + err.message;
            } finally {
                this.uploading = false;
                event.target.value = '';
            }
        },
        clearImage() {
            this.url = '';
            this.errorMessage = '';
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        }
    };
};
</script>
@endsection
