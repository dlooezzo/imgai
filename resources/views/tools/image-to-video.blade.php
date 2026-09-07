<!-- Refactored Header: Glowing Emerald Visual Identity -->
<div style="position: relative; overflow: hidden; border-radius: 16px; background: linear-gradient(90deg, rgba(16, 185, 129, 0.12) 0%, rgba(16, 185, 129, 0.04) 50%, transparent 100%); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 0 25px rgba(16, 185, 129, 0.12); padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; gap: 14px;">
        <div style="display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.35); color: #34d399; box-shadow: 0 0 15px rgba(16, 185, 129, 0.35);">
            <i data-lucide="clapperboard" style="width: 22px; height: 22px;"></i>
        </div>
        <h1 style="font-size: 1.35rem; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.02em;">Image-to-Video</h1>
    </div>

    <button type="button" class="btn-action" @click="createNewVideo()" title="Start a fresh video creation" style="padding: 8px 16px; font-size: 0.84rem; display: flex; align-items: center; gap: 6px; border-color: rgba(16, 185, 129, 0.35); background: rgba(16, 185, 129, 0.1); color: #34d399; font-weight: 600;">
        <i data-lucide="plus" style="width: 15px; height: 15px;"></i>
        <span>New Video</span>
    </button>
</div>

<!-- Glowing Premium AI Tools Crystal Card -->
@include('tools.partials.crystal-card')

