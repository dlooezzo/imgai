<!-- Refactored Header: Glowing Cyan Visual Identity -->
<div style="position: relative; overflow: hidden; border-radius: 16px; background: linear-gradient(90deg, rgba(6, 182, 212, 0.12) 0%, rgba(6, 182, 212, 0.04) 50%, transparent 100%); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 0 25px rgba(6, 182, 212, 0.12); padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
    <div style="display: flex; align-items: center; gap: 14px;">
        <div style="display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px; background: rgba(6, 182, 212, 0.12); border: 1px solid rgba(6, 182, 212, 0.35); color: #22d3ee; box-shadow: 0 0 15px rgba(6, 182, 212, 0.35);">
            <i data-lucide="sparkles" style="width: 22px; height: 22px;"></i>
        </div>
        <h1 style="font-size: 1.35rem; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.02em;">Text-to-Image</h1>
    </div>
</div>

<!-- Main Two-Column Generator Grid -->
<div class="generator-grid">
    <!-- Left Column: Controls & Configuration Panel -->
    <div class="form-panel">
        <!-- Prompt Input Section -->
        <div class="prompt-container">
            <div class="form-section-title">
                <span>Prompt Description</span>
                <span style="font-size: 0.75rem; text-transform: none; color: var(--text-muted);" x-text="prompt.length + ' / 2000'"></span>
            </div>

            <div class="prompt-textarea-wrapper">
                <textarea 
                    class="prompt-textarea" 
                    x-model="prompt" 
                    placeholder="Describe your scene in detail (e.g. A cinematic photorealistic portrait of an astronaut on a desert planet at sunset, 35mm lens, volumetric lighting...)"
                    maxlength="2000"
                    :disabled="isGenerating"
                ></textarea>
            </div>

            <div class="prompt-actions-bar">
                <span>Inspire me:</span>
                <button type="button" class="chip-btn" style="border: none; background: none; color: var(--brand-cyan);" @click="prompt = ''" x-show="prompt.length > 0 && !isGenerating">Clear</button>
            </div>

            <div class="prompt-sample-chips">
                <template x-for="(sample, idx) in samplePrompts" :key="idx">
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
                <!-- 1:1 Square -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '1:1' }" @click="!isGenerating && (aspectRatio = '1:1')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 20px; height: 20px;"></div>
                    <span class="ratio-label">1:1</span>
                    <span class="ratio-desc">Square</span>
                </button>

                <!-- 16:9 Landscape -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '16:9' }" @click="!isGenerating && (aspectRatio = '16:9')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 28px; height: 16px;"></div>
                    <span class="ratio-label">16:9</span>
                    <span class="ratio-desc">Cinema</span>
                </button>

                <!-- 9:16 Portrait / Story -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '9:16' }" @click="!isGenerating && (aspectRatio = '9:16')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 16px; height: 28px;"></div>
                    <span class="ratio-label">9:16</span>
                    <span class="ratio-desc">Mobile</span>
                </button>

                <!-- 4:3 Standard -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '4:3' }" @click="!isGenerating && (aspectRatio = '4:3')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 24px; height: 18px;"></div>
                    <span class="ratio-label">4:3</span>
                    <span class="ratio-desc">Classic</span>
                </button>

                <!-- 3:4 Vertical -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '3:4' }" @click="!isGenerating && (aspectRatio = '3:4')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 18px; height: 24px;"></div>
                    <span class="ratio-label">3:4</span>
                    <span class="ratio-desc">Portrait</span>
                </button>

                <!-- 21:9 Ultrawide -->
                <button type="button" class="ratio-btn" :class="{ 'active': aspectRatio === '21:9' }" @click="!isGenerating && (aspectRatio = '21:9')" :disabled="isGenerating">
                    <div class="ratio-visual-box" style="width: 32px; height: 14px;"></div>
                    <span class="ratio-label">21:9</span>
                    <span class="ratio-desc">Ultrawide</span>
                </button>
            </div>
        </div>

        <!-- Advanced Options Accordion -->
        <div class="accordion">
            <button type="button" class="accordion-header" @click="advancedOpen = !advancedOpen">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="sliders" style="width: 16px; height: 16px;"></i>
                    <span>Advanced Output Settings</span>
                </div>
                <i data-lucide="chevron-down" style="width: 16px; height: 16px; transition: transform 0.2s;" :style="advancedOpen ? 'transform: rotate(180deg)' : ''"></i>
            </button>

            <div class="accordion-body" x-show="advancedOpen" x-transition>
                <!-- Resolution Megapixels -->
                <div class="control-row">
                    <div class="control-label-wrap">
                        <span>Resolution</span>
                        <span class="badge-val" x-text="megapixels + ' Megapixel' + (megapixels > 1 ? 's' : '')"></span>
                    </div>
                    <div class="segmented-group">
                        <button type="button" class="segmented-btn" :class="{ 'active': megapixels === 1 }" @click="!isGenerating && (megapixels = 1)" :disabled="isGenerating">1 MP (Fast)</button>
                        <button type="button" class="segmented-btn" :class="{ 'active': megapixels === 2 }" @click="!isGenerating && (megapixels = 2)" :disabled="isGenerating">2 MP (Ultra HD)</button>
                    </div>
                </div>

                <!-- Format Selection -->
                <div class="control-row">
                    <div class="control-label-wrap">
                        <span>Output Format</span>
                        <span class="badge-val" x-text="outputFormat.toUpperCase()"></span>
                    </div>
                    <div class="segmented-group">
                        <button type="button" class="segmented-btn" :class="{ 'active': outputFormat === 'jpg' }" @click="!isGenerating && (outputFormat = 'jpg')" :disabled="isGenerating">JPG</button>
                        <button type="button" class="segmented-btn" :class="{ 'active': outputFormat === 'png' }" @click="!isGenerating && (outputFormat = 'png')" :disabled="isGenerating">PNG</button>
                        <button type="button" class="segmented-btn" :class="{ 'active': outputFormat === 'webp' }" @click="!isGenerating && (outputFormat = 'webp')" :disabled="isGenerating">WEBP</button>
                    </div>
                </div>

                <!-- JPG Quality Slider (Only when JPG selected) -->
                <div class="control-row" x-show="outputFormat === 'jpg'">
                    <div class="control-label-wrap">
                        <span>JPG Quality</span>
                        <span class="badge-val" x-text="outputQuality + '%'"></span>
                    </div>
                    <input type="range" class="slider-input" min="1" max="100" x-model="outputQuality" :disabled="isGenerating">
                </div>

                <!-- Seed Input -->
                <div class="control-row">
                    <div class="control-label-wrap">
                        <span>Seed (Reproducibility)</span>
                        <span class="badge-val" x-text="seed ? seed : 'Random'"></span>
                    </div>
                    <div class="input-with-action">
                        <input type="number" class="text-input" x-model="seed" placeholder="Random seed..." :disabled="isGenerating">
                        <button type="button" class="btn-icon-square" @click="randomizeSeed()" title="Randomize seed" :disabled="isGenerating">
                            <i data-lucide="dices" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>
                </div>

                <!-- Juiced / Warm Speed Mode -->
                <div class="switch-row" @click="!isGenerating && (juiced = !juiced)">
                    <div class="switch-label-block">
                        <span class="switch-title">Fast "Juiced" Mode</span>
                        <span class="switch-desc">Accelerate inference speed with optimized warm nodes</span>
                    </div>
                    <div class="toggle-switch" :class="{ 'active': juiced }">
                        <div class="toggle-handle"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generate & Stop CTA Buttons -->
        <div style="display: flex; gap: 10px; width: 100%; align-items: stretch;">
            <button type="button" class="btn-generate" :disabled="isGenerating || !prompt.trim()" @click="startGeneration()" style="flex: 1;">
                <template x-if="!isGenerating">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i data-lucide="sparkles" style="width: 20px; height: 20px;"></i>
                        <span>Generate Image</span>
                    </div>
                </template>

                <template x-if="isGenerating">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <div style="width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: rotateOrb 0.8s linear infinite;"></div>
                        <span>Generating (<span x-text="elapsedSeconds + 's'"></span>)...</span>
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
                title="Stop this generation"
            >
                <i data-lucide="square" style="width: 16px; height: 16px; fill: currentColor;"></i>
                <span>Stop</span>
            </button>
        </div>
    </div>

    <!-- Right Column: Result Workspace & Details -->
    <div class="display-workspace">
        <!-- Active Generation Canvas Card -->
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
                            <span class="meta-tag" x-text="currentGeneration.megapixels + ' MP'"></span>
                            <span class="meta-tag" x-text="currentGeneration.output_format.toUpperCase()"></span>
                            <template x-if="currentGeneration.seed">
                                <span class="meta-tag" x-text="'Seed: ' + currentGeneration.seed"></span>
                            </template>
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
                    <canvas id="image-neural-canvas" class="neural-pulse-canvas"></canvas>

                    <!-- Initial State Overlay -->
                    <template x-if="!isGenerating && (!currentGeneration || currentGeneration.status === 'idle')">
                        <div class="neural-overlay-content">
                            <div class="neural-center-icon">
                                <i data-lucide="sparkles" style="width: 22px; height: 22px; color: var(--brand-cyan);"></i>
                            </div>
                            <h3 class="neural-title">Ready to Synthesize</h3>
                            <p class="neural-subtitle">Describe your vision in the prompt and press Generate to produce high-resolution AI art.</p>
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
                            <h3 class="neural-title" style="color: #f59e0b;">Generation Stopped</h3>
                            <p class="neural-subtitle" x-text="currentGeneration.error_message || 'Generation was stopped by user.'"></p>
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
                            <h3 class="neural-title" style="color: #f87171;">Generation Failed</h3>
                            <p class="neural-subtitle" x-text="currentGeneration.error_message || 'Something went wrong while creating your result.'"></p>
                            <button type="button" class="btn-action btn-action-primary" @click="currentGeneration = null; generationStatus = 'idle'" style="margin-top: 10px;">
                                <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                                <span>Try Again</span>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- 2. Succeeded Image Preview (Fades in smoothly upon generation success) -->
                <template x-if="currentGeneration && currentGeneration.status === 'succeeded'">
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; position: relative;" x-transition:enter.duration.400ms>
                        <img 
                            :src="currentGeneration.image_url" 
                            :alt="currentGeneration.prompt" 
                            class="preview-image"
                            @click="openLightbox(currentGeneration.image_url)"
                            title="Click to expand fullscreen"
                            style="cursor: zoom-in;"
                        >
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
                        <a :href="'/tools/image-generator/download/' + currentGeneration.id" class="btn-action btn-action-primary" download>
                            <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                            <span>Download High-Res</span>
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
