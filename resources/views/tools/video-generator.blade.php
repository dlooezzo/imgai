<!-- Refactored Header: Glowing Purple Visual Identity -->
<div style="position: relative; overflow: hidden; border-radius: 16px; background: linear-gradient(90deg, rgba(168, 85, 247, 0.12) 0%, rgba(168, 85, 247, 0.04) 50%, transparent 100%); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 0 25px rgba(168, 85, 247, 0.12); padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; gap: 14px;">
        <div style="display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px; background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.35); color: #c084fc; box-shadow: 0 0 15px rgba(168, 85, 247, 0.35);">
            <i data-lucide="video" style="width: 22px; height: 22px;"></i>
        </div>
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.02em;">Text-to-Video Audio</h1>
            <p style="margin: 3px 0 0; font-size: 0.78rem; color: #a855f7;">Powered by Seedance 1.5 Pro with native sound generation</p>
        </div>
    </div>
</div>

<!-- Glowing Premium AI Tools Crystal Card -->
@include('tools.partials.crystal-card')

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
                    placeholder="Describe your video scene with motion and audio (e.g. A breathtaking drone time-lapse sweeping over emerald fjords at sunrise with rushing ocean waves, volumetric sunlight, 4k cinematic motion...)"
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

        <!-- Duration Selection (Seedance 1.5 Pro: 5s, 8s, 12s) -->
        <div class="control-row">
            <div class="form-section-title">
                <span>Duration</span>
                <span class="badge-val" x-text="duration + 's'"></span>
            </div>
            <div class="segmented-group" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">
                <button type="button" class="segmented-btn" :class="{ 'active': duration === 5 }" @click="!isGenerating && (duration = 5)" :disabled="isGenerating">
                    <span>5 Seconds</span>
                </button>
                <button type="button" class="segmented-btn" :class="{ 'active': duration === 8 }" @click="!isGenerating && (duration = 8)" :disabled="isGenerating">
                    <span>8 Seconds</span>
                </button>
                <button type="button" class="segmented-btn" :class="{ 'active': duration === 12 }" @click="!isGenerating && (duration = 12)" :disabled="isGenerating">
                    <span>12 Seconds</span>
                </button>
            </div>
        </div>

        <!-- Resolution Selection (Seedance 1.5 Pro: 480p, 720p, 1080p) -->
        <div class="control-row">
            <div class="form-section-title">
                <span>Resolution</span>
                <span class="badge-val" x-text="resolution"></span>
            </div>
            <div class="segmented-group" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px;">
                <button type="button" class="segmented-btn" :class="{ 'active': resolution === '480p' }" @click="!isGenerating && (resolution = '480p')" :disabled="isGenerating">
                    <span>480p</span>
                </button>
                <button type="button" class="segmented-btn" :class="{ 'active': resolution === '720p' }" @click="!isGenerating && (resolution = '720p')" :disabled="isGenerating">
                    <span>720p (HD)</span>
                </button>
                <button type="button" class="segmented-btn" :class="{ 'active': resolution === '1080p' }" @click="!isGenerating && (resolution = '1080p')" :disabled="isGenerating">
                    <span>1080p (FHD)</span>
                </button>
            </div>
        </div>

        <!-- Aspect Ratio Selection (Supported ratios: 21:9, 16:9, 4:3, 1:1, 3:4, 9:16, 9:21, adaptive) -->
        <div class="control-row">
            <div class="form-section-title">
                <span>Aspect Ratio</span>
                <span class="badge-val" x-text="aspectRatio"></span>
            </div>

            <div class="ratio-grid" style="grid-template-columns: repeat(4, 1fr); gap: 8px;">
                <!-- 16:9 Cinema -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '16:9' }" @click="!isGenerating && (aspectRatio = '16:9')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 26px; height: 15px;"></div>
                    <span class="ratio-label">16:9</span>
                    <span class="ratio-desc">Cinema</span>
                </button>

                <!-- 9:16 Shorts / Reels -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '9:16' }" @click="!isGenerating && (aspectRatio = '9:16')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 15px; height: 26px;"></div>
                    <span class="ratio-label">9:16</span>
                    <span class="ratio-desc">Reels</span>
                </button>

                <!-- 1:1 Square -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '1:1' }" @click="!isGenerating && (aspectRatio = '1:1')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 18px; height: 18px;"></div>
                    <span class="ratio-label">1:1</span>
                    <span class="ratio-desc">Square</span>
                </button>

                <!-- 4:3 Classic -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '4:3' }" @click="!isGenerating && (aspectRatio = '4:3')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 22px; height: 16px;"></div>
                    <span class="ratio-label">4:3</span>
                    <span class="ratio-desc">Classic</span>
                </button>

                <!-- 3:4 Portrait -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '3:4' }" @click="!isGenerating && (aspectRatio = '3:4')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 16px; height: 22px;"></div>
                    <span class="ratio-label">3:4</span>
                    <span class="ratio-desc">Portrait</span>
                </button>

                <!-- 21:9 Ultrawide -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '21:9' }" @click="!isGenerating && (aspectRatio = '21:9')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 28px; height: 12px;"></div>
                    <span class="ratio-label">21:9</span>
                    <span class="ratio-desc">Ultrawide</span>
                </button>

                <!-- 9:21 Vertical Ultrawide -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '9:21' }" @click="!isGenerating && (aspectRatio = '9:21')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 12px; height: 28px;"></div>
                    <span class="ratio-label">9:21</span>
                    <span class="ratio-desc">Ultra Tall</span>
                </button>

                <!-- adaptive -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === 'adaptive' }" @click="!isGenerating && (aspectRatio = 'adaptive')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 20px; height: 20px; border-style: dashed;"></div>
                    <span class="ratio-label">Adaptive</span>
                    <span class="ratio-desc">Auto</span>
                </button>
            </div>
        </div>

        <!-- Audio Generation (Seedance 1.5 Pro: generate_audio boolean) -->
        <div class="control-row">
            <div class="form-section-title">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="volume-2" style="width: 16px; height: 16px; color: var(--brand-cyan);"></i>
                    <span>Generate Audio</span>
                </div>
                <span class="badge-val" :style="generateAudio ? 'color: #38bdf8; border-color: rgba(56,189,248,0.4);' : ''" x-text="generateAudio ? 'Audio Enabled' : 'No Audio'"></span>
            </div>
            <div class="segmented-group" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px;">
                <button type="button" class="segmented-btn" :class="{ 'active': !generateAudio }" @click="!isGenerating && (generateAudio = false)" :disabled="isGenerating">
                    <i data-lucide="volume-x" style="width: 15px; height: 15px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                    <span>No (Mute)</span>
                </button>
                <button type="button" class="segmented-btn" :class="{ 'active': generateAudio }" @click="!isGenerating && (generateAudio = true)" :disabled="isGenerating">
                    <i data-lucide="volume-2" style="width: 15px; height: 15px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                    <span>Yes (Audio)</span>
                </button>
            </div>
        </div>

        <!-- Advanced Video Settings Accordion -->
        <div class="accordion">
            <button type="button" class="accordion-header" @click="advancedOpen = !advancedOpen">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="sliders" style="width: 16px; height: 16px;"></i>
                    <span>Advanced Seed & Camera Settings</span>
                </div>
                <i data-lucide="chevron-down" style="width: 16px; height: 16px; transition: transform 0.2s;" :style="advancedOpen ? 'transform: rotate(180deg)' : ''"></i>
            </button>

            <div class="accordion-body" x-show="advancedOpen" x-transition>
                <!-- Seed (Integer, optional) -->
                <div class="control-row">
                    <div class="control-label-wrap">
                        <span>Seed (Reproducibility)</span>
                        <span class="badge-val" x-text="seed !== null && seed !== '' ? seed : 'Random'"></span>
                    </div>
                    <input 
                        type="number" 
                        class="prompt-textarea" 
                        style="height: 42px; padding: 8px 12px; resize: none; font-size: 0.88rem;" 
                        placeholder="Leave empty for random seed" 
                        min="0" 
                        max="2147483647" 
                        x-model="seed" 
                        :disabled="isGenerating"
                    >
                </div>

                <!-- Camera Fixed Toggle -->
                <div class="control-row" style="margin-top: 14px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-size: 0.84rem; font-weight: 600; color: #e2e8f0;">Fixed Camera</div>
                            <div style="font-size: 0.74rem; color: var(--text-muted);">Keep the camera viewpoint stable with minimal movement</div>
                        </div>
                        <label class="toggle-switch" style="position: relative; display: inline-block; width: 44px; height: 24px; cursor: pointer;">
                            <input type="checkbox" x-model="camerafixed" :disabled="isGenerating" style="opacity: 0; width: 0; height: 0;">
                            <span class="toggle-slider" :style="camerafixed ? 'background-color: var(--brand-purple, #a855f7);' : 'background-color: rgba(255,255,255,0.15);'" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; border-radius: 24px; transition: 0.3s;">
                                <span :style="camerafixed ? 'transform: translateX(20px);' : 'transform: translateX(0);'" style="position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: 0.3s; display: block;"></span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Watermark Toggle -->
                <div class="control-row" style="margin-top: 14px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-size: 0.84rem; font-weight: 600; color: #e2e8f0;">Watermark</div>
                            <div style="font-size: 0.74rem; color: var(--text-muted);">Include default BytePlus watermark on video output</div>
                        </div>
                        <label class="toggle-switch" style="position: relative; display: inline-block; width: 44px; height: 24px; cursor: pointer;">
                            <input type="checkbox" x-model="watermark" :disabled="isGenerating" style="opacity: 0; width: 0; height: 0;">
                            <span class="toggle-slider" :style="watermark ? 'background-color: var(--brand-purple, #a855f7);' : 'background-color: rgba(255,255,255,0.15);'" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; border-radius: 24px; transition: 0.3s;">
                                <span :style="watermark ? 'transform: translateX(20px);' : 'transform: translateX(0);'" style="position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; border-radius: 50%; transition: 0.3s; display: block;"></span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic Credit Cost Policy Indicator -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: var(--radius-md); margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i data-lucide="coins" style="width: 16px; height: 16px; color: #c084fc;"></i>
                <span style="font-size: 0.84rem; font-weight: 600; color: #e2e8f0;">Estimated Cost:</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 0.95rem; font-weight: 800; color: #c084fc;" x-text="computedCreditCost"></span>
                <span style="font-size: 0.76rem; color: #a855f7; font-weight: 600;">Credits</span>
            </div>
        </div>

        <!-- Generate Video CTA & Stop CTA Buttons -->
        <div style="display: flex; gap: 10px; width: 100%; align-items: stretch;">
            <button type="button" class="btn-generate" :disabled="isGenerating || !prompt.trim()" @click="startGeneration()" style="flex: 1;">
                <template x-if="!isGenerating">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i data-lucide="sparkles" style="width: 20px; height: 20px;"></i>
                        <span>Generate Video (<span x-text="computedCreditCost"></span> Credits)</span>
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
                            <span x-text="isGenerating ? 'Seedance Engine Active' : 'Neural Canvas Ready'"></span>
                        </span>
                    </template>
                </div>

                <template x-if="currentGeneration && currentGeneration.status === 'succeeded'">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div class="result-meta-tags">
                            <span class="meta-tag" x-text="currentGeneration.resolution || '720p'"></span>
                            <span class="meta-tag" x-text="currentGeneration.aspect_ratio"></span>
                            <span class="meta-tag" x-text="(currentGeneration.duration || 5) + 's'"></span>
                            <span class="meta-tag" :style="currentGeneration.generate_audio ? 'color: #38bdf8; border-color: rgba(56,189,248,0.4);' : ''" x-text="currentGeneration.generate_audio ? 'Audio On' : 'No Audio'"></span>
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
                            <h3 class="neural-title">Ready to Synthesize Video with Audio</h3>
                            <p class="neural-subtitle">Describe your cinematic scene on the left, pick resolution & duration, and click Generate Video.</p>
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
