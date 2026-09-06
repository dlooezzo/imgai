@extends('admin.layouts.app')

@section('title', 'Create CMS Page')
@section('breadcrumb', 'Pages / Create')

@section('content')
<div x-data="pageEditor()" style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Top Bar -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="{{ route('admin.pages.index') }}" class="btn-admin btn-admin-secondary" style="padding: 6px 12px; font-size: 0.82rem;">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
                <span>Back to Pages</span>
            </a>
            <h1 style="font-size: 1.4rem; font-weight: 800; letter-spacing: -0.02em;">
                Create New CMS Page
            </h1>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn-admin btn-admin-primary" @click="$refs.pageForm.submit()">
                <i data-lucide="check" style="width: 15px; height: 15px;"></i>
                <span>Save & Publish Page</span>
            </button>
        </div>
    </div>

    <!-- Main Editor Form -->
    <form x-ref="pageForm" method="POST" action="{{ route('admin.pages.store') }}" class="admin-grid-two-col">
        @csrf

        <!-- Left Column: Core Content & Editor -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Title & Slug Card -->
            <div class="admin-card">
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <!-- Title -->
                    <div>
                        <label style="display: block; font-size: 0.86rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                            Page Title <span style="color: #f87171;">*</span>
                        </label>
                        <input type="text" name="title" x-model="title" @input="updateSlug()" placeholder="e.g. About IMGAI" required
                               style="width: 100%; background: rgba(15, 20, 34, 0.9); border: 1px solid var(--admin-border); border-radius: 8px; padding: 12px 16px; color: #f8fafc; font-size: 1.1rem; font-weight: 700; outline: none;">
                        @error('title')
                            <span style="color: #f87171; font-size: 0.78rem; margin-top: 4px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- URL Slug -->
                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label style="font-size: 0.82rem; font-weight: 600; color: #cbd5e1;">
                                URL Slug <span style="color: #f87171;">*</span>
                            </label>
                            <label style="font-size: 0.75rem; color: #94a3b8; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="checkbox" x-model="autoSlug">
                                <span>Auto-generate from title</span>
                            </label>
                        </div>
                        
                        <div style="display: flex; align-items: center; background: rgba(15, 20, 34, 0.9); border: 1px solid var(--admin-border); border-radius: 8px; overflow: hidden;">
                            <span class="font-mono" style="padding: 10px 12px; background: rgba(0,0,0,0.3); color: #64748b; font-size: 0.84rem; border-right: 1px solid var(--admin-border);">
                                {{ url('/') }}/
                            </span>
                            <input type="text" name="slug" x-model="slug" placeholder="about" required class="font-mono"
                                   style="flex: 1; background: transparent; border: none; padding: 10px 14px; color: var(--admin-cyan); font-size: 0.88rem; outline: none;">
                        </div>
                        @error('slug')
                            <span style="color: #f87171; font-size: 0.78rem; margin-top: 4px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Excerpt -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                            Short Excerpt / Summary (Optional)
                        </label>
                        <textarea name="excerpt" rows="2" placeholder="Brief summary of the page for search engines and preview cards..."
                                  style="width: 100%; background: rgba(15, 20, 34, 0.9); border: 1px solid var(--admin-border); border-radius: 8px; padding: 10px 14px; color: #cbd5e1; font-size: 0.85rem; outline: none; line-height: 1.4;"></textarea>
                    </div>
                </div>
            </div>

            <!-- Professional Rich Content & HTML Editor -->
            <div>
                <label style="display: block; font-size: 0.88rem; font-weight: 700; color: #f8fafc; margin-bottom: 8px;">
                    Page Body Content <span style="color: #f87171;">*</span>
                </label>
                @include('admin.components.pro-editor', [
                    'name' => 'content',
                    'value' => old('content', ''),
                    'uploadUrl' => route('admin.pages.upload-image'),
                    'placeholder' => 'Write your rich page content, add headings, format text, or upload images...',
                    'minHeight' => '420px',
                ])
                @error('content')
                    <span style="color: #f87171; font-size: 0.78rem; margin-top: 6px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <!-- SEO Settings Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-card-title">
                            <i data-lucide="search" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                            <span>Search Engine Optimization (SEO) & Social Meta</span>
                        </h2>
                        <p class="admin-card-subtitle">Custom on-page ranking parameters, canonical links, and Open Graph previews</p>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label style="font-size: 0.82rem; font-weight: 600; color: #cbd5e1;">
                                Custom SEO Title (Optional)
                            </label>
                            <span style="font-size: 0.72rem; color: #64748b;">Recommended: 50–60 characters</span>
                        </div>
                        <input type="text" name="meta_title" placeholder="Defaults to Page Title — IMGAI Studio"
                               style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 9px 14px; color: #f8fafc; font-size: 0.86rem; outline: none;">
                    </div>

                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label style="font-size: 0.82rem; font-weight: 600; color: #cbd5e1;">
                                Custom Meta Description (Optional)
                            </label>
                            <span style="font-size: 0.72rem; color: #64748b;">Recommended: 140–160 characters</span>
                        </div>
                        <textarea name="meta_description" rows="3" placeholder="Defaults to page excerpt or opening body summary..."
                                  style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 9px 14px; color: #cbd5e1; font-size: 0.85rem; outline: none; line-height: 1.4;"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 14px;">
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                                Canonical URL (Optional)
                            </label>
                            <input type="url" name="canonical_url" placeholder="Defaults to https://.../{slug}"
                                   style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 9px 14px; color: #f8fafc; font-size: 0.86rem; outline: none;" class="font-mono">
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                                Robots Directive
                            </label>
                            <select name="robots_directive" class="admin-select" style="width: 100%; padding: 9px 12px;">
                                <option value="index, follow" selected>index, follow (Standard)</option>
                                <option value="noindex, follow">noindex, follow</option>
                                <option value="index, nofollow">index, nofollow</option>
                                <option value="noindex, nofollow">noindex, nofollow</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                            Social Preview Image URL (OG / Twitter)
                        </label>
                        <input type="url" name="og_image" placeholder="Leave empty to use global default social image"
                               style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 9px 14px; color: #f8fafc; font-size: 0.86rem; outline: none;" class="font-mono">
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Publishing & Navigation Settings -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Publishing Status Card -->
            <div class="admin-card">
                <div class="admin-card-header" style="margin-bottom: 14px;">
                    <h2 class="admin-card-title" style="font-size: 0.95rem;">
                        <i data-lucide="send" style="width: 16px; height: 16px; color: var(--admin-emerald);"></i>
                        <span>Publishing Status</span>
                    </h2>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--admin-border); border-radius: 8px;">
                        <input type="radio" name="status" value="published" checked style="accent-color: #10b981;">
                        <div>
                            <span style="font-weight: 700; color: #34d399; font-size: 0.86rem;">Published</span>
                            <span style="display: block; font-size: 0.72rem; color: #94a3b8;">Visible to all public visitors</span>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--admin-border); border-radius: 8px;">
                        <input type="radio" name="status" value="draft" style="accent-color: #f59e0b;">
                        <div>
                            <span style="font-weight: 700; color: #fbbf24; font-size: 0.86rem;">Draft</span>
                            <span style="display: block; font-size: 0.72rem; color: #94a3b8;">Only visible to authorized Admins</span>
                        </div>
                    </label>

                    <button type="submit" class="btn-admin btn-admin-primary" style="width: 100%; margin-top: 6px;">
                        <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                        <span>Save Page</span>
                    </button>
                </div>
            </div>

            <!-- Navigation Settings Card -->
            <div class="admin-card">
                <div class="admin-card-header" style="margin-bottom: 14px;">
                    <h2 class="admin-card-title" style="font-size: 0.95rem;">
                        <i data-lucide="compass" style="width: 16px; height: 16px; color: var(--admin-purple);"></i>
                        <span>Navbar Settings</span>
                    </h2>
                </div>

                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <!-- Show in Nav checkbox -->
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 8px 0;">
                        <input type="checkbox" name="show_in_navigation" value="1" style="width: 18px; height: 18px; accent-color: #6366f1;">
                        <span style="font-size: 0.86rem; font-weight: 600; color: #f8fafc;">Show link in main website Navbar</span>
                    </label>

                    <!-- Nav Label -->
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">
                            Navigation Label
                        </label>
                        <input type="text" name="navigation_label" placeholder="Leave empty to use title"
                               style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px 12px; color: #f8fafc; font-size: 0.84rem; outline: none;">
                    </div>

                    <!-- Nav Order -->
                    <div>
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">
                            Navigation Order (Sorting)
                        </label>
                        <input type="number" name="navigation_order" value="{{ $nextOrder }}" min="0" max="999"
                               style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px 12px; color: #f8fafc; font-size: 0.84rem; outline: none;" class="font-mono">
                        <span style="font-size: 0.72rem; color: #64748b; margin-top: 4px; display: block;">Lower numbers appear first (e.g. 1, 2, 3)</span>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

