@extends('admin.layouts.app')

@section('title', 'Article Editor: ' . $toolMeta['name'])

@section('content')
<div x-data="articleEditor({
    initialContent: {{ json_encode(old('content_html', $article->content_html ?? '')) }},
    title: {{ json_encode(old('title', $article->title ?? '')) }},
    seoTitle: {{ json_encode(old('seo_title', $article->seo_title ?? '')) }},
    metaDescription: {{ json_encode(old('meta_description', $article->meta_description ?? '')) }},
    uploadUrl: '{{ route('admin.seo.articles.upload-image') }}',
    csrfToken: '{{ csrf_token() }}'
})" style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Top Header -->
    <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <a href="{{ route('admin.seo.articles.index') }}" style="color: #94a3b8; font-size: 0.82rem; display: flex; align-items: center; gap: 4px; text-decoration: none;">
                    <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
                    <span>Back to Articles</span>
                </a>
                <span style="color: #64748b; font-size: 0.8rem;">•</span>
                <span class="badge badge-purple" style="font-size: 0.72rem;">{{ $toolMeta['badge'] }}</span>
                <span style="color: #64748b; font-size: 0.8rem;">•</span>
                <span style="color: #94a3b8; font-size: 0.8rem; font-family: monospace;">{{ $toolMeta['public_url'] }}</span>
            </div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: #f8fafc; margin: 0; letter-spacing: -0.02em;">
                Edit SEO Article: <span style="color: var(--admin-cyan);">{{ $toolMeta['name'] }}</span>
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem; margin-top: 4px;">
                Craft structured, crawlable technical articles rendered directly below the AI generator.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            @if($article->exists)
                <a href="{{ route('admin.seo.articles.preview', $toolKey) }}" target="_blank" class="btn-action" style="padding: 9px 15px; font-size: 0.84rem;">
                    <i data-lucide="external-link" style="width: 15px; height: 15px; color: var(--admin-cyan);"></i>
                    <span>Live Preview</span>
                </a>
            @endif
            <button type="button" @click="submitForm('draft')" class="btn-action" style="padding: 9px 16px; font-size: 0.84rem; border-color: rgba(251, 191, 36, 0.3); color: #fbbf24;">
                <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                <span>Save Draft</span>
            </button>
            <button type="button" @click="submitForm('published')" class="btn-primary" style="padding: 9px 18px; font-size: 0.84rem;">
                <i data-lucide="check-circle-2" style="width: 15px; height: 15px;"></i>
                <span>Publish Live</span>
            </button>
        </div>
    </div>

    <!-- Main Two-Column Layout -->
    <form id="article-form" method="POST" action="{{ route('admin.seo.articles.update', $toolKey) }}" style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
        @csrf
        <input type="hidden" name="status" x-model="status">
        {{-- NOTE: content_html is submitted by the pro-editor component's own hidden textarea. --
             Do NOT add a duplicate hidden field here, it would overwrite the typed content. --}}

        <!-- Left Column: Editor Core & SEO Configuration -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Article Title & Excerpt Card -->
            <div class="admin-card">
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                            Article Main Headline (H1 / Title) <span style="color: #f87171;">*</span>
                        </label>
                        <input type="text" name="title" x-model="title" required placeholder="e.g. The Complete Guide to 2MP Photorealistic AI Image Generation"
                               style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 11px 14px; color: #f8fafc; font-size: 1.05rem; font-weight: 700; outline: none;">
                        @error('title')
                            <span style="color: #f87171; font-size: 0.78rem; margin-top: 4px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                            Article Opening Summary / Excerpt
                        </label>
                        <textarea name="excerpt" rows="2" placeholder="Brief technical lead paragraph introducing the article topic..."
                                  style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 9px 14px; color: #cbd5e1; font-size: 0.86rem; outline: none; line-height: 1.4;">{{ old('excerpt', $article->excerpt ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Professional Rich Content & HTML Editor Card -->
            <div>
                <label style="display: block; font-size: 0.88rem; font-weight: 700; color: #f8fafc; margin-bottom: 8px;">
                    Article Body Content <span style="color: #f87171;">*</span>
                </label>
                @include('admin.components.pro-editor', [
                    'name' => 'content_html',
                    'value' => old('content_html', $article->content_html ?? ''),
                    'uploadUrl' => route('admin.seo.articles.upload-image'),
                    'placeholder' => 'Write your rich SEO guide, format headings, code blocks, or upload R2 imagery...',
                    'minHeight' => '480px',
                ])
                @error('content_html')
                    <span style="color: #f87171; font-size: 0.78rem; margin-top: 6px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <!-- SEO & Open Graph Metadata Settings Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-card-title">
                            <i data-lucide="search" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                            <span>Search Engine Optimization (SEO) & Social Meta</span>
                        </h2>
                        <p class="admin-card-subtitle">On-page technical meta parameters used by Google, Bing, and Social Crawlers</p>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <!-- Custom SEO Title -->
                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label style="font-size: 0.82rem; font-weight: 600; color: #cbd5e1;">
                                Custom SEO Title (Meta Title)
                            </label>
                            <div style="font-size: 0.74rem; display: flex; align-items: center; gap: 6px;">
                                <span :style="seoTitle.length >= 45 && seoTitle.length <= 65 ? 'color: #34d399;' : (seoTitle.length > 65 ? 'color: #fbbf24;' : 'color: #94a3b8;')"
                                      x-text="seoTitle.length + ' chars'"></span>
                                <span style="color: #64748b;">(Recommended: 50–60)</span>
                            </div>
                        </div>
                        <input type="text" name="seo_title" x-model="seoTitle" placeholder="Defaults to Article Title — IMGAI Studio"
                               style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 10px 14px; color: #f8fafc; font-size: 0.88rem; outline: none;">
                    </div>

                    <!-- Meta Description -->
                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label style="font-size: 0.82rem; font-weight: 600; color: #cbd5e1;">
                                Custom Meta Description
                            </label>
                            <div style="font-size: 0.74rem; display: flex; align-items: center; gap: 6px;">
                                <span :style="metaDescription.length >= 130 && metaDescription.length <= 165 ? 'color: #34d399;' : (metaDescription.length > 165 ? 'color: #fbbf24;' : 'color: #94a3b8;')"
                                      x-text="metaDescription.length + ' chars'"></span>
                                <span style="color: #64748b;">(Recommended: 140–160)</span>
                            </div>
                        </div>
                        <textarea name="meta_description" x-model="metaDescription" rows="3" placeholder="High-CTR search summary describing the guide and AI generator features..."
                                  style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 10px 14px; color: #cbd5e1; font-size: 0.86rem; outline: none; line-height: 1.4;"></textarea>
                    </div>

                    <!-- Featured Social Image with R2 Uploader -->
                    <div x-data="seoImageUploader('{{ old('featured_image', $article->featured_image ?? '') }}', 'featured_image')">
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                            Featured Open Graph Image
                        </label>
                        <input type="file" x-ref="fileInput" @change="handleFileChange($event)" accept="image/png,image/jpeg,image/webp,image/gif" style="display: none;">
                        <input type="hidden" name="featured_image" :value="url">

                        <div style="display: flex; align-items: center; gap: 12px; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 8px 12px;">
                            <template x-if="url">
                                <div style="position: relative; width: 56px; height: 36px; border-radius: 6px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.1); flex-shrink: 0;">
                                    <img :src="url" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            </template>
                            <template x-if="!url">
                                <div style="width: 56px; height: 36px; border-radius: 6px; background: rgba(255,255,255,0.04); border: 1px dashed rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; color: #64748b; flex-shrink: 0;">
                                    <i data-lucide="image" style="width: 16px; height: 16px;"></i>
                                </div>
                            </template>

                            <div style="flex-grow: 1; overflow: hidden;">
                                <input type="url" x-model="url" placeholder="https://... (Uses global default social preview if empty)" class="font-mono"
                                       style="width: 100%; background: transparent; border: none; color: #f8fafc; font-size: 0.82rem; outline: none; text-overflow: ellipsis;">
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
                </div>
            </div>
        </div>

        <!-- Right Column: Publishing Sidebar & Metrics -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Publishing Action Card -->
            <div class="admin-card">
                <div class="admin-card-header" style="margin-bottom: 14px;">
                    <h3 style="font-size: 0.92rem; font-weight: 700; color: #f8fafc; margin: 0;">Publishing State</h3>
                </div>

                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.78rem; font-weight: 600; color: #94a3b8; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.04em;">
                            Publication Status
                        </label>
                        <select x-model="status" class="admin-select" style="width: 100%; padding: 9px 12px; font-weight: 600;">
                            <option value="published" style="color: #34d399;">Published (Live on Public Tool)</option>
                            <option value="draft" style="color: #fbbf24;">Draft (Hidden from Public)</option>
                        </select>
                    </div>

                    <div style="padding: 12px; border-radius: 8px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--admin-border); display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.78rem;">
                            <span style="color: #94a3b8;">Article Words:</span>
                            <strong style="color: #f8fafc;" x-text="calculateWords()"></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.78rem;">
                            <span style="color: #94a3b8;">Est. Reading Time:</span>
                            <span style="color: #38bdf8;" x-text="'~' + Math.max(1, Math.ceil(calculateWords() / 200)) + ' min'"></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.78rem;">
                            <span style="color: #94a3b8;">Target Placement:</span>
                            <span style="color: #a855f7;">Under Generator</span>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 4px;">
                        <button type="button" @click="submitForm('published')" class="btn-primary" style="width: 100%; justify-content: center; padding: 10px 16px;">
                            <i data-lucide="check" style="width: 15px; height: 15px;"></i>
                            <span>Save & Publish Article</span>
                        </button>
                        <button type="button" @click="submitForm('draft')" class="btn-action" style="width: 100%; justify-content: center; padding: 9px 16px; border-color: rgba(251, 191, 36, 0.3); color: #fbbf24;">
                            <i data-lucide="file-text" style="width: 15px; height: 15px;"></i>
                            <span>Save as Draft</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cloudflare R2 Media Details -->
            <div class="admin-card">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(192, 132, 252, 0.15); display: flex; align-items: center; justify-content: center; color: #c084fc;">
                        <i data-lucide="cloud" style="width: 16px; height: 16px;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; color: #f8fafc; font-size: 0.86rem;">Cloudflare R2 Storage</div>
                        <div style="font-size: 0.72rem; color: #94a3b8;">Article Media CDN</div>
                    </div>
                </div>
                <p style="font-size: 0.78rem; color: #94a3b8; line-height: 1.5; margin: 0;">
                    Article imagery is stored directly on your Cloudflare R2 bucket with zero egress fees and distributed global edge caching.
                </p>
            </div>
        </div>
    </form>

    {{-- Image upload modal is handled inside the pro-editor component's own Alpine scope (proContentEditor) --}}
</div>

<style>
/* Visual Editor Preview Styles */
.article-visual-preview {
    color: #cbd5e1;
    font-size: 0.95rem;
    line-height: 1.7;
}
.article-visual-preview h2 {
    font-size: 1.45rem;
    font-weight: 800;
    color: #f8fafc;
    margin-top: 24px;
    margin-bottom: 12px;
    letter-spacing: -0.02em;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 8px;
}
.article-visual-preview h3 {
    font-size: 1.2rem;
    font-weight: 700;
    color: #e2e8f0;
    margin-top: 20px;
    margin-bottom: 10px;
}
.article-visual-preview h4 {
    font-size: 1.05rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-top: 16px;
    margin-bottom: 8px;
}
.article-visual-preview p {
    margin-bottom: 16px;
    color: #94a3b8;
}
.article-visual-preview strong {
    color: #f8fafc;
}
.article-visual-preview ul, .article-visual-preview ol {
    margin-bottom: 16px;
    padding-left: 24px;
}
.article-visual-preview li {
    margin-bottom: 6px;
    color: #cbd5e1;
}
.article-visual-preview blockquote {
    border-left: 3px solid var(--admin-cyan);
    padding: 12px 18px;
    margin: 18px 0;
    background: rgba(56, 189, 248, 0.05);
    border-radius: 0 8px 8px 0;
    color: #e2e8f0;
    font-style: italic;
}
.article-visual-preview figure {
    margin: 24px 0;
    text-align: center;
}
.article-visual-preview img {
    max-width: 100%;
    border-radius: 10px;
    border: 1px solid var(--admin-border);
}
.article-visual-preview figcaption {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 8px;
    font-style: italic;
}
.article-visual-preview pre {
    background: rgba(0, 0, 0, 0.6);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 14px;
    overflow-x: auto;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.85rem;
    color: #38bdf8;
    margin: 16px 0;
}
.article-visual-preview code {
    font-family: 'JetBrains Mono', monospace;
    background: rgba(255, 255, 255, 0.08);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.84em;
    color: #a855f7;
}
.article-visual-preview hr {
    border: none;
    border-top: 1px solid var(--admin-border);
    margin: 28px 0;
}
</style>

<script>
function articleEditor(config) {
    return {
        mode: 'visual',
        content: config.initialContent || '',
        title: config.title || '',
        seoTitle: config.seoTitle || '',
        metaDescription: config.metaDescription || '',
        status: '{{ old('status', $article->status ?? 'draft') }}',
        uploadUrl: config.uploadUrl,
        csrfToken: config.csrfToken,

        submitForm(newStatus) {
            this.status = newStatus;
            this.$nextTick(() => {
                document.getElementById('article-form').submit();
            });
        },

        calculateWords() {
            const text = (this.content || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            return text ? text.split(' ').length : 0;
        }
    };
}

function seoImageUploader(initialUrl, fieldName) {
    return {
        url: initialUrl || '',
        uploading: false,
        errorMessage: '',
        triggerUpload() {
            this.$refs.fileInput.click();
        },
        async handleFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 10 * 1024 * 1024) {
                this.errorMessage = 'File exceeds 10MB limit';
                return;
            }

            this.uploading = true;
            this.errorMessage = '';

            const formData = new FormData();
            formData.append('image', file);

            try {
                const response = await fetch('{{ route('admin.seo.articles.upload-image') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();
                if (data.success && data.url) {
                    this.url = data.url;
                    if (window.lucide) {
                        this.$nextTick(() => window.lucide.createIcons());
                    }
                } else {
                    this.errorMessage = data.message || 'Upload failed';
                }
            } catch (err) {
                this.errorMessage = 'Network error during upload';
            } finally {
                this.uploading = false;
                event.target.value = '';
            }
        },
        clearImage() {
            this.url = '';
        }
    };
}
</script>
@endsection
