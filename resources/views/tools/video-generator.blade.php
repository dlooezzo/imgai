<!-- Refactored Header: Glowing Purple Visual Identity -->
<div style="position: relative; overflow: hidden; border-radius: 16px; background: linear-gradient(90deg, rgba(168, 85, 247, 0.12) 0%, rgba(168, 85, 247, 0.04) 50%, transparent 100%); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 0 25px rgba(168, 85, 247, 0.12); padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; gap: 14px;">
        <div style="display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px; background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.35); color: #c084fc; box-shadow: 0 0 15px rgba(168, 85, 247, 0.35);">
            <i data-lucide="video" style="width: 22px; height: 22px;"></i>
        </div>
        <h1 style="font-size: 1.35rem; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.02em;">Text-to-Video</h1>
    </div>
</div>

    <!-- Main Two-Column Video Generator Grid -->
    <div class="generator-grid">
        <!-- Left Column: Controls & Configuration Panel -->
        <div class="form-panel">
            <!-- Prompt Input Section -->
            <div class="prompt-container">
                <div class="form-section-title">
                    <span>Video Prompt Description</span>
                    <span style="font-size: 0.75rem; text-transform: none; color: var(--text-muted);" x-text="prompt.length + ' / 2500'"></span>
                </div>

                <div class="prompt-textarea-wrapper">
                    <textarea 
                        class="prompt-textarea" 
                        x-model="prompt" 
                        placeholder="Describe your video scene with motion (e.g. A breathtaking drone time-lapse sweeping over misty emerald fjords at sunrise, volumetric sunlight, 4k cinematic motion...)"
                        maxlength="2500"
                        :disabled="isGenerating"
                    ></textarea>
                </div>

                <div class="prompt-actions-bar">
                    <span>Inspire me:</span>
                    <button type="button" class="chip-btn" style="border: none; background: none; color: var(--brand-cyan);" @click="prompt = ''" x-show="prompt.length > 0 && !isGenerating">Clear</button>
                </div>

                <div class="prompt-sample-chips">
                    <template x-for="(sample, idx) in sampleVideoPrompts" :key="idx">
                        <button type="button" class="chip-btn" @click="useSamplePrompt(sample)" :title="sample" :disabled="isGenerating">
                            <span x-text="sample.substring(0, 32) + '...'"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Aspect Ratio Selection -->
            <div class="control-row">
                <div class="form-section-title">
                    <span>Aspect Ratio</span>
                    <span class="badge-val" x-text="aspectRatio"></span>
                </div>

                <div class="ratio-grid">
                    <!-- 16:9 Landscape / Cinema -->
                    <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '16:9' }" @click="!isGenerating && (aspectRatio = '16:9')" :disabled="isGenerating">
                        <div class="ratio-visual-box" style="width: 28px; height: 16px;"></div>
                        <span class="ratio-label">16:9</span>
                        <span class="ratio-desc">Cinema</span>
                    </button>

                    <!-- 9:16 Portrait / Reels -->
                    <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '9:16' }" @click="!isGenerating && (aspectRatio = '9:16')" :disabled="isGenerating">
                        <div class="ratio-visual-box" style="width: 16px; height: 28px;"></div>
                        <span class="ratio-label">9:16</span>
                        <span class="ratio-desc">Reels / Shorts</span>
                    </button>

                    <!-- 1:1 Square -->
                    <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '1:1' }" @click="!isGenerating && (aspectRatio = '1:1')" :disabled="isGenerating">
                        <div class="ratio-visual-box" style="width: 20px; height: 20px;"></div>
                        <span class="ratio-label">1:1</span>
                        <span class="ratio-desc">Square</span>
                    </button>

                    <!-- 4:3 Classic -->
                    <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '4:3' }" @click="!isGenerating && (aspectRatio = '4:3')" :disabled="isGenerating">
                        <div class="ratio-visual-box" style="width: 24px; height: 18px;"></div>
                        <span class="ratio-label">4:3</span>
                        <span class="ratio-desc">Classic</span>
                    </button>

                    <!-- 21:9 Ultrawide -->
                    <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '21:9' }" @click="!isGenerating && (aspectRatio = '21:9')" :disabled="isGenerating">
                        <div class="ratio-visual-box" style="width: 32px; height: 14px;"></div>
                        <span class="ratio-label">21:9</span>
                        <span class="ratio-desc">Ultrawide</span>
                    </button>
                </div>
            </div>

            <!-- Advanced Video Settings Accordion -->
            <div class="accordion">
                <button type="button" class="accordion-header" @click="advancedOpen = !advancedOpen">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="sliders" style="width: 16px; height: 16px;"></i>
                        <span>Motion & Rendering Settings</span>
                    </div>
                    <i data-lucide="chevron-down" style="width: 16px; height: 16px; transition: transform 0.2s;" :style="advancedOpen ? 'transform: rotate(180deg)' : ''"></i>
                </button>

                <div class="accordion-body" x-show="advancedOpen" x-transition>
                    <!-- Frame Rate Selection -->
                    <div class="control-row">
                        <div class="control-label-wrap">
                            <span>Frame Rate</span>
                            <span class="badge-val" x-text="frameRate + ' FPS'"></span>
                        </div>
                        <div class="segmented-group">
                            <button type="button" class="segmented-btn" :class="{ 'active': frameRate === 24 }" @click="!isGenerating && (frameRate = 24)" :disabled="isGenerating">24 FPS (Cinematic)</button>
                            <button type="button" class="segmented-btn" :class="{ 'active': frameRate === 30 }" @click="!isGenerating && (frameRate = 30)" :disabled="isGenerating">30 FPS (Smooth)</button>
                        </div>
                    </div>

                    <!-- Steps Selection -->
                    <div class="control-row">
                        <div class="control-label-wrap">
                            <span>Inference Steps</span>
                            <span class="badge-val" x-text="steps + ' Steps'"></span>
                        </div>
                        <div class="segmented-group">
                            <button type="button" class="segmented-btn" :class="{ 'active': steps === 20 }" @click="!isGenerating && (steps = 20)" :disabled="isGenerating">20 (Fast)</button>
                            <button type="button" class="segmented-btn" :class="{ 'active': steps === 30 }" @click="!isGenerating && (steps = 30)" :disabled="isGenerating">30 (Standard)</button>
                            <button type="button" class="segmented-btn" :class="{ 'active': steps === 50 }" @click="!isGenerating && (steps = 50)" :disabled="isGenerating">50 (Ultra)</button>
                        </div>
                    </div>

                    <!-- Denoise Strength Slider -->
                    <div class="control-row">
                        <div class="control-label-wrap">
                            <span>Motion Denoise Strength</span>
                            <span class="badge-val" x-text="denoiseStrength"></span>
                        </div>
                        <input type="range" class="slider-input" min="0.1" max="1.0" step="0.05" x-model.number="denoiseStrength" :disabled="isGenerating">
                    </div>

                    <!-- Guidance Scale Slider -->
                    <div class="control-row">
                        <div class="control-label-wrap">
                            <span>Prompt Guidance (CFG)</span>
                            <span class="badge-val" x-text="guidanceScale"></span>
                        </div>
                        <input type="range" class="slider-input" min="1.0" max="15.0" step="0.5" x-model.number="guidanceScale" :disabled="isGenerating">
                    </div>

                    <!-- Flow Shift -->
                    <div class="control-row">
                        <div class="control-label-wrap">
                            <span>Flow Shift Motion</span>
                            <span class="badge-val" x-text="flowShift"></span>
                        </div>
                        <input type="range" class="slider-input" min="1" max="15" step="1" x-model.number="flowShift" :disabled="isGenerating">
                    </div>
                </div>
            </div>

            <!-- Generate Video CTA & Stop CTA Buttons -->
            <div style="display: flex; gap: 10px; width: 100%; align-items: stretch;">
                <button type="button" class="btn-generate" :disabled="isGenerating || !prompt.trim()" @click="startGeneration()" style="flex: 1;">
                    <template x-if="!isGenerating">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i data-lucide="sparkles" style="width: 20px; height: 20px;"></i>
                            <span>Generate Video</span>
                        </div>
                    </template>

                    <template x-if="isGenerating">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <div style="width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: rotateOrb 0.8s linear infinite;"></div>
                            <span>Rendering (<span x-text="elapsedSeconds + 's'"></span>)...</span>
                        </div>
                    </template>
                </button>

                <!-- Stop Generation Button -->
                <button 
                    type="button" 
                    class="btn-action btn-action-danger" 
                    x-show="isGenerating" 
                    @click="stopGeneration()" 
                    style="height: auto; min-height: 48px; padding: 0 20px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border-radius: var(--radius-md); font-size: 0.9rem;"
                    title="Stop this video generation"
                >
                    <i data-lucide="square" style="width: 16px; height: 16px; fill: currentColor;"></i>
                    <span>Stop</span>
                </button>
            </div>
        </div>

        <!-- Right Column: Result Workspace & Details -->
        <div class="display-workspace">
            <!-- Active Video Canvas Card -->
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
                                <span class="meta-tag" x-text="currentGeneration.width + 'x' + currentGeneration.height"></span>
                                <span class="meta-tag" x-text="currentGeneration.frame_rate + ' FPS'"></span>
                                <span class="meta-tag" x-text="currentGeneration.steps + ' Steps'"></span>
                            </div>
                            <button type="button" class="btn-action" @click="currentGeneration = null; generationStatus = 'idle'" title="Start a fresh generation" style="padding: 4px 10px; font-size: 0.74rem;">
                                <i data-lucide="plus" style="width: 13px; height: 13px;"></i>
                                <span>New</span>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Preview Viewport Stage -->
                <div class="preview-stage" :class="{ 'has-result': currentGeneration && currentGeneration.status === 'succeeded' }">
                    <!-- 1. Neural Pulse Canvas Container (Active during Initial & Generating states) -->
                    <div class="neural-pulse-container" x-show="!currentGeneration || currentGeneration.status !== 'succeeded'" x-transition:leave.duration.300ms>
                        <canvas id="video-neural-canvas" class="neural-pulse-canvas"></canvas>

                        <!-- Initial State Overlay -->
                        <template x-if="!isGenerating && (!currentGeneration || currentGeneration.status === 'idle')">
                            <div class="neural-overlay-content">
                                <div class="neural-center-icon">
                                    <i data-lucide="video" style="width: 22px; height: 22px; color: var(--brand-cyan);"></i>
                                </div>
                                <h3 class="neural-title">Ready to Synthesize Video</h3>
                                <p class="neural-subtitle">Describe your cinematic scene on the left and click Generate Video to render motion.</p>
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
                                    <span class="neural-step-text" x-text="dynamicStatusMessage"></span>
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
                                <button type="button" class="btn-action" @click="currentGeneration = null; generationStatus = 'idle'" style="margin-top: 10px;">
                                    <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                                    <span>New Prompt</span>
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
                                <button type="button" class="btn-action btn-action-primary" @click="currentGeneration = null; generationStatus = 'idle'" style="margin-top: 10px;">
                                    <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                                    <span>Try Again</span>
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- 2. Succeeded Video Player (Fades in smoothly upon generation success) -->
                    <template x-if="currentGeneration && currentGeneration.status === 'succeeded'">
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; position: relative;" x-transition:enter.duration.400ms>
                            <video 
                                :src="currentGeneration.video_url" 
                                class="preview-image"
                                controls 
                                autoplay 
                                loop 
                                playsinline
                                style="max-height: 620px; width: 100%; object-fit: contain; background: #000; border-radius: var(--radius-md);"
                            ></video>
                        </div>
                    </template>
                </div>

                <!-- Result Prompt Box (Only when result exists) -->
                <template x-if="currentGeneration && currentGeneration.status === 'succeeded'">
                    <div class="result-prompt-box">
                        <div>
                            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;">Prompt</div>
                            <p x-text="currentGeneration.prompt"></p>
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
                            <a :href="'/tools/video-generator/download/' + currentGeneration.id" class="btn-action btn-action-primary" download>
                                <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                                <span>Download Video (MP4)</span>
                            </a>

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