<script>
    function pageEditor() {
        return {
            title: '',
            slug: '',
            content: '',
            autoSlug: true,
            editorMode: 'visual',

            updateSlug() {
                if (this.autoSlug) {
                    this.slug = this.title
                        .toLowerCase()
                        .trim()
                        .replace(/[^\w\s-]/g, '')
                        .replace(/[\s_-]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                }
            },

            insertTag(openTag, closeTag) {
                const textarea = this.$refs.contentInput;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const selectedText = this.content.substring(start, end);
                const replacement = openTag + selectedText + closeTag;
                this.content = this.content.substring(0, start) + replacement + this.content.substring(end);
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.setSelectionRange(start + openTag.length, start + openTag.length + selectedText.length);
                });
            },

            insertText(text) {
                const textarea = this.$refs.contentInput;
                const start = textarea.selectionStart;
                this.content = this.content.substring(0, start) + text + this.content.substring(start);
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.setSelectionRange(start + text.length, start + text.length);
                });
            },

            insertList() {
                this.insertText("<ul>\n  <li>Feature point one</li>\n  <li>Feature point two</li>\n</ul>\n");
            },

            insertOrderedList() {
                this.insertText("<ol>\n  <li>Step one</li>\n  <li>Step two</li>\n</ol>\n");
            },

            insertLink() {
                const url = prompt("Enter link URL (e.g. https://example.com or /contact):", "https://");
                if (url) {
                    this.insertTag('<a href="' + url + '">', '</a>');
                }
            }
        }
    }
</script>
@endsection
