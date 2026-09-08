<!-- Studio Overview Experience -->
<div class="studio-overview-container">
    <!-- 1. Two-Column Hero Composition -->
    <section class="studio-hero-section">
        <!-- Hero Ambient Glow Behind -->
        <div class="studio-hero-glow"></div>

        <div class="studio-hero-grid">
            <!-- Left Side: Brand Identity & Messaging -->
            <div class="studio-hero-content">
                @php
                    $overviewBrandTitle = strtoupper(\App\Models\SiteSetting::get('site_title', 'IMGAI'));
                    $overviewBrandTagline = strtoupper(\App\Models\SiteSetting::get('site_tagline', 'AI CREATIVE STUDIO'));
                @endphp
                <div class="studio-pill-badge">
                    <span class="studio-pulse-dot"></span>
                    <span class="studio-pill-text">{{ $overviewBrandTitle }} • {{ $overviewBrandTagline }}</span>
                </div>

                <h1 class="studio-hero-title">
                    Create Beyond<br>
                    <span class="studio-gradient-text">Imagination.</span>
                </h1>

                <p class="studio-hero-subtitle">
                    Transform ideas into powerful visual content with intelligent creative tools. Synthesize photorealistic 2MP cinematic imagery and fluid motion video in one unified workspace.
                </p>

                <!-- Action CTAs -->
                <div class="studio-hero-actions">
                    <a href="{{ route('tools.image.index') }}" class="btn-studio-primary">
                        <i data-lucide="sparkles" style="width: 18px; height: 18px;"></i>
                        <span>Start Creating</span>
                    </a>

                    <a href="#creative-tools" class="btn-studio-secondary">
                        <i data-lucide="layout-grid" style="width: 18px; height: 18px;"></i>
                        <span>Explore Tools</span>
                    </a>
                </div>

                <!-- Feature Highlights Bar -->
                <div class="studio-hero-metrics">
                    <div class="metric-item">
                        <div class="metric-icon-wrap">
                            <i data-lucide="aperture" style="width: 16px; height: 16px; color: var(--brand-cyan);"></i>
                        </div>
                        <div class="metric-text">
                            <span class="metric-label">2MP Native Resolution</span>
                            <span class="metric-sub">Wan 2.2 Cinematic Core</span>
                        </div>
                    </div>

                    <div class="metric-divider"></div>

                    <div class="metric-item">
                        <div class="metric-icon-wrap">
                            <i data-lucide="film" style="width: 16px; height: 16px; color: #c084fc;"></i>
                        </div>
                        <div class="metric-text">
                            <span class="metric-label">Temporal Motion Engine</span>
                            <span class="metric-sub">Hunyuan Diffusion 24 FPS</span>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $videoScale = (int) ($heroVideoScale ?? \App\Models\SiteSetting::get('hero_showcase_video_scale', 200));
                // Base width was 420px. At 200% scale => 840px (approx 2x).
                $calculatedMaxWidth = round(420 * ($videoScale / 100));
            @endphp
            <!-- Right Side: Cinematic Hero Video Area (Clean, borderless, frameless display) -->
            <div class="studio-hero-media-wrapper" style="--hero-video-max-width: {{ $calculatedMaxWidth }}px; max-width: {{ $calculatedMaxWidth }}px; width: 100%; margin: 0 auto; background: transparent; border: none; box-shadow: none; display: flex; justify-content: center; align-items: center;">

                <div class="hero-video-container" style="position: relative; overflow: hidden; border: none; box-shadow: none; background: transparent; width: 100%; height: auto; margin: 0 auto; display: flex; justify-content: center; align-items: center; border-radius: var(--radius-lg, 16px);">
                    
                    <!-- Hero Showcase Video — Clean, frameless, native aspect ratio, guaranteed playback -->
                    <video 
                        id="hero-showcase-video-elem"
                        autoplay 
                        loop 
                        muted 
                        playsinline 
                        webkit-playsinline
                        x5-playsinline
                        preload="auto"
                        class="hero-video-element" 
                        style="display: block; width: 100%; height: auto; max-width: 100%; max-height: 85vh; object-fit: contain; position: relative; z-index: 2; border: none; background: #07090e; box-shadow: none; border-radius: var(--radius-lg, 16px);"
                    >
                        <source src="{{ $heroVideoUrl }}" type="video/mp4">
                    </video>

                    <!-- Mobile / Desktop Interactive Play & Pause Overlay -->
                    <div id="hero-video-play-overlay" class="hero-video-play-overlay" style="position: absolute; inset: 0; z-index: 5; display: flex; align-items: center; justify-content: center; pointer-events: none; transition: opacity 0.25s ease; opacity: 0; background: rgba(7, 9, 14, 0.35);">
                        <button type="button" id="hero-video-play-btn" class="hero-video-play-btn" aria-label="Play Video" style="pointer-events: auto; width: 58px; height: 58px; border-radius: 50%; background: rgba(15, 20, 36, 0.85); backdrop-filter: blur(12px); border: 2px solid rgba(168, 85, 247, 0.6); box-shadow: 0 0 25px rgba(168, 85, 247, 0.5), 0 0 45px rgba(6, 182, 212, 0.25); color: #ffffff; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s ease;">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor" style="margin-left: 2px;"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                    </div>

                    <!-- Atmospheric Procedural Canvas — Underlay & Fallback -->
                    <div id="hero-video-fallback-canvas" class="hero-video-atmospheric-canvas" style="position: absolute; inset: 0; width: 100%; height: 100%; z-index: 1; pointer-events: none; display: flex;">
                        <div class="atmospheric-mesh-grid"></div>
                        <div class="atmospheric-light-orb orb-primary"></div>
                        <div class="atmospheric-light-orb orb-secondary"></div>
                        <div class="atmospheric-light-orb orb-accent"></div>
                        <div class="atmospheric-vignette" style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(7, 9, 14, 0.35) 0%, rgba(7, 9, 14, 0.1) 50%, rgba(7, 9, 14, 0.75) 100%), radial-gradient(circle at 50% 50%, transparent 40%, rgba(7, 9, 14, 0.6) 100%);"></div>
                    </div>

                    <script>
                        (function() {
                            var v = document.getElementById('hero-showcase-video-elem');
                            var overlay = document.getElementById('hero-video-play-overlay');
                            var btn = document.getElementById('hero-video-play-btn');
                            if (!v) return;

                            v.muted = true;
                            v.defaultMuted = true;

                            function updateOverlay() {
                                if (!overlay) return;
                                if (v.paused || v.ended) {
                                    overlay.style.opacity = '1';
                                    overlay.style.pointerEvents = 'auto';
                                } else {
                                    overlay.style.opacity = '0';
                                    overlay.style.pointerEvents = 'none';
                                }
                            }

                            v.addEventListener('play', updateOverlay);
                            v.addEventListener('pause', updateOverlay);
                            v.addEventListener('playing', updateOverlay);
                            v.addEventListener('ended', updateOverlay);

                            v.addEventListener('loadedmetadata', function() {
                                if (v.videoWidth && v.videoHeight) {
                                    v.style.aspectRatio = v.videoWidth + ' / ' + v.videoHeight;
                                }
                            });

                            function togglePlay(e) {
                                if (e) e.stopPropagation();
                                if (v.paused) {
                                    v.muted = true;
                                    var p = v.play();
                                    if (p !== undefined) {
                                        p.then(updateOverlay).catch(function(err) {
                                            console.warn('Manual playback failed:', err);
                                            updateOverlay();
                                        });
                                    }
                                } else {
                                    v.pause();
                                    updateOverlay();
                                }
                            }

                            if (btn) btn.addEventListener('click', togglePlay);
                            v.addEventListener('click', togglePlay);

                            // Auto play attempt with user interaction fallback for strict mobile browser policies
                            var initPlay = function() {
                                v.muted = true;
                                v.defaultMuted = true;
                                var p = v.play();
                                if (p !== undefined) {
                                    p.then(updateOverlay).catch(function() {
                                        updateOverlay();
                                        // If mobile browser policy blocks autoplay, unlock on first user gesture
                                        var unlock = function() {
                                            v.muted = true;
                                            v.play().then(updateOverlay).catch(updateOverlay);
                                            window.removeEventListener('touchend', unlock);
                                            window.removeEventListener('click', unlock);
                                        };
                                        window.addEventListener('touchend', unlock, { once: true });
                                        window.addEventListener('click', unlock, { once: true });
                                    });
                                } else {
                                    updateOverlay();
                                }
                            };

                            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                                initPlay();
                            } else {
                                document.addEventListener('DOMContentLoaded', initPlay);
                            }
                        })();
                    </script>
                </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Glowing Premium AI Tools Crystal Card -->
    <div style="margin-bottom: 32px;">
        @include('tools.partials.crystal-card')
    </div>

    <!-- 2. Creative Tools Section -->
    <section id="creative-tools" class="studio-section">
        <div class="studio-section-header">
            <div class="studio-pill-badge">
                <i data-lucide="layers" style="width: 14px; height: 14px;"></i>
                <span class="studio-pill-text">CREATIVE SUITE</span>
            </div>
            <h2 class="studio-section-title">Intelligent Generation Tools</h2>
            <p class="studio-section-desc">
                Specialized neural generation models fine-tuned for professional visual storytelling and creative production.
            </p>
        </div>

        <div class="studio-tools-grid">
            <!-- Tool 1: Text to Image Generator -->
            <div class="studio-tool-card">
                <div class="tool-card-glow glow-cyan"></div>
                <div class="tool-card-header">
                    <div class="tool-card-icon-wrap icon-cyan">
                        <i data-lucide="image" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div class="tool-card-badge">Wan 2.2 Cinematic</div>
                </div>

                <div class="tool-card-body">
                    <h3 class="tool-card-title">Text-to-Image Generator</h3>
                    <p class="tool-card-desc">
                        Synthesize stunning, photorealistic 2MP cinematic imagery from text descriptions with nuanced lighting, rich textural depth, and precise composition control.
                    </p>

                    <div class="tool-specs-list">
                        <span class="spec-pill">2MP Ultra-HD</span>
                        <span class="spec-pill">1:1, 16:9, 9:16, 4:3, 3:4</span>
                        <span class="spec-pill">JPG, PNG, WEBP</span>
                        <span class="spec-pill">Juiced Engine</span>
                    </div>
                </div>

                <div class="tool-card-footer">
                    <a href="{{ route('tools.image.index') }}" class="btn-tool-launch">
                        <span>Open Image Generator</span>
                        <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                    </a>
                </div>
            </div>

            <!-- Tool 2: Text to Video Generator -->
            <div class="studio-tool-card">
                <div class="tool-card-glow glow-purple"></div>
                <div class="tool-card-header">
                    <div class="tool-card-icon-wrap icon-purple">
                        <i data-lucide="video" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div class="tool-card-badge">Hunyuan-Video</div>
                </div>

                <div class="tool-card-body">
                    <h3 class="tool-card-title">Text-to-Video Generator</h3>
                    <p class="tool-card-desc">
                        Generate fluid, temporal-consistent cinematic motion video sequences from text prompts with lifelike physical motion and professional camera dynamics.
                    </p>

                    <div class="tool-specs-list">
                        <span class="spec-pill">24 FPS Motion</span>
                        <span class="spec-pill">Temporal Diffusion</span>
                        <span class="spec-pill">Custom Steps</span>
                        <span class="spec-pill">MP4 Direct Export</span>
                    </div>
                </div>

                <div class="tool-card-footer">
                    <a href="{{ route('tools.video.index') }}" class="btn-tool-launch">
                        <span>Open Video Generator</span>
                        <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                    </a>
                </div>
            </div>

            <!-- Tool 3: Image to Video Generator -->
            <div class="studio-tool-card">
                <div class="tool-card-glow glow-pink" style="background: #ec4899;"></div>
                <div class="tool-card-header">
                    <div class="tool-card-icon-wrap" style="background: rgba(236, 72, 153, 0.12); border: 1px solid rgba(236, 72, 153, 0.3); color: #f472b6;">
                        <i data-lucide="clapperboard" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div class="tool-card-badge">Wan 2.2 I2V</div>
                </div>

                <div class="tool-card-body">
                    <h3 class="tool-card-title">Image-to-Video Generator</h3>
                    <p class="tool-card-desc">
                        Bring static pictures to life. Upload source imagery to Cloudflare R2 and synthesize seamless motion videos matching your narrative.
                    </p>

                    <div class="tool-specs-list">
                        <span class="spec-pill">720p & 480p</span>
                        <span class="spec-pill">16:9 & 9:16</span>
                        <span class="spec-pill">Cloudflare R2 Storage</span>
                        <span class="spec-pill">24 FPS Cinema</span>
                    </div>
                </div>

                <div class="tool-card-footer">
                    <a href="{{ route('tools.image-to-video.index') }}" class="btn-tool-launch">
                        <span>Open Image to Video</span>
                        <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Platform Introduction / About Pixora -->
    <section class="studio-section">
        <div class="studio-about-panel">
            <div class="about-ambient-glow"></div>

            <div class="about-content-header">
                <div class="studio-pill-badge">
                    <i data-lucide="shield-check" style="width: 14px; height: 14px;"></i>
                    <span class="studio-pill-text">PLATFORM ARCHITECTURE</span>
                </div>
                <h2 class="studio-section-title">Built for Professional AI Creators</h2>
                <p class="studio-section-desc">
                    Pixora unites cutting-edge diffusion synthesis with a friction-free studio workflow. Everything you need to conceptualize, render, and manage digital creations.
                </p>
            </div>

            <div class="about-pillars-grid">
                <div class="pillar-card">
                    <div class="pillar-icon">
                        <i data-lucide="zap" style="width: 20px; height: 20px; color: var(--brand-cyan);"></i>
                    </div>
                    <h4 class="pillar-title">Sub-Second Dispatch</h4>
                    <p class="pillar-desc">
                        Instant queue orchestration communicates directly with high-throughput GPU clusters for rapid turnaround.
                    </p>
                </div>

                <div class="pillar-card">
                    <div class="pillar-icon">
                        <i data-lucide="sparkles" style="width: 20px; height: 20px; color: #c084fc;"></i>
                    </div>
                    <h4 class="pillar-title">Photorealistic Detail</h4>
                    <p class="pillar-desc">
                        Wan 2.2 and Hunyuan neural models ensure fine micro-textures, authentic lighting, and coherent geometry.
                    </p>
                </div>

                <div class="pillar-card">
                    <div class="pillar-icon">
                        <i data-lucide="folder-git-2" style="width: 20px; height: 20px; color: #f472b6;"></i>
                    </div>
                    <h4 class="pillar-title">Integrated Media Library</h4>
                    <p class="pillar-desc">
                        Every generation is automatically preserved in your private media library with one-click export and remixing.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. Visual Identity & Neural Engine Link -->
    <section class="studio-section">
        <div class="neural-identity-card">
            <div class="identity-mesh-backdrop"></div>
            <div class="identity-content">
                <div class="identity-text">
                    <div class="studio-pill-badge" style="margin-bottom: 12px;">
                        <span class="studio-pulse-dot" style="background: #ec4899;"></span>
                        <span class="studio-pill-text">NEURAL PULSE ENGINE</span>
                    </div>
                    <h3 class="identity-title">Living AI Diffusion Synthesis</h3>
                    <p class="identity-desc">
                        Experience generative AI through real-time procedural visualizations. Our integrated neural pulse monitors synaptic compute passes live during generation.
                    </p>
                </div>

                <div class="identity-actions">
                    <a href="{{ route('tools.image.index') }}" class="btn-studio-primary">
                        <i data-lucide="play" style="width: 16px; height: 16px;"></i>
                        <span>Launch Image Studio</span>
                    </a>
                    <a href="{{ route('tools.video.index') }}" class="btn-studio-secondary">
                        <i data-lucide="film" style="width: 16px; height: 16px;"></i>
                        <span>Launch Video Studio</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
