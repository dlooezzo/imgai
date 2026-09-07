@extends('admin.layouts.app')

@section('title', 'Branding & SEO')
@section('breadcrumb', 'System / Branding & SEO')

@section('content')
<div x-data="brandingSeoManager({{ json_encode($settings) }}, '{{ request()->query('tab', 'identity') }}')" style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Top Action Bar Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; background: var(--admin-card); border: 1px solid var(--admin-border); border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div>
            <h1 style="font-size: 1.45rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px; color: var(--admin-text-primary);">
                Branding & SEO Configuration
            </h1>
            <p style="color: var(--admin-text-muted); font-size: 0.86rem; margin: 0;">
                Unified management of brand identity, meta tags, AI tools search parameters, verification tokens, and XML sitemaps.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <a href="{{ url('sitemap.xml') }}" target="_blank" class="btn-admin btn-admin-secondary" style="font-size: 0.82rem; padding: 8px 14px;">
                <i data-lucide="map" style="width: 14px; height: 14px;"></i>
                <span>Sitemap</span>
            </a>
            <a href="{{ url('robots.txt') }}" target="_blank" class="btn-admin btn-admin-secondary" style="font-size: 0.82rem; padding: 8px 14px;">
                <i data-lucide="bot" style="width: 14px; height: 14px;"></i>
                <span>Robots.txt</span>
            </a>
            <button type="button" @click="flushCache()" class="btn-admin btn-admin-secondary" :disabled="cacheFlushing" style="font-size: 0.82rem; padding: 8px 14px;">
                <i data-lucide="refresh-cw" style="width: 14px; height: 14px;" :class="{ 'animate-spin': cacheFlushing }"></i>
                <span x-text="cacheFlushing ? 'Flushing...' : 'Flush Cache'"></span>
            </button>
            <button type="submit" form="branding-seo-form" class="btn-admin btn-admin-primary" style="padding: 9px 20px; font-weight: 700; font-size: 0.86rem;">
                <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                <span>Save All Branding & SEO Settings</span>
            </button>
        </div>
    </div>

    <!-- Navigation Tabs (7 Structured Sections) -->
    <div style="display: flex; gap: 8px; border-bottom: 1px solid var(--admin-border); padding-bottom: 12px; overflow-x: auto;">
        <button type="button" class="btn-admin" :class="activeTab === 'identity' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="setTab('identity')">
            <i data-lucide="palette" style="width: 14px; height: 14px;"></i>
            <span>1. Brand Identity</span>
        </button>
        <button type="button" class="btn-admin" :class="activeTab === 'homepage' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="setTab('homepage')">
            <i data-lucide="home" style="width: 14px; height: 14px;"></i>
            <span>2. Homepage SEO</span>
        </button>
        <button type="button" class="btn-admin" :class="activeTab === 'global' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="setTab('global')">
            <i data-lucide="globe" style="width: 14px; height: 14px;"></i>
            <span>3. Global SEO</span>
        </button>
        <button type="button" class="btn-admin" :class="activeTab === 'tools' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="setTab('tools')">
            <i data-lucide="sparkles" style="width: 14px; height: 14px;"></i>
            <span>4. AI Tools SEO</span>
        </button>
        <button type="button" class="btn-admin" :class="activeTab === 'verification' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="setTab('verification')">
            <i data-lucide="check-shield" style="width: 14px; height: 14px;"></i>
            <span>5. Search Verification</span>
        </button>
        <button type="button" class="btn-admin" :class="activeTab === 'sitemap' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="setTab('sitemap')">
            <i data-lucide="file-code" style="width: 14px; height: 14px;"></i>
            <span>6. Sitemap & Robots</span>
        </button>
        <button type="button" class="btn-admin" :class="activeTab === 'technical' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="setTab('technical')">
            <i data-lucide="activity" style="width: 14px; height: 14px;"></i>
            <span>7. Technical SEO</span>
        </button>
    </div>

    <!-- Master Form -->
    <form id="branding-seo-form" method="POST" action="{{ route('admin.branding-seo.update', [], false) }}" style="display: flex; flex-direction: column; gap: 24px;">
        @csrf
        <input type="hidden" name="active_tab" :value="activeTab">

        <!-- =================================================================== -->
        <!-- SECTION 1: Brand Identity                                          -->
        <!-- =================================================================== -->
        <div x-show="activeTab === 'identity'" style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Live Header Mockup Preview -->
            <div class="admin-card" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.9) 100%); border-color: rgba(99, 102, 241, 0.3);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="eye" style="width: 16px; height: 16px; color: var(--admin-cyan);"></i>
                        <span style="font-size: 0.8rem; font-weight: 700; color: #f8fafc; text-transform: uppercase; letter-spacing: 0.05em;">Live Header Mockup</span>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.7rem;">Real-time Update</span>
                </div>

                <div style="background: rgba(10, 15, 29, 0.95); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 12px 20px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <template x-if="form.site_logo">
                            <img :src="form.site_logo" :alt="form.site_title" style="max-height: 36px; width: auto; object-fit: contain;">
                        </template>
                        <template x-if="!form.site_logo">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <template x-if="form.site_logo_icon">
                                    <img :src="form.site_logo_icon" alt="Icon" style="width: 32px; height: 32px; border-radius: 8px; object-fit: contain;">
                                </template>
                                <template x-if="!form.site_logo_icon">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white;">
                                        <i data-lucide="sparkles" style="width: 16px; height: 16px;"></i>
                                    </div>
                                </template>
                                <div>
                                    <div style="font-weight: 800; font-size: 1rem; color: #fff;" x-text="form.site_title || 'IMGAI'"></div>
                                    <div style="font-size: 0.72rem; color: #94a3b8;" x-text="form.site_tagline || 'AI Creative Studio'"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div style="display: flex; align-items: center; gap: 16px; font-size: 0.8rem; color: #94a3b8;">
                        <span style="color: #fff; font-weight: 600;">Overview</span>
                        <span>Generate Image</span>
                        <span>Video Studio</span>
                    </div>
                </div>
            </div>

            <!-- Brand Information Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-card-title">
                            <i data-lucide="type" style="width: 18px; height: 18px; color: var(--admin-primary);"></i>
                            <span>Brand Identity Details</span>
                        </h2>
                        <p class="admin-card-subtitle">General website name, slogan, and copyright text rendered across user interfaces</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 6px;">
                            Website / Brand Name <span style="color: #f87171;">*</span>
                        </label>
                        <input type="text" name="site_title" x-model="form.site_title" placeholder="e.g. IMGAI Studio" required class="admin-input" style="width: 100%; box-sizing: border-box;">
                        <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Displays on the browser tab, navbar brand, and admin portal.</span>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 6px;">
                            Slogan / Tagline
                        </label>
                        <input type="text" name="site_tagline" x-model="form.site_tagline" placeholder="e.g. AI Creative Studio" class="admin-input" style="width: 100%; box-sizing: border-box;">
                        <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Displays under the brand name and on marketing badges.</span>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 6px;">
                        Footer Copyright & Attribution
                    </label>
                    <input type="text" name="site_footer_text" x-model="form.site_footer_text" placeholder="e.g. © 2026 IMGAI. All rights reserved." class="admin-input" style="width: 100%; box-sizing: border-box;">
                </div>
            </div>

            <!-- Logo & Favicon Assets Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-card-title">
                            <i data-lucide="image" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                            <span>Logo & Favicon Assets</span>
                        </h2>
                        <p class="admin-card-subtitle">Upload graphic logos or enter direct CDN image URLs (Cloudflare R2)</p>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <!-- 1. Main Website Logo -->
                    <div style="background: var(--admin-card-hover); border: 1px solid var(--admin-border); border-radius: 10px; padding: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem; color: var(--admin-text-primary); display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="layout" style="width: 15px; height: 15px; color: var(--admin-primary);"></i>
                                    <span>Main Website Logo (Full Header Logo)</span>
                                </div>
                                <span style="font-size: 0.74rem; color: var(--admin-text-muted);">Recommended: Transparent PNG or SVG (approx. 240x60px).</span>
                            </div>
                            <button type="button" class="btn-admin btn-admin-secondary" style="padding: 5px 12px; font-size: 0.78rem;" @click="triggerUpload('site_logo')">
                                <i data-lucide="upload-cloud" style="width: 13px; height: 13px;"></i>
                                <span x-text="form.site_logo ? 'Replace Logo' : 'Upload Logo (R2)'"></span>
                            </button>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 70px; height: 42px; border-radius: 6px; background: rgba(0,0,0,0.4); border: 1px dashed var(--admin-border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">
                                <template x-if="form.site_logo">
                                    <img :src="form.site_logo" alt="Logo" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                </template>
                                <template x-if="!form.site_logo">
                                    <span style="font-size: 0.68rem; color: var(--admin-text-muted);">No Logo</span>
                                </template>
                            </div>
                            <input type="url" name="site_logo" x-model="form.site_logo" placeholder="https://... or upload above" class="admin-input font-mono" style="flex-grow: 1; font-size: 0.82rem;">
                            <button type="button" class="btn-micro" @click="form.site_logo = ''" x-show="form.site_logo" style="color: #f87171;">
                                <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                            </button>
                        </div>
                    </div>

                    <!-- 2. Logo Icon / Mark -->
                    <div style="background: var(--admin-card-hover); border: 1px solid var(--admin-border); border-radius: 10px; padding: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem; color: var(--admin-text-primary); display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="square" style="width: 15px; height: 15px; color: var(--admin-cyan);"></i>
                                    <span>Logo Icon / Mark (Compact Square Icon)</span>
                                </div>
                                <span style="font-size: 0.74rem; color: var(--admin-text-muted);">Used in collapsed sidebar and mobile headers (approx. 64x64px).</span>
                            </div>
                            <button type="button" class="btn-admin btn-admin-secondary" style="padding: 5px 12px; font-size: 0.78rem;" @click="triggerUpload('site_logo_icon')">
                                <i data-lucide="upload-cloud" style="width: 13px; height: 13px;"></i>
                                <span x-text="form.site_logo_icon ? 'Replace Icon' : 'Upload Icon (R2)'"></span>
                            </button>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 44px; height: 42px; border-radius: 6px; background: rgba(0,0,0,0.4); border: 1px dashed var(--admin-border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">
                                <template x-if="form.site_logo_icon">
                                    <img :src="form.site_logo_icon" alt="Icon" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                </template>
                                <template x-if="!form.site_logo_icon">
                                    <span style="font-size: 0.68rem; color: var(--admin-text-muted);">No Icon</span>
                                </template>
                            </div>
                            <input type="url" name="site_logo_icon" x-model="form.site_logo_icon" placeholder="https://... or upload above" class="admin-input font-mono" style="flex-grow: 1; font-size: 0.82rem;">
                            <button type="button" class="btn-micro" @click="form.site_logo_icon = ''" x-show="form.site_logo_icon" style="color: #f87171;">
                                <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                            </button>
                        </div>
                    </div>

                    <!-- 3. Website Favicon -->
                    <div style="background: var(--admin-card-hover); border: 1px solid var(--admin-border); border-radius: 10px; padding: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <div style="font-weight: 700; font-size: 0.9rem; color: var(--admin-text-primary); display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="globe" style="width: 15px; height: 15px; color: var(--admin-emerald);"></i>
                                    <span>Website Favicon (Browser Tab Icon)</span>
                                </div>
                                <span style="font-size: 0.74rem; color: var(--admin-text-muted);">Displayed on browser tabs and bookmarks (Supports .ico, .png, .svg 32x32px).</span>
                            </div>
                            <button type="button" class="btn-admin btn-admin-secondary" style="padding: 5px 12px; font-size: 0.78rem;" @click="triggerUpload('site_favicon')">
                                <i data-lucide="upload-cloud" style="width: 13px; height: 13px;"></i>
                                <span x-text="form.site_favicon ? 'Replace Favicon' : 'Upload Favicon (R2)'"></span>
                            </button>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 44px; height: 42px; border-radius: 6px; background: rgba(0,0,0,0.4); border: 1px dashed var(--admin-border); display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">
                                <template x-if="form.site_favicon">
                                    <img :src="form.site_favicon" alt="Favicon" style="max-height: 24px; max-width: 24px; object-fit: contain;">
                                </template>
                                <template x-if="!form.site_favicon">
                                    <span style="font-size: 0.68rem; color: var(--admin-text-muted);">No Favicon</span>
                                </template>
                            </div>
                            <input type="url" name="site_favicon" x-model="form.site_favicon" placeholder="https://... or upload above" class="admin-input font-mono" style="flex-grow: 1; font-size: 0.82rem;">
                            <button type="button" class="btn-micro" @click="form.site_favicon = ''" x-show="form.site_favicon" style="color: #f87171;">
                                <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- SECTION 2: Homepage SEO                                            -->
        <!-- =================================================================== -->
        <div x-show="activeTab === 'homepage'" style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Real-time SERP Snippet Preview -->
            <div class="admin-card" style="background: #ffffff; border: 1px solid #dfe1e5; border-radius: 12px; padding: 20px;">
                <div style="font-size: 0.76rem; font-weight: 700; color: #5f6368; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.05em; display: flex; align-items: center; justify-content: space-between;">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="search" style="width: 14px; height: 14px; color: #1a73e8;"></i>
                        <span>Google Search Live SERP Preview</span>
                    </span>
                    <span style="font-size: 0.72rem; color: #1e8e3e; font-weight: 600;">Snippet Verified</span>
                </div>

                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <div style="width: 18px; height: 18px; border-radius: 50%; background: #e8eaed; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <template x-if="form.site_favicon">
                            <img :src="form.site_favicon" alt="Favicon" style="width: 14px; height: 14px; object-fit: contain;">
                        </template>
                        <template x-if="!form.site_favicon">
                            <span style="font-size: 9px; font-weight: bold; color: #5f6368;">AI</span>
                        </template>
                    </div>
                    <span style="font-size: 13px; color: #202124; font-weight: 500;" x-text="form.seo_site_name || form.site_title || 'IMGAI'"></span>
                    <span style="font-size: 12px; color: #5f6368;">{{ url('/') }}</span>
                </div>

                <div style="font-size: 1.18rem; color: #1a0dab; line-height: 1.3; font-weight: 500; text-decoration: none; cursor: pointer; margin-bottom: 4px;"
                     x-text="form.seo_homepage_title || 'IMGAI — Cinematic 2MP Image & Video AI Studio Platform'">
                </div>

                <div style="font-size: 0.88rem; color: #4d5156; line-height: 1.45; max-width: 680px;"
                     x-text="form.seo_homepage_description || 'Transform creative ideas into photorealistic 2MP visual assets and 24 FPS cinematic motion videos with Wan 2.2 and Hunyuan generative AI models.'">
                </div>
            </div>

            <!-- Homepage SEO Inputs -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-card-title">
                            <i data-lucide="home" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                            <span>Homepage SEO Metadata</span>
                        </h2>
                        <p class="admin-card-subtitle">Custom metadata rendered on root URL (/) and /tools/overview</p>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label style="font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary);">
                                Homepage SEO Title <span style="color: #f87171;">*</span>
                            </label>
                            <span style="font-size: 0.72rem;" :style="{ color: (form.seo_homepage_title || '').length > 60 ? '#f59e0b' : '#10b981' }">
                                <span x-text="(form.seo_homepage_title || '').length"></span>/60 characters
                            </span>
                        </div>
                        <input type="text" name="seo_homepage_title" x-model="form.seo_homepage_title" required class="admin-input" style="width: 100%; box-sizing: border-box;">
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label style="font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary);">
                                Homepage Meta Description <span style="color: #f87171;">*</span>
                            </label>
                            <span style="font-size: 0.72rem;" :style="{ color: (form.seo_homepage_description || '').length > 160 ? '#f59e0b' : '#10b981' }">
                                <span x-text="(form.seo_homepage_description || '').length"></span>/160 characters
                            </span>
                        </div>
                        <textarea name="seo_homepage_description" x-model="form.seo_homepage_description" rows="3" required class="admin-input" style="width: 100%; box-sizing: border-box; line-height: 1.5;"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                        <!-- Homepage OG Image -->
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label style="font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary);">
                                    Homepage Open Graph Image
                                </label>
                                <button type="button" class="btn-micro" @click="triggerUpload('seo_homepage_og_image')">
                                    <i data-lucide="upload-cloud" style="width: 12px; height: 12px;"></i>
                                    <span>Upload (R2)</span>
                                </button>
                            </div>
                            <input type="url" name="seo_homepage_og_image" x-model="form.seo_homepage_og_image" placeholder="Leave blank to use default global social image" class="admin-input font-mono" style="width: 100%; box-sizing: border-box; font-size: 0.82rem;">
                            <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Dedicated social share card for the root homepage (1200x630px).</span>
                        </div>

                        <!-- Robots Directive -->
                        <div>
                            <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 6px;">
                                Robots Directive
                            </label>
                            <select name="seo_homepage_robots" x-model="form.seo_homepage_robots" class="admin-select" style="width: 100%;">
                                <option value="index, follow">index, follow (Standard Public Indexing)</option>
                                <option value="noindex, follow">noindex, follow (Crawl links, do not index)</option>
                                <option value="index, nofollow">index, nofollow (Index page, do not follow links)</option>
                                <option value="noindex, nofollow">noindex, nofollow (Completely Disallow)</option>
                            </select>
                            <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Controls crawler behavior specifically on root domain.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- SECTION 3: Global SEO                                              -->
        <!-- =================================================================== -->
        <div x-show="activeTab === 'global'" style="display: flex; flex-direction: column; gap: 24px;">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-card-title">
                            <i data-lucide="globe" style="width: 18px; height: 18px; color: var(--admin-purple);"></i>
                            <span>Global SEO Identity & Defaults</span>
                        </h2>
                        <p class="admin-card-subtitle">Fallback title formats, default descriptions, and social graph cards for non-overridden pages</p>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                        <div>
                            <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 6px;">
                                Default SEO Title
                            </label>
                            <input type="text" name="seo_default_title" x-model="form.seo_default_title" class="admin-input" style="width: 100%; box-sizing: border-box;">
                            <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Fallback title rendered on pages without a custom title.</span>
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 6px;">
                                SEO Brand / Site Name
                            </label>
                            <input type="text" name="seo_site_name" x-model="form.seo_site_name" class="admin-input" style="width: 100%; box-sizing: border-box;">
                            <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Appended to dynamic tool pages and rendered in og:site_name.</span>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 6px;">
                            Title Format Template
                        </label>
                        <input type="text" name="seo_title_format" x-model="form.seo_title_format" class="admin-input font-mono" style="width: 100%; box-sizing: border-box; font-size: 0.86rem;">
                        <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Available tokens: <code>%title%</code>, <code>%site_name%</code>.</span>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 6px;">
                            Default Meta Description
                        </label>
                        <textarea name="seo_default_description" x-model="form.seo_default_description" rows="2" class="admin-input" style="width: 100%; box-sizing: border-box; line-height: 1.5;"></textarea>
                        <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Primary search snippet description for standard pages.</span>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 6px;">
                            Meta Keywords (Comma-Separated)
                        </label>
                        <input type="text" name="seo_meta_keywords" x-model="form.seo_meta_keywords" class="admin-input" style="width: 100%; box-sizing: border-box;">
                        <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Rendered in &lt;meta name="keywords"&gt; tag.</span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                        <!-- Default OG Image -->
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label style="font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary);">
                                    Default Open Graph Social Image
                                </label>
                                <button type="button" class="btn-micro" @click="triggerUpload('seo_default_og_image')">
                                    <i data-lucide="upload-cloud" style="width: 12px; height: 12px;"></i>
                                    <span>Upload (R2)</span>
                                </button>
                            </div>
                            <input type="url" name="seo_default_og_image" x-model="form.seo_default_og_image" placeholder="https://..." class="admin-input font-mono" style="width: 100%; box-sizing: border-box; font-size: 0.82rem;">
                            <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Rendered on Facebook, LinkedIn, Discord previews (1200x630px).</span>
                        </div>

                        <!-- Default Twitter Image -->
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label style="font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary);">
                                    Default Twitter / X Card Image
                                </label>
                                <button type="button" class="btn-micro" @click="triggerUpload('seo_default_twitter_image')">
                                    <i data-lucide="upload-cloud" style="width: 12px; height: 12px;"></i>
                                    <span>Upload (R2)</span>
                                </button>
                            </div>
                            <input type="url" name="seo_default_twitter_image" x-model="form.seo_default_twitter_image" placeholder="https://..." class="admin-input font-mono" style="width: 100%; box-sizing: border-box; font-size: 0.82rem;">
                            <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Rendered in summary_large_image Twitter card.</span>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 6px;">
                            Global Search Engine Indexing (Default Robots)
                        </label>
                        <select name="seo_default_robots" x-model="form.seo_default_robots" class="admin-select" style="max-width: 380px; width: 100%;">
                            <option value="index, follow">index, follow (Recommended - Full Public Indexing)</option>
                            <option value="noindex, follow">noindex, follow (Crawl links, do not index)</option>
                            <option value="index, nofollow">index, nofollow (Index page, do not follow links)</option>
                            <option value="noindex, nofollow">noindex, nofollow (Completely Disallow)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- SECTION 4: AI Tools SEO                                            -->
        <!-- =================================================================== -->
        <div x-show="activeTab === 'tools'" style="display: flex; flex-direction: column; gap: 24px;">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-card-title">
                            <i data-lucide="sparkles" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                            <span>AI Creation Tools SEO Parameters</span>
                        </h2>
                        <p class="admin-card-subtitle">Dedicated search titles, descriptions, and social previews for public AI tools</p>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <!-- Tool 1: Text-to-Image -->
                    <div style="background: var(--admin-card-hover); border: 1px solid var(--admin-border); border-radius: 12px; padding: 20px;">
                        <div style="font-weight: 700; color: var(--admin-cyan); font-size: 0.96rem; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="image" style="width: 17px; height: 17px;"></i>
                            <span>Text-to-Image Generator (/tools/image-generator)</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 4px;">SEO Title</label>
                                <input type="text" name="seo_tool_image_title" x-model="form.seo_tool_image_title" class="admin-input" style="width: 100%; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 4px;">Meta Description</label>
                                <textarea name="seo_tool_image_description" x-model="form.seo_tool_image_description" rows="2" class="admin-input" style="width: 100%; box-sizing: border-box; line-height: 1.5;"></textarea>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label style="font-size: 0.8rem; font-weight: 700; color: var(--admin-text-primary);">Social Image</label>
                                    <button type="button" class="btn-micro" @click="triggerUpload('seo_tool_image_og_image')">
                                        <i data-lucide="upload-cloud" style="width: 12px; height: 12px;"></i>
                                        <span>Upload (R2)</span>
                                    </button>
                                </div>
                                <input type="url" name="seo_tool_image_og_image" x-model="form.seo_tool_image_og_image" placeholder="Leave blank to use default global social image" class="admin-input font-mono" style="width: 100%; box-sizing: border-box; font-size: 0.82rem;">
                            </div>
                        </div>
                    </div>

                    <!-- Tool 2: Text-to-Video -->
                    <div style="background: var(--admin-card-hover); border: 1px solid var(--admin-border); border-radius: 12px; padding: 20px;">
                        <div style="font-weight: 700; color: var(--admin-purple); font-size: 0.96rem; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="video" style="width: 17px; height: 17px;"></i>
                            <span>Text-to-Video Generator (/tools/video-generator)</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 4px;">SEO Title</label>
                                <input type="text" name="seo_tool_video_title" x-model="form.seo_tool_video_title" class="admin-input" style="width: 100%; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 4px;">Meta Description</label>
                                <textarea name="seo_tool_video_description" x-model="form.seo_tool_video_description" rows="2" class="admin-input" style="width: 100%; box-sizing: border-box; line-height: 1.5;"></textarea>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label style="font-size: 0.8rem; font-weight: 700; color: var(--admin-text-primary);">Social Image</label>
                                    <button type="button" class="btn-micro" @click="triggerUpload('seo_tool_video_og_image')">
                                        <i data-lucide="upload-cloud" style="width: 12px; height: 12px;"></i>
                                        <span>Upload (R2)</span>
                                    </button>
                                </div>
                                <input type="url" name="seo_tool_video_og_image" x-model="form.seo_tool_video_og_image" placeholder="Leave blank to use default global social image" class="admin-input font-mono" style="width: 100%; box-sizing: border-box; font-size: 0.82rem;">
                            </div>
                        </div>
                    </div>

                    <!-- Tool 3: Image-to-Video -->
                    <div style="background: var(--admin-card-hover); border: 1px solid var(--admin-border); border-radius: 12px; padding: 20px;">
                        <div style="font-weight: 700; color: var(--admin-rose); font-size: 0.96rem; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="clapperboard" style="width: 17px; height: 17px;"></i>
                            <span>Image-to-Video Generator (/tools/image-to-video)</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 4px;">SEO Title</label>
                                <input type="text" name="seo_tool_i2v_title" x-model="form.seo_tool_i2v_title" class="admin-input" style="width: 100%; box-sizing: border-box;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 4px;">Meta Description</label>
                                <textarea name="seo_tool_i2v_description" x-model="form.seo_tool_i2v_description" rows="2" class="admin-input" style="width: 100%; box-sizing: border-box; line-height: 1.5;"></textarea>
                            </div>
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <label style="font-size: 0.8rem; font-weight: 700; color: var(--admin-text-primary);">Social Image</label>
                                    <button type="button" class="btn-micro" @click="triggerUpload('seo_tool_i2v_og_image')">
                                        <i data-lucide="upload-cloud" style="width: 12px; height: 12px;"></i>
                                        <span>Upload (R2)</span>
                                    </button>
                                </div>
                                <input type="url" name="seo_tool_i2v_og_image" x-model="form.seo_tool_i2v_og_image" placeholder="Leave blank to use default global social image" class="admin-input font-mono" style="width: 100%; box-sizing: border-box; font-size: 0.82rem;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- SECTION 5: Search Engine Verification                              -->
        <!-- =================================================================== -->
        <div x-show="activeTab === 'verification'" style="display: flex; flex-direction: column; gap: 24px;">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-card-title">
                            <i data-lucide="check-shield" style="width: 18px; height: 18px; color: var(--admin-emerald);"></i>
                            <span>Search Engine Webmaster Verification</span>
                        </h2>
                        <p class="admin-card-subtitle">Ownership verification tokens injected directly into the document &lt;head&gt;</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 6px;">
                            Google Search Console Verification Token
                        </label>
                        <input type="text" name="seo_google_verification" x-model="form.seo_google_verification" placeholder="e.g. 7q8_abc123..." class="admin-input font-mono" style="width: 100%; box-sizing: border-box; font-size: 0.84rem;">
                        <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Injected into &lt;meta name="google-site-verification"&gt;.</span>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary); margin-bottom: 6px;">
                            Bing Webmaster Verification Token
                        </label>
                        <input type="text" name="seo_bing_verification" x-model="form.seo_bing_verification" placeholder="e.g. 384C12..." class="admin-input font-mono" style="width: 100%; box-sizing: border-box; font-size: 0.84rem;">
                        <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Injected into &lt;meta name="msvalidate.01"&gt;.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- SECTION 6: Sitemap & Robots                                        -->
        <!-- =================================================================== -->
        <div x-show="activeTab === 'sitemap'" style="display: flex; flex-direction: column; gap: 24px;">
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-card-title">
                            <i data-lucide="file-code" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                            <span>Sitemap & Robots.txt Directives</span>
                        </h2>
                        <p class="admin-card-subtitle">Automated XML sitemap endpoints and custom crawler rules</p>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <!-- Sitemap Status Box -->
                    <div style="background: var(--admin-card-hover); border: 1px solid var(--admin-border); border-radius: 10px; padding: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(56, 189, 248, 0.1); color: var(--admin-cyan); display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="map" style="width: 20px; height: 20px;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 0.92rem; color: var(--admin-text-primary);">XML Sitemap Status</div>
                                <div style="font-size: 0.76rem; color: var(--admin-text-muted);">Dynamically generated at <code>/sitemap.xml</code> with published CMS pages and public tools.</div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px;">
                            <a href="{{ url('sitemap.xml') }}" target="_blank" class="btn-admin btn-admin-secondary" style="font-size: 0.8rem; padding: 6px 14px;">
                                <i data-lucide="external-link" style="width: 13px; height: 13px;"></i>
                                <span>Open Sitemap</span>
                            </a>
                        </div>
                    </div>

                    <!-- Robots.txt Custom Editor -->
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <label style="font-size: 0.84rem; font-weight: 700; color: var(--admin-text-primary);">
                                Custom Robots.txt Rules
                            </label>
                            <a href="{{ url('robots.txt') }}" target="_blank" style="font-size: 0.74rem; color: var(--admin-primary); text-decoration: underline;">
                                View Current Live Robots.txt
                            </a>
                        </div>
                        <textarea name="seo_robots_txt_custom" x-model="form.seo_robots_txt_custom" rows="8" placeholder="User-agent: *&#10;Disallow: /admin&#10;Disallow: /auth/&#10;Allow: /" class="admin-input font-mono" style="width: 100%; box-sizing: border-box; line-height: 1.5; font-size: 0.82rem;"></textarea>
                        <span style="font-size: 0.72rem; color: var(--admin-text-muted); margin-top: 4px; display: block;">Leave blank to use system auto-optimized rules.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- SECTION 7: Technical SEO                                           -->
        <!-- =================================================================== -->
        <div x-show="activeTab === 'technical'" style="display: flex; flex-direction: column; gap: 24px;">
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
                        <span class="stat-hint">Crawlable by Search Engines</span>
                    </div>
                    <div class="stat-icon-wrapper" style="color: #34d399; background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.25);">
                        <i data-lucide="search"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-content">
                        <span class="stat-label">Noindex Pages</span>
                        <span class="stat-value" style="color: #fbbf24;">{{ $stats['total_noindex'] }}</span>
                        <span class="stat-hint">Excluded from search index</span>
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
                            <i data-lucide="activity" style="width: 18px; height: 18px; color: var(--admin-emerald);"></i>
                            <span>Automated SEO Health Checklist</span>
                        </h2>
                        <p class="admin-card-subtitle">Real-time technical integrity audit</p>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--admin-card-hover); border-radius: 8px; border: 1px solid var(--admin-border);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i data-lucide="check-circle" style="width: 18px; height: 18px; color: #10b981;"></i>
                            <span style="font-size: 0.88rem; font-weight: 600; color: var(--admin-text-primary);">Canonical HTTPS URLs Enforced</span>
                        </div>
                        <span class="badge badge-success">Optimized</span>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--admin-card-hover); border-radius: 8px; border: 1px solid var(--admin-border);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i data-lucide="check-circle" style="width: 18px; height: 18px; color: #10b981;"></i>
                            <span style="font-size: 0.88rem; font-weight: 600; color: var(--admin-text-primary);">XML Sitemap Auto-Generation</span>
                        </div>
                        <span class="badge badge-success">Active</span>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--admin-card-hover); border-radius: 8px; border: 1px solid var(--admin-border);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i data-lucide="{{ $stats['google_verified'] ? 'check-circle' : 'alert-circle' }}" style="width: 18px; height: 18px; color: {{ $stats['google_verified'] ? '#10b981' : '#f59e0b' }};"></i>
                            <span style="font-size: 0.88rem; font-weight: 600; color: var(--admin-text-primary);">Google Search Console Verification</span>
                        </div>
                        <span class="badge {{ $stats['google_verified'] ? 'badge-success' : 'badge-warning' }}">
                            {{ $stats['google_verified'] ? 'Configured' : 'Missing Token' }}
                        </span>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--admin-card-hover); border-radius: 8px; border: 1px solid var(--admin-border);">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i data-lucide="{{ $stats['bing_verified'] ? 'check-circle' : 'alert-circle' }}" style="width: 18px; height: 18px; color: {{ $stats['bing_verified'] ? '#10b981' : '#f59e0b' }};"></i>
                            <span style="font-size: 0.88rem; font-weight: 600; color: var(--admin-text-primary);">Bing Webmaster Verification</span>
                        </div>
                        <span class="badge {{ $stats['bing_verified'] ? 'badge-success' : 'badge-warning' }}">
                            {{ $stats['bing_verified'] ? 'Configured' : 'Missing Token' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Save Action Bar -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; background: var(--admin-card); border: 1px solid var(--admin-border); border-radius: 12px; padding: 18px 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: var(--admin-text-muted);">
                <i data-lucide="shield-check" style="width: 16px; height: 16px; color: #10b981;"></i>
                <span>All branding assets and SEO tags will be validated and updated instantly.</span>
            </div>

            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px; font-weight: 700; font-size: 0.88rem;">
                <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                <span>Save All Branding & SEO Settings</span>
            </button>
        </div>

    </form>

    <!-- Hidden Generic File Input for Uploads -->
    <input type="file" id="branding-seo-generic-file-input" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml,image/x-icon,image/vnd.microsoft.icon" style="display: none;" @change="handleFileUpload($event)">

</div>

<script>
function brandingSeoManager(initialData, initialTab) {
    return {
        activeTab: initialTab || 'identity',
        cacheFlushing: false,
        uploadingField: null,
        form: { ...initialData },

        setTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        },

        triggerUpload(fieldName) {
            this.uploadingField = fieldName;
            const input = document.getElementById('branding-seo-generic-file-input');
            if (input) {
                input.value = '';
                input.click();
            }
        },

        async handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file || !this.uploadingField) return;

            const targetField = this.uploadingField;
            const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const formData = new FormData();
            formData.append('image', file);
            if (metaToken) {
                formData.append('_token', metaToken);
            }

            try {
                const res = await fetch("{{ route('admin.branding-seo.upload-image', [], false) }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': metaToken || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (res.status === 419) {
                    alert('Session expired or CSRF mismatch. Please refresh the page and try again.');
                    return;
                }

                const data = await res.json();
                if (data.success && data.url) {
                    this.form[targetField] = data.url;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                } else {
                    alert(data.message || 'Image upload failed.');
                }
            } catch (err) {
                alert('Upload error: ' + err.message);
            } finally {
                this.uploadingField = null;
                event.target.value = '';
            }
        },

        async flushCache() {
            this.cacheFlushing = true;
            const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const res = await fetch("{{ route('admin.branding-seo.clear-cache', [], false) }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': metaToken || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await res.json();
                if (data.success) {
                    alert('SEO Cache and XML sitemap have been successfully cleared!');
                } else {
                    alert('Could not clear cache.');
                }
            } catch (e) {
                alert('Flush error: ' + e.message);
            } finally {
                this.cacheFlushing = false;
            }
        }
    };
}
</script>
@endsection
