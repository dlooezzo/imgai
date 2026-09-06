@extends('admin.layouts.app')

@section('title', 'Site Branding, Logo & Technical SEO')
@section('breadcrumb', 'System / Branding & Technical SEO')

@section('content')
<div x-data="brandingManager({{ json_encode($settings) }})" style="display: flex; flex-direction: column; gap: 28px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Site Branding & Logo Configuration
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Manage website brand identity, logos, slogans, favicon, and Technical SEO Title & Metadata across search engines.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="{{ route('admin.seo.index') }}" class="btn-admin btn-admin-secondary" style="font-size: 0.82rem;">
                <i data-lucide="compass" style="width: 15px; height: 15px;"></i>
                <span>Open SEO Audit Center</span>
            </a>
            <button type="submit" form="branding-form" class="btn-admin btn-admin-primary">
                <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                <span>Save All Settings</span>
            </button>
        </div>
    </div>

    <!-- Live Preview Banner -->
    <div class="admin-card" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(30, 41, 59, 0.6) 100%); border-color: rgba(99, 102, 241, 0.25);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i data-lucide="eye" style="width: 16px; height: 16px; color: var(--admin-cyan);"></i>
                <span style="font-size: 0.84rem; font-weight: 700; color: #f8fafc; text-transform: uppercase; letter-spacing: 0.05em;">Live Header Mockup Preview</span>
            </div>
            <span class="badge badge-success" style="font-size: 0.72rem;">Real-time Update</span>
        </div>

        <!-- Simulated Header -->
        <div style="background: rgba(10, 15, 29, 0.95); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 14px 22px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <!-- Logo Preview -->
                <template x-if="form.site_logo">
                    <img :src="form.site_logo" :alt="form.site_title" style="max-height: 38px; width: auto; object-fit: contain;">
                </template>
                <template x-if="!form.site_logo">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <template x-if="form.site_logo_icon">
                            <img :src="form.site_logo_icon" alt="Icon" style="width: 32px; height: 32px; border-radius: 8px; object-fit: contain;">
                        </template>
                        <template x-if="!form.site_logo_icon">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white;">
                                <i data-lucide="sparkles" style="width: 18px; height: 18px;"></i>
                            </div>
                        </template>
                        <div>
                            <div style="font-weight: 800; font-size: 1.05rem; color: #fff; letter-spacing: -0.01em;" x-text="form.site_title || 'IMGAI'"></div>
                            <div style="font-size: 0.74rem; color: #94a3b8;" x-text="form.site_tagline || 'AI Creative Studio'"></div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Simulated Tabs -->
            <div style="display: flex; align-items: center; gap: 16px; font-size: 0.82rem; color: #94a3b8;">
                <span style="color: #fff; font-weight: 600;">Overview</span>
                <span>Generate Image</span>
                <span>Video Studio</span>
            </div>
        </div>
    </div>

    <!-- Main Branding & Technical SEO Form -->
    <form id="branding-form" method="POST" action="{{ route('admin.branding.update') }}" style="display: flex; flex-direction: column; gap: 24px;">
        @csrf

        <!-- Card 1: Brand Title & Slogan -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="type" style="width: 18px; height: 18px; color: var(--admin-primary);"></i>
                        <span>Website Title & Slogan</span>
                    </h2>
                    <p class="admin-card-subtitle">General brand naming rendered in headers, page titles, and meta tags</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                <!-- Website Title -->
                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        Website Name / Brand Title <span style="color: #f87171;">*</span>
                    </label>
                    <input type="text" name="site_title" x-model="form.site_title" placeholder="e.g. IMGAI Studio" required class="admin-input" style="width: 100%; box-sizing: border-box; font-size: 0.9rem;">
                    <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Displays on the browser tab, header title, and admin portal.</span>
                </div>

                <!-- Slogan / Tagline -->
                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        Slogan / Tagline
                    </label>
                    <input type="text" name="site_tagline" x-model="form.site_tagline" placeholder="e.g. AI Creative Studio" class="admin-input" style="width: 100%; box-sizing: border-box; font-size: 0.9rem;">
                    <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Displays below the brand name and on the studio hero badge.</span>
                </div>
            </div>

            <div style="margin-top: 16px;">
                <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                    Footer Copyright & Attribution Text
                </label>
                <input type="text" name="site_footer_text" x-model="form.site_footer_text" placeholder="e.g. © 2026 IMGAI. All rights reserved." class="admin-input" style="width: 100%; box-sizing: border-box; font-size: 0.86rem;">
            </div>
        </div>

        <!-- Card 2: Logo & Image Assets -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="image" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                        <span>Logo & Favicon Assets</span>
                    </h2>
                    <p class="admin-card-subtitle">Upload graphic logos or enter direct CDN image URLs (Cloudflare R2 / Storage)</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 24px;">

                <!-- 1. Main Full Logo -->
                <div style="background: rgba(15, 20, 34, 0.7); border: 1px solid var(--admin-border); border-radius: 12px; padding: 18px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.92rem; color: #f8fafc; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="layout" style="width: 16px; height: 16px; color: var(--admin-primary);"></i>
                                <span>Main Website Logo (Full Header Logo)</span>
                            </div>
                            <div style="font-size: 0.76rem; color: #94a3b8; margin-top: 2px;">
                                Recommended: Transparent PNG or SVG (approx. 240×60px). Replaces text title when set.
                            </div>
                        </div>

                        <div>
                            <button type="button" class="btn-admin btn-admin-secondary" style="padding: 6px 14px; font-size: 0.8rem;" @click="triggerUpload('site_logo')" :disabled="uploadingField === 'site_logo'">
                                <span x-show="uploadingField !== 'site_logo'" style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="upload-cloud" style="width: 14px; height: 14px;"></i>
                                    <span x-text="form.site_logo ? 'Replace Logo' : 'Upload Logo (R2)'"></span>
                                </span>
                                <span x-show="uploadingField === 'site_logo'" style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i>
                                    <span>Uploading...</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <!-- Logo Preview Box -->
                        <div style="width: 180px; height: 64px; border-radius: 8px; background: rgba(0,0,0,0.5); border: 1px dashed rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 6px; flex-shrink: 0;">
                            <template x-if="form.site_logo">
                                <img :src="form.site_logo" alt="Main Logo Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            </template>
                            <template x-if="!form.site_logo">
                                <span style="font-size: 0.76rem; color: #64748b;">No Logo Uploaded</span>
                            </template>
                        </div>

                        <!-- URL Input -->
                        <div style="flex: 1; min-width: 250px; display: flex; align-items: center; gap: 8px;">
                            <input type="text" name="site_logo" x-model="form.site_logo" placeholder="https://... or upload above" class="admin-input font-mono" style="flex: 1; font-size: 0.84rem; color: var(--admin-cyan);">
                            <button type="button" class="btn-admin btn-admin-secondary" style="padding: 10px 12px; color: #f87171;" @click="form.site_logo = ''" x-show="form.site_logo" title="Remove logo">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 2. Compact Logo Icon -->
                <div style="background: rgba(15, 20, 34, 0.7); border: 1px solid var(--admin-border); border-radius: 12px; padding: 18px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.92rem; color: #f8fafc; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="square" style="width: 16px; height: 16px; color: var(--admin-cyan);"></i>
                                <span>Logo Icon / Mark (Compact Square Icon)</span>
                            </div>
                            <div style="font-size: 0.76rem; color: #94a3b8; margin-top: 2px;">
                                Square logo mark used in the sidebar and collapsed mobile headers (e.g. 64×64px).
                            </div>
                        </div>

                        <div>
                            <button type="button" class="btn-admin btn-admin-secondary" style="padding: 6px 14px; font-size: 0.8rem;" @click="triggerUpload('site_logo_icon')" :disabled="uploadingField === 'site_logo_icon'">
                                <span x-show="uploadingField !== 'site_logo_icon'" style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="upload-cloud" style="width: 14px; height: 14px;"></i>
                                    <span x-text="form.site_logo_icon ? 'Replace Icon' : 'Upload Icon (R2)'"></span>
                                </span>
                                <span x-show="uploadingField === 'site_logo_icon'" style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i>
                                    <span>Uploading...</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <!-- Icon Preview Box -->
                        <div style="width: 64px; height: 64px; border-radius: 8px; background: rgba(0,0,0,0.5); border: 1px dashed rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 6px; flex-shrink: 0;">
                            <template x-if="form.site_logo_icon">
                                <img :src="form.site_logo_icon" alt="Icon Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            </template>
                            <template x-if="!form.site_logo_icon">
                                <span style="font-size: 0.7rem; color: #64748b;">No Icon</span>
                            </template>
                        </div>

                        <!-- URL Input -->
                        <div style="flex: 1; min-width: 250px; display: flex; align-items: center; gap: 8px;">
                            <input type="text" name="site_logo_icon" x-model="form.site_logo_icon" placeholder="https://... or upload above" class="admin-input font-mono" style="flex: 1; font-size: 0.84rem; color: var(--admin-cyan);">
                            <button type="button" class="btn-admin btn-admin-secondary" style="padding: 10px 12px; color: #f87171;" @click="form.site_logo_icon = ''" x-show="form.site_logo_icon" title="Remove icon">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 3. Website Favicon -->
                <div style="background: rgba(15, 20, 34, 0.7); border: 1px solid var(--admin-border); border-radius: 12px; padding: 18px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.92rem; color: #f8fafc; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="globe" style="width: 16px; height: 16px; color: var(--admin-emerald);"></i>
                                <span>Website Favicon (Browser Tab Icon)</span>
                            </div>
                            <div style="font-size: 0.76rem; color: #94a3b8; margin-top: 2px;">
                                Displayed in browser tabs and bookmarks bar (Supports .ico, .png, .svg, 32×32px).
                            </div>
                        </div>

                        <div>
                            <button type="button" class="btn-admin btn-admin-secondary" style="padding: 6px 14px; font-size: 0.8rem;" @click="triggerUpload('site_favicon')" :disabled="uploadingField === 'site_favicon'">
                                <span x-show="uploadingField !== 'site_favicon'" style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="upload-cloud" style="width: 14px; height: 14px;"></i>
                                    <span x-text="form.site_favicon ? 'Replace Favicon' : 'Upload Favicon (R2)'"></span>
                                </span>
                                <span x-show="uploadingField === 'site_favicon'" style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i>
                                    <span>Uploading...</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <!-- Favicon Browser Tab Mock Preview -->
                        <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 8px 14px; display: flex; align-items: center; gap: 8px; font-size: 0.78rem; color: #cbd5e1; flex-shrink: 0;">
                            <template x-if="form.site_favicon">
                                <img :src="form.site_favicon" alt="Favicon Preview" style="width: 16px; height: 16px; object-fit: contain;">
                            </template>
                            <template x-if="!form.site_favicon">
                                <i data-lucide="globe" style="width: 16px; height: 16px; color: #94a3b8;"></i>
                            </template>
                            <span x-text="(form.site_title || 'IMGAI') + ' - Studio'"></span>
                            <span style="opacity: 0.5; margin-left: 6px;">&times;</span>
                        </div>

                        <!-- URL Input -->
                        <div style="flex: 1; min-width: 250px; display: flex; align-items: center; gap: 8px;">
                            <input type="text" name="site_favicon" x-model="form.site_favicon" placeholder="https://... or upload above" class="admin-input font-mono" style="flex: 1; font-size: 0.84rem; color: var(--admin-cyan);">
                            <button type="button" class="btn-admin btn-admin-secondary" style="padding: 10px 12px; color: #f87171;" @click="form.site_favicon = ''" x-show="form.site_favicon" title="Remove favicon">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Card 3: Technical SEO Title & Metadata Configuration -->
        <div class="admin-card" style="border-color: rgba(6, 182, 212, 0.35);">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="search" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                        <span>Technical SEO Title & Metadata Configuration</span>
                    </h2>
                    <p class="admin-card-subtitle">
                        Configure search engine page titles, title format templates, meta descriptions, indexing tags, and OpenGraph social metadata.
                    </p>
                </div>
                <span class="badge badge-info" style="font-size: 0.74rem;">Search Engine Optimization</span>
            </div>

            <!-- Google Search Snippet Live Preview -->
            <div style="background: rgba(11, 15, 26, 0.9); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 8px;">
                    <div style="font-size: 0.78rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="globe-2" style="width: 14px; height: 14px; color: var(--admin-cyan);"></i>
                        <span>Google Search SERP Real-Time Snippet</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="badge" :class="(form.seo_default_title.length >= 45 && form.seo_default_title.length <= 65) ? 'badge-success' : 'badge-warning'" style="font-size: 0.7rem;">
                            Title: <span x-text="form.seo_default_title.length"></span>/60 chars
                        </span>
                        <span class="badge" :class="(form.seo_default_description.length >= 120 && form.seo_default_description.length <= 165) ? 'badge-success' : 'badge-warning'" style="font-size: 0.7rem;">
                            Desc: <span x-text="form.seo_default_description.length"></span>/160 chars
                        </span>
                    </div>
                </div>

                <!-- Google Snippet Box -->
                <div style="background: #ffffff; border-radius: 8px; padding: 14px 18px; color: #202124; font-family: arial, sans-serif; max-width: 680px; box-shadow: 0 4px 14px rgba(0,0,0,0.2);">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <div style="width: 18px; height: 18px; border-radius: 50%; background: #f1f3f4; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                            <template x-if="form.site_favicon">
                                <img :src="form.site_favicon" alt="" style="width: 14px; height: 14px; object-fit: contain;">
                            </template>
                            <template x-if="!form.site_favicon">
                                <span style="font-size: 10px; color: #1a73e8; font-weight: bold;">G</span>
                            </template>
                        </div>
                        <div style="display: flex; flex-direction: column; line-height: 1.2;">
                            <span style="font-size: 12px; color: #202124; font-weight: 500;" x-text="form.seo_site_name || form.site_title || 'IMGAI'"></span>
                            <span style="font-size: 11px; color: #4d5156;">{{ rtrim(config('app.url') ?: url('/'), '/') }}</span>
                        </div>
                    </div>
                    <div style="color: #1a0dab; font-size: 18px; line-height: 1.3; font-weight: 400; text-decoration: none; cursor: pointer; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                         x-text="form.seo_homepage_title || form.seo_default_title || 'IMGAI — Cinematic 2MP Image & Video AI Creation Studio'">
                    </div>
                    <div style="color: #4d5156; font-size: 13px; line-height: 1.45; word-wrap: break-word;"
                         x-text="form.seo_homepage_description || form.seo_default_description || 'Synthesize photorealistic 2MP cinematic imagery and fluid motion video sequences with Wan 2.2 and Tencent Hunyuan generative AI diffusion models.'">
                    </div>
                </div>
            </div>

            <!-- SEO Input Fields -->
            <div style="display: flex; flex-direction: column; gap: 20px;">

                <!-- Row 1: SEO Titles -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                    <!-- Default Technical SEO Title -->
                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label style="font-size: 0.84rem; font-weight: 700; color: #f8fafc;">
                                Global Default SEO Title <span style="color: #f87171;">*</span>
                            </label>
                            <span style="font-size: 0.72rem; color: #94a3b8;" x-text="form.seo_default_title.length + ' / 60 chars'"></span>
                        </div>
                        <input type="text" name="seo_default_title" x-model="form.seo_default_title" placeholder="e.g. IMGAI — Cinematic 2MP Image & Video AI Studio" required class="admin-input" style="width: 100%; box-sizing: border-box; font-size: 0.88rem;">
                        <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Fallback &lt;title&gt; rendered on pages without a custom title.</span>
                    </div>

                    <!-- Homepage Specific SEO Title -->
                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label style="font-size: 0.84rem; font-weight: 700; color: #f8fafc;">
                                Homepage SEO Title
                            </label>
                            <span style="font-size: 0.72rem; color: #94a3b8;" x-text="form.seo_homepage_title.length + ' / 60 chars'"></span>
                        </div>
                        <input type="text" name="seo_homepage_title" x-model="form.seo_homepage_title" placeholder="e.g. IMGAI — Cinematic 2MP Image & Video AI Studio Platform" class="admin-input" style="width: 100%; box-sizing: border-box; font-size: 0.88rem;">
                        <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Dedicated &lt;title&gt; for the root homepage and overview studio.</span>
                    </div>
                </div>

                <!-- Row 2: SEO Site Name & Title Format -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                    <!-- SEO Site Name -->
                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                            SEO Brand / Site Name
                        </label>
                        <input type="text" name="seo_site_name" x-model="form.seo_site_name" placeholder="e.g. IMGAI — AI Creative Studio" class="admin-input" style="width: 100%; box-sizing: border-box; font-size: 0.88rem;">
                        <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Appended to dynamic tool pages and rendered in og:site_name.</span>
                    </div>

                    <!-- Title Format Template -->
                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                            Title Format Template
                        </label>
                        <input type="text" name="seo_title_format" x-model="form.seo_title_format" placeholder="%title% | %site_name%" class="admin-input font-mono" style="width: 100%; box-sizing: border-box; font-size: 0.88rem;">
                        <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Tokens available: <code style="color: var(--admin-cyan);">%title%</code>, <code style="color: var(--admin-cyan);">%site_name%</code></span>
                    </div>
                </div>

                <!-- Row 3: Meta Descriptions -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                    <!-- Default Meta Description -->
                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label style="font-size: 0.84rem; font-weight: 700; color: #f8fafc;">
                                Global Default Meta Description
                            </label>
                            <span style="font-size: 0.72rem; color: #94a3b8;" x-text="form.seo_default_description.length + ' / 160 chars'"></span>
                        </div>
                        <textarea name="seo_default_description" x-model="form.seo_default_description" rows="3" placeholder="Primary search snippet description for standard pages..." class="admin-input" style="width: 100%; box-sizing: border-box; font-size: 0.86rem; resize: vertical;"></textarea>
                    </div>

                    <!-- Homepage Meta Description -->
                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label style="font-size: 0.84rem; font-weight: 700; color: #f8fafc;">
                                Homepage Meta Description
                            </label>
                            <span style="font-size: 0.72rem; color: #94a3b8;" x-text="form.seo_homepage_description.length + ' / 160 chars'"></span>
                        </div>
                        <textarea name="seo_homepage_description" x-model="form.seo_homepage_description" rows="3" placeholder="Homepage search snippet description..." class="admin-input" style="width: 100%; box-sizing: border-box; font-size: 0.86rem; resize: vertical;"></textarea>
                    </div>
                </div>

                <!-- Row 4: Meta Keywords & Robots Indexing -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 18px;">
                    <!-- Meta Keywords -->
                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                            Meta Keywords (Comma-Separated)
                        </label>
                        <input type="text" name="seo_meta_keywords" x-model="form.seo_meta_keywords" placeholder="AI image generator, text to image, AI video studio, photorealistic AI, Wan 2.2, Hunyuan" class="admin-input" style="width: 100%; box-sizing: border-box; font-size: 0.86rem;">
                        <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Renders in &lt;meta name="keywords" content="..."&gt; tag.</span>
                    </div>

                    <!-- Robots Directive -->
                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                            Search Engine Indexing
                        </label>
                        <select name="seo_default_robots" x-model="form.seo_default_robots" class="admin-select" style="width: 100%; box-sizing: border-box; font-size: 0.86rem;">
                            <option value="index, follow">index, follow (Recommended)</option>
                            <option value="noindex, follow">noindex, follow</option>
                            <option value="index, nofollow">index, nofollow</option>
                            <option value="noindex, nofollow">noindex, nofollow</option>
                        </select>
                        <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Default &lt;meta name="robots"&gt; directive.</span>
                    </div>
                </div>

                <!-- Row 5: Open Graph Social Share Image -->
                <div style="background: rgba(15, 20, 34, 0.7); border: 1px solid var(--admin-border); border-radius: 12px; padding: 18px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.92rem; color: #f8fafc; display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="share-2" style="width: 16px; height: 16px; color: var(--admin-purple);"></i>
                                <span>Default Social Share Image (Open Graph & Twitter Cards)</span>
                            </div>
                            <div style="font-size: 0.76rem; color: #94a3b8; margin-top: 2px;">
                                Rendered on Facebook, LinkedIn, Twitter/X, and WhatsApp links (Recommended: 1200×630px JPG or PNG).
                            </div>
                        </div>

                        <div>
                            <button type="button" class="btn-admin btn-admin-secondary" style="padding: 6px 14px; font-size: 0.8rem;" @click="triggerUpload('seo_default_og_image')" :disabled="uploadingField === 'seo_default_og_image'">
                                <span x-show="uploadingField !== 'seo_default_og_image'" style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="upload-cloud" style="width: 14px; height: 14px;"></i>
                                    <span x-text="form.seo_default_og_image ? 'Replace OG Image' : 'Upload OG Image (R2)'"></span>
                                </span>
                                <span x-show="uploadingField === 'seo_default_og_image'" style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i>
                                    <span>Uploading...</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <!-- OG Preview Box -->
                        <div style="width: 140px; height: 75px; border-radius: 8px; background: rgba(0,0,0,0.5); border: 1px dashed rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 4px; flex-shrink: 0;">
                            <template x-if="form.seo_default_og_image">
                                <img :src="form.seo_default_og_image" alt="OG Image Preview" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                            </template>
                            <template x-if="!form.seo_default_og_image">
                                <span style="font-size: 0.72rem; color: #64748b; text-align: center;">No OG Image</span>
                            </template>
                        </div>

                        <!-- URL Input -->
                        <div style="flex: 1; min-width: 250px; display: flex; align-items: center; gap: 8px;">
                            <input type="text" name="seo_default_og_image" x-model="form.seo_default_og_image" placeholder="https://... or upload above" class="admin-input font-mono" style="flex: 1; font-size: 0.84rem; color: var(--admin-cyan);">
                            <button type="button" class="btn-admin btn-admin-secondary" style="padding: 10px 12px; color: #f87171;" @click="form.seo_default_og_image = ''" x-show="form.seo_default_og_image" title="Remove image">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Row 6: Search Engine Webmaster Verification -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                            Google Search Console Verification Token
                        </label>
                        <input type="text" name="seo_google_verification" x-model="form.seo_google_verification" placeholder="e.g. 7qX_abc123..." class="admin-input font-mono" style="width: 100%; box-sizing: border-box; font-size: 0.84rem;">
                        <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Injected into &lt;meta name="google-site-verification"&gt;.</span>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                            Bing Webmaster Verification Token
                        </label>
                        <input type="text" name="seo_bing_verification" x-model="form.seo_bing_verification" placeholder="e.g. 384C12..." class="admin-input font-mono" style="width: 100%; box-sizing: border-box; font-size: 0.84rem;">
                        <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Injected into &lt;meta name="msvalidate.01"&gt;.</span>
                    </div>
                </div>

            </div>

            <!-- Bottom Save Bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--admin-border);">
                <span style="font-size: 0.8rem; color: #94a3b8;">
                    Changes to Technical SEO are instantly applied and SEO cache is flushed automatically.
                </span>
                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
                    <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                    <span>Save All Branding & SEO Settings</span>
                </button>
            </div>
        </div>

    </form>

    <!-- Dedicated HTML Form for Uploads with CSRF Directive Protection -->
    <form id="branding-upload-form" method="POST" action="{{ route('admin.branding.upload-image') }}" enctype="multipart/form-data" style="display: none;">
        @csrf
        <input type="file" id="branding-generic-file-input" name="image" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml,image/x-icon,image/vnd.microsoft.icon" @change="handleFileUpload($event)">
    </form>