<!-- Main Two-Column Generator Grid (Controls 45% / Canvas 55%) -->
<div class="generator-grid">
    <!-- Left Column: Controls & Configuration Panel -->
    <div class="form-panel">
        <!-- 1. Source Image Upload Section -->
        <div class="control-row">
            <div class="form-section-title">
                <span>1. Source Image</span>
                <span class="badge-val" x-text="selectedFile ? selectedFileName : 'Required'"></span>
            </div>

            <!-- Hidden File Input -->
            <input 
                type="file" 
                id="source-image-file-input" 
                accept="image/jpeg,image/png,image/webp" 
                @change="handleFileSelect($event)" 
                style="display: none;"
            >

            <!-- Dropzone (Shown when no image is selected) -->
            <div 
                x-show="!imagePreviewUrl"
                class="image-upload-dropzone"
                :class="{ 'drag-over': isDragging }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleFileDrop($event)"
                @click="triggerFileInput()"
            >
                <div class="dropzone-icon-wrap">
                    <i data-lucide="upload-cloud" style="width: 24px; height: 24px; color: var(--brand-cyan);"></i>
                </div>
                <div class="dropzone-text-main">Click or drop source image here</div>
                <div class="dropzone-text-sub">JPG, PNG, WebP • Max 15 MB</div>
            </div>

            <!-- Selected Image Preview Card -->
            <div x-show="imagePreviewUrl" class="selected-image-card" style="display: none;">
                <div class="selected-image-thumb-wrap">
                    <img :src="imagePreviewUrl" alt="Source Image" class="selected-image-thumb" @click="openLightbox(imagePreviewUrl)">
                </div>
                <div class="selected-image-details">
                    <div class="selected-image-name" x-text="selectedFileName"></div>
                    <div class="selected-image-meta" x-text="selectedFileSize"></div>
                    <div class="selected-image-actions">
                        <button type="button" class="btn-micro" @click="triggerFileInput()" :disabled="isGenerating">
                            <i data-lucide="refresh-cw" style="width: 12px; height: 12px;"></i>
                            <span>Replace</span>
                        </button>
                        <button type="button" class="btn-micro btn-micro-danger" @click="removeSelectedImage()" :disabled="isGenerating">
                            <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                            <span>Remove</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Motion Prompt Description -->
        <div class="prompt-container">
            <div class="form-section-title">
                <span>2. Motion Description</span>
                <span style="font-size: 0.75rem; text-transform: none; color: var(--text-muted);" x-text="prompt.length + ' / 2000'"></span>
            </div>

            <div class="prompt-textarea-wrapper">
                <textarea 
                    class="prompt-textarea" 
                    x-model="prompt" 
                    placeholder="Describe your video scene with motion (e.g. A sleek car drives fast along an open desert highway, camera tracking dynamically from the side with swirling dust trails...)"
                    maxlength="2000"
                    :disabled="isGenerating"
                    rows="3"
                ></textarea>
            </div>

            <div class="prompt-actions-bar">
                <span>Inspire me:</span>
                <button type="button" class="chip-btn" style="border: none; background: none; color: var(--brand-cyan);" @click="prompt = ''" x-show="prompt.length > 0 && !isGenerating">Clear</button>
            </div>

            <div class="prompt-sample-chips">
                <template x-for="(sample, idx) in sampleMotionPrompts" :key="idx">
                    <button type="button" class="chip-btn" @click="useSamplePrompt(sample)" :title="sample" :disabled="isGenerating">
                        <span x-text="sample.length > 36 ? sample.substring(0, 36) + '...' : sample"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- 3. Aspect Ratio Selection -->
        <div class="control-row">
            <div class="form-section-title">
                <span>3. Aspect Ratio</span>
                <span class="badge-val" x-text="aspectRatio"></span>
            </div>

            <div class="ratio-grid" style="grid-template-columns: repeat(2, 1fr);">
                <!-- 16:9 Landscape / Cinema -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '16:9' }" @click="!isGenerating && (aspectRatio = '16:9')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 28px; height: 16px;"></div>
                    <span class="ratio-label">16:9</span>
                    <span class="ratio-desc">Cinema</span>
                </button>

                <!-- 9:16 Portrait / Story -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '9:16' }" @click="!isGenerating && (aspectRatio = '9:16')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 16px; height: 28px;"></div>
                    <span class="ratio-label">9:16</span>
                    <span class="ratio-desc">Reels / Shorts</span>
                </button>
            </div>
        </div>

        <!-- 4. Video Resolution Selection -->
        <div class="control-row">
            <div class="form-section-title">
                <span>4. Resolution</span>
                <span class="badge-val" x-text="resolution.toUpperCase()"></span>
            </div>

            <div class="segmented-group">
                <button type="button" class="segmented-btn" :class="{ 'active': resolution === '720p' }" @click="!isGenerating && (resolution = '720p')" :disabled="isGenerating">720p (High Definition)</button>
                <button type="button" class="segmented-btn" :class="{ 'active': resolution === '480p' }" @click="!isGenerating && (resolution = '480p')" :disabled="isGenerating">480p (Standard Definition)</button>
            </div>
        </div>

        <!-- 5. Advanced Motion Controls Accordion -->
        <div class="accordion">
            <button type="button" class="accordion-header" @click="advancedOpen = !advancedOpen">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="sliders" style="width: 16px; height: 16px;"></i>
                    <span>Advanced Motion Settings</span>
                </div>
                <i data-lucide="chevron-down" style="width: 16px; height: 16px; transition: transform 0.2s;" :style="advancedOpen ? 'transform: rotate(180deg)' : ''"></i>
            </button>

            <div class="accordion-body" x-show="advancedOpen" x-transition>
                <!-- Number of Frames -->
                <div class="control-row">
                    <div class="control-label-wrap">
                        <span>Video Frames</span>
                        <span class="badge-val" x-text="numFrames + ' Frames'"></span>
                    </div>
                    <div class="segmented-group">
                        <button type="button" class="segmented-btn" :class="{ 'active': numFrames === 41 }" @click="!isGenerating && (numFrames = 41)" :disabled="isGenerating">41 (~1.7s)</button>
                        <button type="button" class="segmented-btn" :class="{ 'active': numFrames === 81 }" @click="!isGenerating && (numFrames = 81)" :disabled="isGenerating">81 (~3.4s)</button>
                        <button type="button" class="segmented-btn" :class="{ 'active': numFrames === 121 }" @click="!isGenerating && (numFrames = 121)" :disabled="isGenerating">121 (~5.0s)</button>
                    </div>
                </div>

                <!-- Frame Rate FPS -->
                <div class="control-row">
                    <div class="control-label-wrap">
                        <span>Frame Rate</span>
                        <span class="badge-val" x-text="framesPerSecond + ' FPS'"></span>
                    </div>
                    <div class="segmented-group">
                        <button type="button" class="segmented-btn" :class="{ 'active': framesPerSecond === 24 }" @click="!isGenerating && (framesPerSecond = 24)" :disabled="isGenerating">24 FPS (Cinematic)</button>
                        <button type="button" class="segmented-btn" :class="{ 'active': framesPerSecond === 16 }" @click="!isGenerating && (framesPerSecond = 16)" :disabled="isGenerating">16 FPS (Smooth)</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Generate Action Button -->
        <div class="generate-button-wrapper" style="margin-top: 4px;">
            <template x-if="!isGenerating">
                <button 
                    type="button" 
                    class="btn-generate"
                    :disabled="!selectedFile || !prompt.trim()"
                    @click="startGeneration"
                >
                    <i data-lucide="clapperboard" style="width: 18px; height: 18px;"></i>
                    <span>Generate Video from Image</span>
                </button>
            </template>

            <template x-if="isGenerating">
                <button 
                    type="button" 
                    class="btn-generate btn-generating"
                    @click="stopGeneration"
                >
                    <div class="spinner-sm"></div>
                    <span>Stop Generation</span>
                </button>
            </template>
        </div>
    </div>

    <!-- Right Column: Result Workspace Canvas -->
    <div class="display-workspace">
        <div class="result-card">
            <div class="result-card-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="result-title">Result Preview</span>
                    <template x-if="!currentGeneration || currentGeneration.status !== 'succeeded'">
                        <span class="neural-live-badge" :class="{ 'active': isGenerating }">
                            <span class="neural-status-dot" :class="{ 'pulse': !isGenerating, 'active-spinner': isGenerating }"></span>
                            <span x-text="isGenerating ? 'Neural Engine Active' : 'Neural Canvas Ready'"></span>
                        </span>
                    </template>
                </div>

                <template x-if="currentGeneration && currentGeneration.status === 'succeeded'">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div class="result-meta-tags">
                            <span class="meta-tag" x-text="currentGeneration.aspect_ratio"></span>
                            <span class="meta-tag" x-text="currentGeneration.resolution || '720p'"></span>
                            <span class="meta-tag" x-text="(currentGeneration.frame_rate || 24) + ' FPS'"></span>
                            <span class="meta-tag" x-text="(currentGeneration.num_frames || 81) + ' Frames'"></span>
                        </div>
                        <button type="button" class="btn-action" @click="createNewVideo()" title="Start a fresh video generation" style="padding: 4px 10px; font-size: 0.74rem;">
                            <i data-lucide="plus-circle" style="width: 13px; height: 13px;"></i>
                            <span>New Video</span>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Preview Viewport Stage -->
            <div class="preview-stage" :class="{ 'has-result': currentGeneration && currentGeneration.status === 'succeeded' }">
                <!-- 1. Shared Neural Pulse Canvas Container (Active during Initial & Generating states) -->
                <div class="neural-pulse-container" x-show="!currentGeneration || currentGeneration.status !== 'succeeded'" x-transition:leave.duration.300ms>
                    <canvas id="i2v-neural-canvas" class="neural-pulse-canvas"></canvas>

                    <!-- Initial State Overlay -->
                    <template x-if="!isGenerating && (!currentGeneration || currentGeneration.status === 'idle')">
                        <div class="neural-overlay-content">
                            <div class="neural-center-icon">
                                <i data-lucide="clapperboard" style="width: 22px; height: 22px; color: var(--brand-cyan);"></i>
                            </div>
                            <h3 class="neural-title">Ready to Animate Image</h3>
                            <p class="neural-subtitle">Select a source image on the left and enter a motion prompt to synthesize a cinematic video clip.</p>
                        </div>
                    </template>

                    <!-- Generating State Overlay -->
                    <template x-if="isGenerating">
                        <div class="neural-overlay-content">
                            <div class="neural-center-icon active-glow">
                                <div class="neural-pulse-ring"></div>
                                <i data-lucide="cpu" style="width: 24px; height: 24px; color: #ffffff;"></i>
                            </div>
                            <h3 class="neural-title neural-pulse-text">Generating your creation</h3>
                            <div class="neural-dynamic-step">
                                <div class="ai-inline-spinner"></div>
                                <span class="neural-step-text" x-text="statusMessage"></span>
                            </div>
                            <div class="neural-time-pill" x-text="'Elapsed: ' + elapsedSeconds + 's'"></div>
                        </div>
                    </template>

                    <!-- Cancelled State Overlay -->
                    <template x-if="!isGenerating && currentGeneration && currentGeneration.status === 'cancelled'">
                        <div class="neural-overlay-content">
                            <div class="error-icon-wrap" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                                <i data-lucide="ban" style="width: 26px; height: 26px;"></i>
                            </div>
                            <h3 class="neural-title" style="color: #f59e0b;">Video Generation Stopped</h3>
                            <p class="neural-subtitle" x-text="currentGeneration.error_message || 'Video generation was stopped by user.'"></p>
                            <button type="button" class="btn-action" @click="createNewVideo()" style="margin-top: 10px;">
                                <i data-lucide="plus-circle" style="width: 14px; height: 14px;"></i>
                                <span>New Video</span>
                            </button>
                        </div>
                    </template>

                    <!-- Failed Error State Overlay -->
                    <template x-if="!isGenerating && currentGeneration && currentGeneration.status === 'failed'">
                        <div class="neural-overlay-content error-state">
                            <div class="error-icon-wrap">
                                <i data-lucide="alert-triangle" style="width: 26px; height: 26px; color: #f87171;"></i>
                            </div>
                            <h3 class="neural-title" style="color: #f87171;">Video Generation Failed</h3>
                            <p class="neural-subtitle" x-text="currentGeneration.error_message || 'Something went wrong while creating your video result.'"></p>
                            <button type="button" class="btn-action btn-action-primary" @click="createNewVideo()" style="margin-top: 10px;">
                                <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                                <span>New Video</span>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- 2. Succeeded Video Player -->
                <template x-if="currentGeneration && currentGeneration.status === 'succeeded'">
                    <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative;" x-transition:enter.duration.400ms>
                        <video 
                            :src="currentGeneration.video_url" 
                            class="preview-image"
                            controls 
                            autoplay 
                            loop 
                            playsinline
                            style="max-height: 580px; width: 100%; object-fit: contain; background: #000; border-radius: var(--radius-md);"
                        ></video>
                    </div>
                </template>
            </div>

            <!-- Result Prompt & Source Image Box -->
            <template x-if="currentGeneration && currentGeneration.status === 'succeeded'">
                <div class="result-prompt-box" style="display: flex; gap: 14px; align-items: flex-start;">
                    <template x-if="currentGeneration.source_image_url">
                        <div style="flex-shrink: 0; width: 56px; height: 56px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-default); cursor: pointer;" @click="openLightbox(currentGeneration.source_image_url)">
                            <img :src="currentGeneration.source_image_url" alt="Source" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    </template>
                    <div style="flex-grow: 1;">
                        <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;">Motion Prompt</div>
                        <p x-text="currentGeneration.prompt" style="margin: 0;"></p>
                    </div>
                    <button type="button" class="btn-icon-square" @click="copyPrompt(currentGeneration.prompt)" title="Copy prompt">
                        <i data-lucide="copy" style="width: 16px; height: 16px;"></i>
                    </button>
                </div>
            </template>

            <!-- Action Toolbar (Download, Share, Delete, Remix) -->
            <template x-if="currentGeneration && currentGeneration.status === 'succeeded'">
                <div class="action-toolbar">
                    <div class="action-btn-group">
                        <a :href="'/tools/image-to-video/download/' + currentGeneration.id" class="btn-action btn-action-primary" download>
                            <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                            <span>Download Video (MP4)</span>
                        </a>

                        <button type="button" class="btn-action" @click="createNewVideo()" title="Start a fresh video creation">
                            <i data-lucide="plus-circle" style="width: 16px; height: 16px; color: var(--brand-cyan);"></i>
                            <span>New Video</span>
                        </button>

                        <button type="button" class="btn-action" @click="shareGeneration(currentGeneration)">
                            <i data-lucide="share-2" style="width: 16px; height: 16px;"></i>
                            <span>Share</span>
                        </button>

                        <button type="button" class="btn-action" @click="remixPrompt(currentGeneration)">
                            <i data-lucide="refresh-cw" style="width: 16px; height: 16px;"></i>
                            <span>Remix</span>
                        </button>
                    </div>

                    <div class="action-btn-group">
                        <button type="button" class="btn-action btn-action-danger" @click="deleteGeneration(currentGeneration.id)">
                            <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                            <span>Delete</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@if(isset($toolArticle) && $toolArticle->isPublished())
    @include('tools.partials.seo-article', ['article' => $toolArticle])
@endif
