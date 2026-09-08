@extends('admin.layouts.app')

@section('title', 'Hero Showcase Video Management')
@section('breadcrumb', 'Overview / Hero Showcase')

@section('content')
<div x-data="heroShowcaseManager()" style="display: flex; flex-direction: column; gap: 28px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Hero Showcase Management
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Control the dynamic video stream and display size featured in the hero section on the public landing page.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="{{ route('tools.overview') }}" target="_blank" class="btn-admin btn-admin-secondary">
                <i data-lucide="external-link" style="width: 15px; height: 15px;"></i>
                <span>View Live Landing Page</span>
            </a>
        </div>
    </div>

    <!-- Main Two-Column Layout: Controls on Left, Live Preview on Right -->
    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px;">

        <!-- Left: Size Settings & Upload Form -->
        <div style="display: flex; flex-direction: column; gap: 24px;">

            <!-- Video Display Size & Scale Control Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-card-title">
                            <i data-lucide="maximize-2" style="width: 18px; height: 18px; color: var(--admin-primary);"></i>
                            <span>Hero Video Display Size (التحكم بحجم الفيديو)</span>
                        </h2>
                        <p class="admin-card-subtitle">Scale the hero video on the landing page (50% to 300%). Default is 200% (~2× enlarged).</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="badge badge-info" style="font-size: 0.88rem; font-weight: 700; padding: 6px 14px;" x-text="videoScale + '%'"></span>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <!-- Slider & Dynamic Metric Readout -->
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <label style="font-size: 0.84rem; font-weight: 600; color: #cbd5e1;">
                                Video Scale Slider:
                            </label>
                            <span style="font-size: 0.84rem; color: #38bdf8; font-family: 'JetBrains Mono', monospace; font-weight: 600;" 
                                  x-text="'Max Width: ' + calculatedMaxWidth + 'px (' + (videoScale / 100).toFixed(1) + '× scale)'"></span>
                        </div>

                        <!-- Range Input -->
                        <div style="padding: 6px 0;">
                            <input type="range" 
                                   min="50" 
                                   max="300" 
                                   step="5" 
                                   x-model.number="videoScale" 
                                   style="width: 100%; height: 8px; border-radius: 4px; background: rgba(255,255,255,0.1); outline: none; cursor: pointer; accent-color: #6366f1;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.72rem; color: #64748b; margin-top: 6px;">
                                <span>50% (Compact)</span>
                                <span>100% (1× Base)</span>
                                <span style="color: #818cf8; font-weight: 700;">200% (2× Default)</span>
                                <span>300% (3× Max)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Selection Presets -->
                    <div>
                        <span style="display: block; font-size: 0.78rem; font-weight: 600; color: #94a3b8; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">
                            Quick Presets:
                        </span>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <button type="button" @click="setScale(50)" class="btn-admin btn-admin-secondary" style="padding: 5px 12px; font-size: 0.78rem;" :style="videoScale === 50 ? 'border-color: #6366f1; background: rgba(99, 102, 241, 0.15); color: #818cf8;' : ''">50% (Compact)</button>
                            <button type="button" @click="setScale(100)" class="btn-admin btn-admin-secondary" style="padding: 5px 12px; font-size: 0.78rem;" :style="videoScale === 100 ? 'border-color: #6366f1; background: rgba(99, 102, 241, 0.15); color: #818cf8;' : ''">100% (1× Base)</button>
                            <button type="button" @click="setScale(150)" class="btn-admin btn-admin-secondary" style="padding: 5px 12px; font-size: 0.78rem;" :style="videoScale === 150 ? 'border-color: #6366f1; background: rgba(99, 102, 241, 0.15); color: #818cf8;' : ''">150% (1.5×)</button>
                            <button type="button" @click="setScale(200)" class="btn-admin btn-admin-secondary" style="padding: 5px 12px; font-size: 0.78rem;" :style="videoScale === 200 ? 'border-color: #6366f1; background: rgba(99, 102, 241, 0.15); color: #818cf8; font-weight: 700;' : ''">200% (2× Double / Recommended)</button>
                            <button type="button" @click="setScale(250)" class="btn-admin btn-admin-secondary" style="padding: 5px 12px; font-size: 0.78rem;" :style="videoScale === 250 ? 'border-color: #6366f1; background: rgba(99, 102, 241, 0.15); color: #818cf8;' : ''">250% (2.5×)</button>
                            <button type="button" @click="setScale(300)" class="btn-admin btn-admin-secondary" style="padding: 5px 12px; font-size: 0.78rem;" :style="videoScale === 300 ? 'border-color: #6366f1; background: rgba(99, 102, 241, 0.15); color: #818cf8;' : ''">300% (3× Max)</button>
                        </div>
                    </div>

                    <!-- Save Video Size Action Button -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 14px; border-top: 1px solid var(--admin-border);">
                        <div>
                            <template x-if="scaleSaveSuccess">
                                <span style="display: inline-flex; align-items: center; gap: 6px; color: #34d399; font-size: 0.84rem; font-weight: 600;">
                                    <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
                                    <span>Saved successfully! Changes active on site.</span>
                                </span>
                            </template>
                        </div>

                        <button type="button" 
                                @click="saveScale()" 
                                class="btn-admin btn-admin-primary" 
                                style="padding: 9px 22px; font-size: 0.88rem;" 
                                :disabled="isSavingScale">
                            <template x-if="!isSavingScale">
                                <span style="display: flex; align-items: center; gap: 7px;">
                                    <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                                    <span>Save Video Size</span>
                                </span>
                            </template>
                            <template x-if="isSavingScale">
                                <span style="display: flex; align-items: center; gap: 7px;">
                                    <i data-lucide="loader-2" style="width: 15px; height: 15px; animation: spin 1s linear infinite;"></i>
                                    <span>Saving Size...</span>
                                </span>
                            </template>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Upload & Configuration Form -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2 class="admin-card-title">
                            <i data-lucide="upload-cloud" style="width: 18px; height: 18px; color: var(--admin-primary);"></i>
                            <span>Upload & Publish Video</span>
                        </h2>
                        <p class="admin-card-subtitle">Choose a video from your local computer or provide a direct video URL</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.hero-showcase.update') }}" enctype="multipart/form-data" @submit="isSubmitting = true" style="display: flex; flex-direction: column; gap: 20px;">
                    @csrf
                    <input type="hidden" name="video_scale" :value="videoScale">

                    <!-- File Dropzone -->
                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 8px;">
                            Video File (MP4, WebM, MOV - Max 100MB)
                        </label>

                        <div style="border: 2px dashed rgba(99, 102, 241, 0.35); background: rgba(15, 20, 34, 0.6); border-radius: 12px; padding: 28px 16px; text-align: center; cursor: pointer; transition: all 0.25s;"
                             :style="isDragging ? 'border-color: #38bdf8; background: rgba(56, 189, 248, 0.1);' : ''"
                             @dragover.prevent="isDragging = true"
                             @dragleave.prevent="isDragging = false"
                             @drop.prevent="handleFileDrop($event)"
                             @click="$refs.fileInput.click()">

                            <input type="file" x-ref="fileInput" name="video_file" accept="video/mp4,video/webm,video/quicktime,video/ogg" style="display: none;" @change="handleFileSelect($event)">

                            <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.25); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; color: var(--admin-primary);">
                                <i data-lucide="video" style="width: 24px; height: 24px;"></i>
                            </div>

                            <template x-if="!selectedFileName">
                                <div>
                                    <div style="font-size: 0.92rem; font-weight: 700; color: #f8fafc;">
                                        Click to select or drag & drop video
                                    </div>
                                    <div style="font-size: 0.78rem; color: #64748b; margin-top: 4px;">
                                        Supports H.264/HEVC MP4, WebM (Auto-plays muted & looped in hero)
                                    </div>
                                </div>
                            </template>

                            <template x-if="selectedFileName">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                                    <i data-lucide="check-circle" style="width: 18px; height: 18px; color: #34d399;"></i>
                                    <span style="font-weight: 700; color: #34d399; font-size: 0.92rem;" x-text="selectedFileName"></span>
                                    <span style="font-size: 0.78rem; color: #94a3b8;" x-text="'(' + selectedFileSize + ')'"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="flex: 1; height: 1px; background: var(--admin-border);"></div>
                        <span style="font-size: 0.76rem; color: #64748b; text-transform: uppercase; font-weight: 700;">OR DIRECT VIDEO URL</span>
                        <div style="flex: 1; height: 1px; background: var(--admin-border);"></div>
                    </div>

                    <!-- Direct Video URL Input -->
                    <div>
                        <label style="display: block; font-size: 0.84rem; font-weight: 600; color: #cbd5e1; margin-bottom: 8px;">
                            External Direct Video URL (Optional)
                        </label>
                        <input type="url" name="video_url" x-model="inputUrl" @input="handleUrlInput()" placeholder="https://cdn.example.com/cinematic_motion_reel.mp4"
                               style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 10px 14px; color: #f8fafc; font-size: 0.88rem; outline: none;"
                               class="font-mono">
                    </div>

                    <!-- Title & Caption Fields -->
                    <div style="display: flex; flex-direction: column; gap: 14px; border-top: 1px solid var(--admin-border); padding-top: 16px;">
                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                                Showcase Overlay Title
                            </label>
                            <input type="text" name="title" value="{{ $currentVideoTitle }}" placeholder="Neural Frame Synthesis"
                                   style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 9px 14px; color: #f8fafc; font-size: 0.86rem; outline: none;">
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">
                                Showcase Overlay Caption
                            </label>
                            <input type="text" name="caption" value="{{ $currentVideoCaption }}" placeholder="Spatial Diffusion • Volumetric Lighting • Temporal Consistency"
                                   style="width: 100%; background: rgba(15, 20, 34, 0.8); border: 1px solid var(--admin-border); border-radius: 8px; padding: 9px 14px; color: #f8fafc; font-size: 0.86rem; outline: none;">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 8px;">
                        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 12px 24px; font-size: 0.9rem;" :disabled="isSubmitting">
                            <template x-if="!isSubmitting">
                                <span style="display: flex; align-items: center; gap: 8px;">
                                    <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                                    <span>Save & Publish Hero Video</span>
                                </span>
                            </template>
                            <template x-if="isSubmitting">
                                <span style="display: flex; align-items: center; gap: 8px;">
                                    <i data-lucide="loader-2" style="width: 16px; height: 16px; animation: spin 1s linear infinite;"></i>
                                    <span>Publishing Video Stream...</span>
                                </span>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right: Real-time Live Preview Box -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- Live Preview Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <h2 class="admin-card-title">Live Preview Verification</h2>
                            <template x-if="previewSrc">
                                <span class="badge badge-success" style="font-size: 0.68rem;">Ready</span>
                            </template>
                        </div>
                        <p class="admin-card-subtitle">Exact visual rendering matching the landing page</p>
                    </div>
                </div>

                <!-- Clean, Frameless Simulated Hero Preview Box -->
                <div style="width: 100%; min-height: 240px; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2); border: 1px dashed var(--admin-border); border-radius: 14px; padding: 20px; overflow: hidden;">
                    
                    <div :style="'width: 100%; max-width: ' + Math.min(calculatedMaxWidth, 560) + 'px; transition: all 0.3s ease; display: flex; justify-content: center;'" style="position: relative;">
                        <!-- Background Video Element -->
                        <template x-if="previewSrc">
                            <video :src="previewSrc" autoplay loop muted playsinline style="width: 100%; height: auto; object-fit: contain; border-radius: 14px; display: block; background: transparent;"></video>
                        </template>

                        <template x-if="!previewSrc">
                            <div style="width: 100%; aspect-ratio: 16/10; border-radius: 12px; background: rgba(15, 20, 34, 0.6); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #64748b; font-size: 0.85rem; gap: 8px;">
                                <i data-lucide="clapperboard" style="width: 32px; height: 32px; color: #475569;"></i>
                                <span>No video selected or uploaded yet</span>
                            </div>
                        </template>
                    </div>
                </div>

                <div style="margin-top: 10px; font-size: 0.78rem; color: #64748b; text-align: center;">
                    * The video renders cleanly on the site without dark boxes, borders, or shadows.
                </div>
            </div>

            <!-- Current Active Video Status Card -->
            <div class="admin-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px #10b981;"></span>
                        <span style="font-weight: 700; font-size: 0.88rem; color: #f8fafc;">Published Status</span>
                    </div>

                    @if ($currentVideoUrl)
                        <form method="POST" action="{{ route('admin.hero-showcase.reset') }}" onsubmit="return confirm('Reset hero video back to default state?');">
                            @csrf
                            <button type="submit" class="btn-admin btn-admin-danger" style="padding: 4px 10px; font-size: 0.74rem;">
                                <i data-lucide="rotate-ccw" style="width: 12px; height: 12px;"></i>
                                <span>Reset to Default</span>
                            </button>
                        </form>
                    @endif
                </div>

                <div style="font-size: 0.82rem; color: #94a3b8; display: flex; flex-direction: column; gap: 6px;">
                    <div>
                        <span style="color: #64748b;">Source URL:</span>
                        <span class="font-mono" style="color: var(--admin-cyan); word-break: break-all;">
                            {{ $currentVideoUrl ?: 'Default Ambient Procedural Canvas' }}
                        </span>
                    </div>
                    <div>
                        <span style="color: #64748b;">Active Scale:</span>
                        <span style="color: #38bdf8; font-weight: 700;" x-text="videoScale + '% (~' + calculatedMaxWidth + 'px max-width)'"></span>
                    </div>
                    <div>
                        <span style="color: #64748b;">Last Updated:</span>
                        <span>{{ $updatedAt ? $updatedAt->format('M d, Y H:i:s') : 'Initial setup' }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    function heroShowcaseManager() {
        return {
            isDragging: false,
            isSubmitting: false,
            isSavingScale: false,
            scaleSaveSuccess: false,
            selectedFileName: null,
            selectedFileSize: null,
            inputUrl: '',
            previewSrc: "{{ $currentVideoUrl ?: '' }}",
            videoScale: {{ (int) ($currentVideoScale ?? 200) }},

            get calculatedMaxWidth() {
                return Math.round(420 * (this.videoScale / 100));
            },

            setScale(val) {
                this.videoScale = val;
            },

            async saveScale() {
                this.isSavingScale = true;
                this.scaleSaveSuccess = false;
                try {
                    const res = await fetch("{{ route('admin.hero-showcase.scale') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ video_scale: this.videoScale })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.scaleSaveSuccess = true;
                        setTimeout(() => { this.scaleSaveSuccess = false; }, 3500);
                    } else {
                        alert(data.message || 'Failed to update video scale.');
                    }
                } catch (err) {
                    alert('Network error while saving video size.');
                } finally {
                    this.isSavingScale = false;
                }
            },

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (file) {
                    this.setPreviewFromFile(file);
                }
            },

            handleFileDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer.files[0];
                if (file) {
                    this.$refs.fileInput.files = event.dataTransfer.files;
                    this.setPreviewFromFile(file);
                }
            },

            setPreviewFromFile(file) {
                this.selectedFileName = file.name;
                this.selectedFileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                this.previewSrc = URL.createObjectURL(file);
                this.inputUrl = '';
                setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 50);
            },

            handleUrlInput() {
                if (this.inputUrl.trim() !== '') {
                    this.previewSrc = this.inputUrl.trim();
                    this.selectedFileName = null;
                    this.selectedFileSize = null;
                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.value = '';
                    }
                }
            }
        }
    }
</script>
@endsection