</div>

<script>
function brandingManager(initialData) {
    return {
        form: {
            // Core Branding
            site_title: initialData.site_title || 'IMGAI',
            site_tagline: initialData.site_tagline || 'AI Creative Studio',
            site_logo: initialData.site_logo || '',
            site_logo_icon: initialData.site_logo_icon || '',
            site_favicon: initialData.site_favicon || '',
            site_footer_text: initialData.site_footer_text || '',

            // Technical SEO Title & Metadata
            seo_site_name: initialData.seo_site_name || '',
            seo_title_format: initialData.seo_title_format || '%title% | %site_name%',
            seo_default_title: initialData.seo_default_title || '',
            seo_homepage_title: initialData.seo_homepage_title || '',
            seo_default_description: initialData.seo_default_description || '',
            seo_homepage_description: initialData.seo_homepage_description || '',
            seo_meta_keywords: initialData.seo_meta_keywords || '',
            seo_default_robots: initialData.seo_default_robots || 'index, follow',
            seo_default_og_image: initialData.seo_default_og_image || '',
            seo_google_verification: initialData.seo_google_verification || '',
            seo_bing_verification: initialData.seo_bing_verification || '',
        },
        uploadingField: null,
        triggerUpload(fieldName) {
            this.uploadingField = fieldName;
            const input = document.getElementById('branding-generic-file-input');
            if (input) {
                input.value = '';
                input.click();
            }
        },
        async handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file || !this.uploadingField) return;

            const targetField = this.uploadingField;

            // Retrieve CSRF token from multiple robust sources:
            // 1. Dedicated upload form @csrf token input
            // 2. <meta name="csrf-token"> in <head>
            // 3. Blade template rendered fallback
            const uploadForm = document.getElementById('branding-upload-form');
            const formToken = uploadForm ? uploadForm.querySelector('input[name="_token"]')?.value : null;
            const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const csrfToken = formToken || metaToken || '{{ csrf_token() }}';

            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', csrfToken);

            try {
                const res = await fetch("{{ route('admin.branding.upload-image') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (res.status === 419) {
                    throw new Error('CSRF token expired or mismatch. Please reload the page and try again.');
                }

                const data = await res.json();
                if (data.success && data.url) {
                    this.form[targetField] = data.url;
                    this.$nextTick(() => {
                        if (window.lucide) window.lucide.createIcons();
                    });
                } else {
                    alert(data.message || 'Image upload failed. Please check file size and format.');
                }
            } catch (err) {
                alert('Upload error: ' + err.message);
            } finally {
                this.uploadingField = null;
                event.target.value = '';
            }
        }
    };
}
</script>
@endsection
