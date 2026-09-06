// ==========================================
// Neural Pulse Procedural Animation Engine
// ==========================================
class NeuralPulseAnimation {
    constructor(canvasId) {
        this.canvasId = canvasId;
        this.canvas = null;
        this.ctx = null;
        this.mode = 'idle'; // 'idle' or 'generating'
        this.nodes = [];
        this.pulses = [];
        this.animationFrameId = null;
        this.width = 0;
        this.height = 0;
        this.dpr = window.devicePixelRatio || 1;
        this.resizeObserver = null;
        this.coreAngle = 0;
        this.corePulse = 0;
        this.init();
    }

    init() {
        this.canvas = document.getElementById(this.canvasId);
        if (!this.canvas) return;
        this.ctx = this.canvas.getContext('2d');
        this.resize();

        if (window.ResizeObserver && this.canvas.parentElement) {
            this.resizeObserver = new ResizeObserver(() => this.resize());
            this.resizeObserver.observe(this.canvas.parentElement);
        } else {
            window.addEventListener('resize', () => this.resize());
        }

        this.createNodes();
        this.start();
    }

    resize() {
        if (!this.canvas) return;
        const rect = this.canvas.parentElement ? this.canvas.parentElement.getBoundingClientRect() : this.canvas.getBoundingClientRect();
        this.width = Math.max(280, Math.floor(rect.width || 560));
        this.height = Math.max(360, Math.floor(rect.height || 520));
        this.dpr = Math.min(window.devicePixelRatio || 1, 2);

        this.canvas.width = this.width * this.dpr;
        this.canvas.height = this.height * this.dpr;
        this.canvas.style.width = `${this.width}px`;
        this.canvas.style.height = `${this.height}px`;

        if (this.ctx) {
            this.ctx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);
        }

        this.createNodes();
    }

    createNodes() {
        this.nodes = [];
        this.pulses = [];
        const count = Math.max(20, Math.min(38, Math.floor(this.width / 24)));
        const colors = ['#6366f1', '#8b5cf6', '#06b6d4', '#a855f7', '#38bdf8'];

        for (let i = 0; i < count; i++) {
            this.nodes.push({
                x: Math.random() * this.width,
                y: Math.random() * this.height,
                vx: (Math.random() - 0.5) * 0.4,
                vy: (Math.random() - 0.5) * 0.4,
                radius: 1.5 + Math.random() * 2.0,
                baseAlpha: 0.3 + Math.random() * 0.4,
                alpha: 0.5,
                color: colors[Math.floor(Math.random() * colors.length)],
                glow: 0,
                pulsePhase: Math.random() * Math.PI * 2
            });
        }
    }

    setMode(mode) {
        this.mode = mode; // 'idle' or 'generating'
    }

    start() {
        if (this.animationFrameId) return;
        const animate = () => {
            this.render();
            this.animationFrameId = requestAnimationFrame(animate);
        };
        this.animationFrameId = requestAnimationFrame(animate);
    }

    stop() {
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
            this.animationFrameId = null;
        }
    }

    destroy() {
        this.stop();
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
            this.resizeObserver = null;
        }
    }

    render() {
        if (!this.ctx || this.width === 0) return;
        const ctx = this.ctx;
        const speedMult = this.mode === 'generating' ? 1.7 : 0.75;
        const maxDist = Math.min(130, this.width * 0.28);

        ctx.clearRect(0, 0, this.width, this.height);

        // Ambient radial background glow
        const grad = ctx.createRadialGradient(this.width / 2, this.height / 2, 10, this.width / 2, this.height / 2, this.width * 0.55);
        if (this.mode === 'generating') {
            grad.addColorStop(0, 'rgba(99, 102, 241, 0.16)');
            grad.addColorStop(0.5, 'rgba(6, 182, 212, 0.07)');
            grad.addColorStop(1, 'rgba(9, 12, 20, 0)');
        } else {
            grad.addColorStop(0, 'rgba(99, 102, 241, 0.08)');
            grad.addColorStop(0.6, 'rgba(139, 92, 246, 0.02)');
            grad.addColorStop(1, 'rgba(9, 12, 20, 0)');
        }
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, this.width, this.height);

        // Draw Central AI Visual Core
        this.drawCenterCore(ctx);

        // Update Nodes
        for (let i = 0; i < this.nodes.length; i++) {
            const node = this.nodes[i];
            node.x += node.vx * speedMult;
            node.y += node.vy * speedMult;

            if (node.x < 12) { node.x = 12; node.vx *= -1; }
            if (node.x > this.width - 12) { node.x = this.width - 12; node.vx *= -1; }
            if (node.y < 12) { node.y = 12; node.vy *= -1; }
            if (node.y > this.height - 12) { node.y = this.height - 12; node.vy *= -1; }

            node.pulsePhase += 0.022 * speedMult;
            node.alpha = node.baseAlpha + Math.sin(node.pulsePhase) * 0.18;
            if (node.glow > 0) {
                node.glow = Math.max(0, node.glow - 0.03);
            }
        }

        // Draw Connections
        const activeConnections = [];
        ctx.lineWidth = 1.0;

        for (let i = 0; i < this.nodes.length; i++) {
            for (let j = i + 1; j < this.nodes.length; j++) {
                const n1 = this.nodes[i];
                const n2 = this.nodes[j];
                const dx = n2.x - n1.x;
                const dy = n2.y - n1.y;
                const dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < maxDist) {
                    activeConnections.push({ n1, n2, dist });
                    const lineAlpha = (1 - dist / maxDist) * (this.mode === 'generating' ? 0.32 : 0.15);
                    ctx.strokeStyle = `rgba(139, 92, 246, ${lineAlpha})`;
                    ctx.beginPath();
                    ctx.moveTo(n1.x, n1.y);
                    ctx.lineTo(n2.x, n2.y);
                    ctx.stroke();
                }
            }
        }

        // Spawn Traveling Synapse Pulses
        const maxPulses = this.mode === 'generating' ? 12 : 4;
        const spawnChance = this.mode === 'generating' ? 0.08 : 0.02;

        if (this.pulses.length < maxPulses && activeConnections.length > 0 && Math.random() < spawnChance) {
            const conn = activeConnections[Math.floor(Math.random() * activeConnections.length)];
            const reverse = Math.random() > 0.5;
            this.pulses.push({
                from: reverse ? conn.n2 : conn.n1,
                to: reverse ? conn.n1 : conn.n2,
                progress: 0,
                speed: (0.012 + Math.random() * 0.014) * (this.mode === 'generating' ? 1.5 : 1.0),
                color: this.mode === 'generating' ? '#06b6d4' : '#c084fc',
                size: 2.0 + Math.random() * 1.4
            });
        }

        // Update & Draw Pulses
        for (let p = this.pulses.length - 1; p >= 0; p--) {
            const pulse = this.pulses[p];
            pulse.progress += pulse.speed;

            if (pulse.progress >= 1) {
                pulse.to.glow = 1.0;
                this.pulses.splice(p, 1);
                continue;
            }

            const curX = pulse.from.x + (pulse.to.x - pulse.from.x) * pulse.progress;
            const curY = pulse.from.y + (pulse.to.y - pulse.from.y) * pulse.progress;

            ctx.fillStyle = pulse.color;
            ctx.shadowColor = pulse.color;
            ctx.shadowBlur = this.mode === 'generating' ? 10 : 5;
            ctx.beginPath();
            ctx.arc(curX, curY, pulse.size, 0, Math.PI * 2);
            ctx.fill();
            ctx.shadowBlur = 0;
        }

        // Draw Nodes
        for (let i = 0; i < this.nodes.length; i++) {
            const node = this.nodes[i];
            const finalRadius = node.radius + (node.glow * 2.2);

            ctx.fillStyle = node.color;
            ctx.globalAlpha = Math.min(1.0, node.alpha + node.glow * 0.6);

            if (node.glow > 0.2 || this.mode === 'generating') {
                ctx.shadowColor = node.color;
                ctx.shadowBlur = 6 + (node.glow * 8);
            }

            ctx.beginPath();
            ctx.arc(node.x, node.y, finalRadius, 0, Math.PI * 2);
            ctx.fill();

            ctx.shadowBlur = 0;
            ctx.globalAlpha = 1.0;
        }
    }

    drawCenterCore(ctx) {
        const cx = this.width / 2;
        const cy = this.height / 2;
        this.coreAngle += this.mode === 'generating' ? 0.022 : 0.007;
        this.corePulse += this.mode === 'generating' ? 0.045 : 0.018;

        const pulseScale = 1 + Math.sin(this.corePulse) * (this.mode === 'generating' ? 0.1 : 0.04);

        ctx.save();
        ctx.translate(cx, cy);

        // Outer rotating dashed aura ring
        ctx.rotate(this.coreAngle);
        ctx.strokeStyle = this.mode === 'generating' ? 'rgba(6, 182, 212, 0.4)' : 'rgba(99, 102, 241, 0.2)';
        ctx.lineWidth = 1.4;
        ctx.setLineDash([5, 5]);
        ctx.beginPath();
        ctx.arc(0, 0, 48 * pulseScale, 0, Math.PI * 2);
        ctx.stroke();
        ctx.setLineDash([]);

        // Counter-rotating inner ring
        ctx.rotate(-this.coreAngle * 1.7);
        ctx.strokeStyle = this.mode === 'generating' ? 'rgba(168, 85, 247, 0.5)' : 'rgba(139, 92, 246, 0.18)';
        ctx.beginPath();
        ctx.arc(0, 0, 36 * pulseScale, 0, Math.PI * 2);
        ctx.stroke();

        ctx.restore();

        // Glowing center orb
        ctx.save();
        ctx.translate(cx, cy);
        const orbGrad = ctx.createRadialGradient(0, 0, 2, 0, 0, 28 * pulseScale);
        if (this.mode === 'generating') {
            orbGrad.addColorStop(0, 'rgba(6, 182, 212, 0.8)');
            orbGrad.addColorStop(0.45, 'rgba(99, 102, 241, 0.35)');
            orbGrad.addColorStop(1, 'rgba(99, 102, 241, 0)');
        } else {
            orbGrad.addColorStop(0, 'rgba(99, 102, 241, 0.45)');
            orbGrad.addColorStop(0.5, 'rgba(139, 92, 246, 0.12)');
            orbGrad.addColorStop(1, 'rgba(99, 102, 241, 0)');
        }
        ctx.fillStyle = orbGrad;
        ctx.beginPath();
        ctx.arc(0, 0, 28 * pulseScale, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
    }
}
window.NeuralPulseAnimation = NeuralPulseAnimation;

// ==========================================
// 1. Text-to-Image Generator Store
// ==========================================
function imageGeneratorApp(config = {}) {
    return {
        // Config & Auth State
        supabaseUrl: config.supabaseUrl || '',
        supabaseAnonKey: config.supabaseAnonKey || '',
        supabase: null,
        user: config.currentUser || null,
        authModalOpen: false,
        authTab: 'signin',
        authLoading: false,
        authEmail: '',
        authPassword: '',
        authFullName: '',
        authMessage: null,

        // Generator Parameters
        prompt: '',
        aspectRatio: '1:1',
        megapixels: 2,
        outputFormat: 'jpg',
        outputQuality: 80,
        seed: null,
        juiced: false,
        advancedOpen: false,

        // Generation State
        isGenerating: false,
        generationStatus: 'idle', // idle, starting, processing, succeeded, failed, cancelled
        statusMessage: '',
        elapsedSeconds: 0,
        timerInterval: null,
        pollInterval: null,
        currentPollGenerationId: null,
        currentGeneration: null,
        historyList: config.initialGenerations || [],
        
        // Neural Pulse & Dynamic Status Messages
        neuralPulse: null,
        dynamicMessages: [
            "Initializing neural network...",
            "Analyzing your prompt...",
            "Building visual composition...",
            "Generating details...",
            "Rendering your creation...",
            "Applying final details..."
        ],
        dynamicStatusMessage: "Initializing neural network...",
        messageIndex: 0,
        messageInterval: null,

        // Modal & Lightbox
        lightboxOpen: false,
        lightboxImage: null,

        // Sample Prompts
        samplePrompts: [
            "A cinematic, photorealistic medium shot capturing the nostalgic warmth of a mid-2000s indie film. Messy platinum hair, golden hour rim lighting, 35mm film grain.",
            "Futuristic neon-lit Tokyo cyberpunk alley at night, reflections in rain puddles, volumetric teal and magenta fog, cinematic 8k resolution.",
            "Surreal architectural villa nestled on a floating Icelandic volcanic rock above clouds, brutalist minimalist glass architecture, ultra-detailed photorealism.",
            "Commercial high-fashion product photography of a luxury perfume bottle on black obsidian stone with liquid splashes and warm amber studio illumination."
        ],

        // Initialization
        init() {
            this.initSupabase();

            // Clean Initial State: always open in clean idle state on page load/refresh
            this.currentGeneration = null;
            this.isGenerating = false;
            this.generationStatus = 'idle';

            // Restore pending action after post-login page reload
            const savedPending = sessionStorage.getItem('pendingAction');
            if (savedPending && this.user) {
                sessionStorage.removeItem('pendingAction');
                if (savedPending === 'generate') {
                    this.$nextTick(() => this.showToast('You are signed in! Enter a prompt and click Generate.', 'success'));
                }
            }

            // Check if redirected with auth required flag
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auth') === 'required' || urlParams.get('auth') === '1') {
                this.authModalOpen = true;
                this.showToast('Please sign in or create an account to use the creative tools.', 'info');
                window.history.replaceState({}, '', window.location.pathname);
            }

            this.initNeuralCanvas('image-neural-canvas');
        },

        initNeuralCanvas(canvasId) {
            this.$nextTick(() => {
                if (window.NeuralPulseAnimation) {
                    this.neuralPulse = new window.NeuralPulseAnimation(canvasId);
                    if (this.isGenerating) {
                        this.neuralPulse.setMode('generating');
                    }
                }
            });
        },

        startMessageCycle() {
            this.messageIndex = 0;
            this.dynamicStatusMessage = this.dynamicMessages[0];
            if (this.messageInterval) clearInterval(this.messageInterval);
            this.messageInterval = setInterval(() => {
                this.messageIndex = (this.messageIndex + 1) % this.dynamicMessages.length;
                this.dynamicStatusMessage = this.dynamicMessages[this.messageIndex];
            }, 2600);
        },

        stopMessageCycle() {
            if (this.messageInterval) {
                clearInterval(this.messageInterval);
                this.messageInterval = null;
            }
        },

        initSupabase() {
            if (window.supabase && this.supabaseUrl && this.supabaseAnonKey) {
                try {
                    this.supabase = window.supabase.createClient(this.supabaseUrl, this.supabaseAnonKey);

                    this.supabase.auth.onAuthStateChange(async (event, session) => {
                        // INITIAL_SESSION fires on every page load.
                        // If the server already provided currentUser via Laravel session,
                        // there is nothing to sync — skip to avoid triggering the login loop.
                        if (event === 'INITIAL_SESSION') {
                            if (!this.user && session && session.user) {
                                // Server session is missing but Supabase has a valid session:
                                // sync once so Laravel knows about this user.
                                await this.syncSessionWithServer(session);
                            }
                            return;
                        }

                        if ((event === 'SIGNED_IN' || event === 'TOKEN_REFRESHED') && session && session.user) {
                            await this.syncSessionWithServer(session);
                        } else if (event === 'SIGNED_OUT') {
                            this.user = null;
                        }
                    });
                } catch (e) {
                    console.warn('Supabase initialization error:', e);
                }
            }
        },

        // Reset generator tool controls to initial defaults (ONLY after successful generation)
        resetToolState() {
            this.prompt = '';
            this.aspectRatio = '1:1';
            this.megapixels = 2;
            this.outputFormat = 'jpg';
            this.outputQuality = 80;
            this.seed = null;
            this.juiced = false;
            this.advancedOpen = false;
            this.elapsedSeconds = 0;
            this.statusMessage = '';
        },

        useSamplePrompt(text) {
            if (this.isGenerating) return;
            this.prompt = text;
            this.showToast('Sample prompt applied', 'success');
        },

        randomizeSeed() {
            if (this.isGenerating) return;
            this.seed = Math.floor(Math.random() * 999999);
            this.showToast(`Seed randomized: ${this.seed}`, 'info');
        },

        clearSeed() {
            if (this.isGenerating) return;
            this.seed = null;
        },

        async startGeneration() {
            if (this.isGenerating) {
                this.showToast('A generation is already in progress.', 'info');
                return;
            }

            // Auth Check: Unauthenticated users must log in / register first
            if (!this.user || !this.user.id) {
                this.showToast('Please sign in or create an account to generate images.', 'warning');
                this.pendingAction = 'generate';
                this.authModalOpen = true;
                return;
            }

            if (!this.prompt || this.prompt.trim().length < 2) {
                this.showToast('Please enter a descriptive prompt first.', 'error');
                return;
            }

            this.stopAllTimers();

            this.isGenerating = true;
            this.generationStatus = 'starting';
            this.statusMessage = 'Submitting prompt to Wan 2.2 Cinematic Model...';
            this.elapsedSeconds = 0;
            this.startMessageCycle();
            if (this.neuralPulse) this.neuralPulse.setMode('generating');

            this.timerInterval = setInterval(() => {
                this.elapsedSeconds++;
            }, 1000);

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                const response = await fetch('/tools/image-generator/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({
                        prompt: this.prompt,
                        aspect_ratio: this.aspectRatio,
                        megapixels: parseInt(this.megapixels),
                        output_format: this.outputFormat,
                        output_quality: parseInt(this.outputQuality),
                        seed: this.seed ? parseInt(this.seed) : null,
                        juiced: this.juiced,
                    })
                });

                const data = await response.json();

                if (response.status === 401 || (data && data.requires_auth)) {
                    this.user = null;
                    this.authModalOpen = true;
                    this.pendingAction = 'generate';
                    this.isGenerating = false;
                    this.generationStatus = 'idle';
                    this.stopAllTimers();
                    this.stopMessageCycle();
                    if (this.neuralPulse) this.neuralPulse.setMode('idle');
                    this.showToast(data.message || data.error || 'Please sign in to generate images.', 'warning');
                    return;
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.message || data.error || 'Failed to start generation.');
                }

                const generation = data.generation;
                this.currentGeneration = generation;
                this.statusMessage = 'AI model queued. Synthesizing pixels...';
                this.generationStatus = generation.status || 'starting';

                if (!this.historyList.some(item => item.id === generation.id)) {
                    this.historyList.unshift(generation);
                }

                this.pollGenerationStatus(generation.id);

            } catch (err) {
                console.error('Generation Error:', err);
                this.generationStatus = 'failed';
                this.isGenerating = false;
                this.stopAllTimers();
                this.stopMessageCycle();
                if (this.neuralPulse) this.neuralPulse.setMode('idle');
                this.showToast(err.message || 'Generation failed. Please try again.', 'error');
            }
        },

        async stopGeneration() {
            if (!this.isGenerating || !this.currentGeneration) {
                return;
            }

            const genId = this.currentGeneration.id;

            this.stopAllTimers();
            this.stopMessageCycle();
            if (this.neuralPulse) this.neuralPulse.setMode('idle');
            this.isGenerating = false;
            this.generationStatus = 'cancelled';
            this.statusMessage = 'Generation cancelled.';

            if (this.currentGeneration) {
                this.currentGeneration.status = 'cancelled';
                this.currentGeneration.error_message = 'Generation was stopped by user.';
            }

            const hIdx = this.historyList.findIndex(item => item.id === genId);
            if (hIdx !== -1) {
                this.historyList[hIdx].status = 'cancelled';
                this.historyList[hIdx].error_message = 'Generation was stopped by user.';
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/tools/image-generator/cancel/${genId}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    }
                });

                const data = await res.json();
                if (data.success && data.generation) {
                    this.currentGeneration = data.generation;
                    const idx = this.historyList.findIndex(item => item.id === genId);
                    if (idx !== -1) {
                        this.historyList[idx] = data.generation;
                    }
                }
                this.showToast('Generation cancelled.', 'info');
            } catch (e) {
                console.warn('Cancellation notice:', e);
                this.showToast('Generation stopped locally.', 'info');
            }
        },

        resumePendingGeneration(generation) {
            this.isGenerating = true;
            this.generationStatus = generation.status;
            this.statusMessage = 'Resuming image generation status check...';
            this.elapsedSeconds = 5;
            this.startMessageCycle();
            if (this.neuralPulse) this.neuralPulse.setMode('generating');

            this.stopAllTimers();
            this.timerInterval = setInterval(() => {
                this.elapsedSeconds++;
            }, 1000);

            this.pollGenerationStatus(generation.id);
        },

        stopAllTimers() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
            this.currentPollGenerationId = null;
        },

        pollGenerationStatus(generationId) {
            this.currentPollGenerationId = generationId;
            const MAX_TIMEOUT_SEC = 600;
            const startTime = Date.now();
            let isPollingRequestInFlight = false;

            const performPoll = async () => {
                if (this.currentPollGenerationId !== generationId) {
                    if (this.pollInterval) {
                        clearInterval(this.pollInterval);
                        this.pollInterval = null;
                    }
                    return;
                }

                const elapsed = (Date.now() - startTime) / 1000;

                if (elapsed > MAX_TIMEOUT_SEC) {
                    this.stopAllTimers();
                    this.stopMessageCycle();
                    if (this.neuralPulse) this.neuralPulse.setMode('idle');
                    this.isGenerating = false;
                    this.generationStatus = 'failed';
                    this.showToast('Generation timed out after 10 minutes. Please try again.', 'error');
                    return;
                }

                if (isPollingRequestInFlight) {
                    return;
                }

                isPollingRequestInFlight = true;

                try {
                    const res = await fetch(`/tools/image-generator/status/${generationId}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const result = await res.json();

                    if (result.success && result.generation) {
                        const gen = result.generation;

                        if (this.currentPollGenerationId !== generationId || this.generationStatus === 'cancelled') {
                            return;
                        }

                        this.currentGeneration = gen;
                        this.generationStatus = gen.status;

                        const idx = this.historyList.findIndex(item => item.id === gen.id);
                        if (idx !== -1) {
                            this.historyList[idx] = gen;
                        }

                        if (gen.status === 'succeeded' && gen.image_url) {
                            this.stopAllTimers();
                            this.stopMessageCycle();
                            if (this.neuralPulse) this.neuralPulse.setMode('idle');
                            this.isGenerating = false;
                            this.generationStatus = 'succeeded';
                            this.resetToolState();
                            this.showToast('Cinematic image generated successfully!', 'success');
                        } else if (gen.status === 'failed') {
                            this.stopAllTimers();
                            this.stopMessageCycle();
                            if (this.neuralPulse) this.neuralPulse.setMode('idle');
                            this.isGenerating = false;
                            this.generationStatus = 'failed';
                            this.showToast(gen.error_message || 'Image generation failed on provider.', 'error');
                        } else if (gen.status === 'cancelled') {
                            this.stopAllTimers();
                            this.stopMessageCycle();
                            if (this.neuralPulse) this.neuralPulse.setMode('idle');
                            this.isGenerating = false;
                            this.generationStatus = 'cancelled';
                            this.showToast('Generation was cancelled.', 'info');
                        } else {
                            if (gen.status === 'starting') {
                                this.statusMessage = 'Allocating GPU compute node on Wan 2.2 cluster...';
                            } else if (gen.status === 'processing') {
                                if (this.elapsedSeconds > 10) {
                                    this.statusMessage = 'Performing high-resolution diffusion passes...';
                                } else {
                                    this.statusMessage = 'Rendering cinematic 2MP resolution...';
                                }
                            }
                        }
                    }
                } catch (e) {
                    console.warn('Polling network notice:', e);
                } finally {
                    isPollingRequestInFlight = false;
                }
            };

            this.pollInterval = setInterval(performPoll, 5000);
            
            setTimeout(() => {
                if (this.currentPollGenerationId === generationId && this.isGenerating) {
                    performPoll();
                }
            }, 3000);
        },

        selectGeneration(item) {
            this.currentGeneration = item;
            if (['starting', 'processing'].includes(item.status)) {
                this.resumePendingGeneration(item);
            } else {
                this.isGenerating = false;
                this.stopAllTimers();
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        remixPrompt(item) {
            if (this.isGenerating) return;
            this.prompt = item.prompt;
            this.aspectRatio = item.aspect_ratio || '1:1';
            this.outputFormat = item.output_format || 'jpg';
            this.megapixels = item.megapixels || 2;
            if (item.seed) this.seed = item.seed;
            this.showToast('Loaded settings from generation into editor', 'info');
        },

        copyPrompt(text) {
            navigator.clipboard.writeText(text);
            this.showToast('Prompt copied to clipboard!', 'success');
        },

        shareGeneration(gen) {
            const url = window.location.origin + '/tools';
            if (navigator.share) {
                navigator.share({
                    title: 'AI Generated Image',
                    text: gen.prompt,
                    url: url
                }).catch(() => {});
            } else {
                navigator.clipboard.writeText(gen.image_url || url);
                this.showToast('Image link copied to clipboard!', 'success');
            }
        },

        async deleteGeneration(id) {
            if (!confirm('Are you sure you want to delete this generated image?')) return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/tools/image-generator/delete/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    }
                });

                const data = await res.json();
                if (data.success) {
                    this.historyList = this.historyList.filter(item => item.id !== id);
                    if (this.currentGeneration && this.currentGeneration.id === id) {
                        this.currentGeneration = this.historyList[0] || null;
                    }
                    this.showToast('Image deleted successfully', 'info');
                }
            } catch (err) {
                this.showToast('Failed to delete image.', 'error');
            }
        },

        openLightbox(imageUrl) {
            if (!imageUrl) return;
            this.lightboxImage = imageUrl;
            this.lightboxOpen = true;
        },

        closeLightbox() {
            this.lightboxOpen = false;
            this.lightboxImage = null;
        },

        // Supabase Auth Methods
        async handleEmailAuth() {
            if (!this.supabase) {
                this.showToast('Authentication service is running in local mode.', 'info');
                this.authModalOpen = false;
                return;
            }

            this.authLoading = true;
            this.authMessage = null;

            try {
                if (this.authTab === 'signin') {
                    const { data, error } = await this.supabase.auth.signInWithPassword({
                        email: this.authEmail,
                        password: this.authPassword,
                    });
                    if (error) throw error;
                    this.showToast('Signed in successfully!', 'success');
                    this.authModalOpen = false;
                } else if (this.authTab === 'signup') {
                    const { data, error } = await this.supabase.auth.signUp({
                        email: this.authEmail,
                        password: this.authPassword,
                        options: {
                            data: { full_name: this.authFullName }
                        }
                    });
                    if (error) throw error;
                    this.showToast('Account created! Check your email for verification.', 'success');
                    this.authModalOpen = false;
                } else if (this.authTab === 'forgot') {
                    const { data, error } = await this.supabase.auth.resetPasswordForEmail(this.authEmail);
                    if (error) throw error;
                    this.showToast('Password reset link sent to your email.', 'success');
                    this.authTab = 'signin';
                }
            } catch (err) {
                this.authMessage = err.message;
            } finally {
                this.authLoading = false;
            }
        },

        async handleGoogleAuth() {
            if (!this.supabase) {
                this.showToast('Google OAuth is available when Supabase is configured.', 'info');
                return;
            }

            try {
                const { data, error } = await this.supabase.auth.signInWithOAuth({
                    provider: 'google',
                    options: {
                        redirectTo: window.location.origin + '/tools/overview'
                    }
                });
                if (error) throw error;
            } catch (err) {
                this.showToast(err.message, 'error');
            }
        },

        async syncSessionWithServer(session) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/auth/sync-session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({
                        access_token: session.access_token,
                        user: session.user
                    })
                });

                const data = await res.json();
                if (data.success && data.user) {
                    const wasLoggedOut = !this.user;
                    this.user = data.user;
                    this.authModalOpen = false;

                    if (wasLoggedOut) {
                        // First time sign-in: reload so Laravel session is fully hydrated
                        // and server renders currentUser correctly, breaking the auth loop.
                        this.showToast('Signed in successfully! Loading your workspace...', 'success');
                        if (this.pendingAction === 'generate') {
                            // Store pending action in sessionStorage to resume after reload
                            sessionStorage.setItem('pendingAction', 'generate');
                        }
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        // Token refresh: just update user object silently
                        if (this.pendingAction === 'generate') {
                            this.pendingAction = null;
                            this.startGeneration();
                        }
                    }
                } else {
                    // Sync failed — do NOT clear this.user if it was already set from server
                    console.warn('Session sync response was not successful:', data);
                }
            } catch (e) {
                // Network / server error — keep existing user state, do not log out
                console.warn('Session sync error (non-fatal):', e);
            }
        },

        async handleLogout() {
            if (this.supabase) {
                await this.supabase.auth.signOut();
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            await fetch('/auth/logout', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            this.user = null;
            this.showToast('Signed out successfully', 'info');
        },

        showToast(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message, type }
            }));
        }
    };
}
window.imageGeneratorApp = imageGeneratorApp;

// ==========================================
// 2. Pure Text-to-Video Generator Store
// ==========================================
function videoGeneratorApp(config = {}) {
    return {
        // Config & Auth State
        supabaseUrl: config.supabaseUrl || '',
        supabaseAnonKey: config.supabaseAnonKey || '',
        supabase: null,
        user: config.currentUser || null,
        authModalOpen: false,
        authTab: 'signin',
        authLoading: false,
        authEmail: '',
        authPassword: '',
        authFullName: '',
        authMessage: null,

        // Video Generator Parameters
        prompt: '',
        aspectRatio: '16:9',
        frameRate: 24,
        steps: 30,
        denoiseStrength: 0.85,
        guidanceScale: 6.0,
        flowShift: 9,
        crf: 19,
        advancedOpen: false,

        // Video Generation State
        isGenerating: false,
        generationStatus: 'idle', // idle, starting, processing, succeeded, failed, cancelled
        statusMessage: '',
        elapsedSeconds: 0,
        timerInterval: null,
        pollInterval: null,
        currentPollGenerationId: null,
        currentGeneration: null,
        historyList: config.initialGenerations || [],

        // Neural Pulse & Dynamic Status Messages
        neuralPulse: null,
        dynamicMessages: [
            "Initializing neural network...",
            "Analyzing your prompt...",
            "Building visual composition...",
            "Generating details...",
            "Rendering your creation...",
            "Applying final details..."
        ],
        dynamicStatusMessage: "Initializing neural network...",
        messageIndex: 0,
        messageInterval: null,

        // Modal & Lightbox (for layout compatibility)
        lightboxOpen: false,
        lightboxImage: null,

        // Curated Cinematic Video Prompts
        sampleVideoPrompts: [
            "A breathtaking drone time-lapse sweeping over misty emerald fjords at sunrise, volumetric sunlight, 4k cinematic motion.",
            "Futuristic cyberpunk hover-car speeding down a rainy neon boulevard, volumetric headlights and water spray, 8k smooth motion.",
            "A realistic close-up of a majestic white tiger walking gracefully through snowy Siberian birch forest, slow motion 4k.",
            "Spectacular underwater cinematic footage of glowing bioluminescent jellyfish floating in deep ocean trenches at night."
        ],

        init() {
            this.initSupabase();

            // Clean Initial State: always open in clean idle state on page load/refresh
            this.currentGeneration = null;
            this.isGenerating = false;
            this.generationStatus = 'idle';

            // Check if redirected with auth required flag
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auth') === 'required' || urlParams.get('auth') === '1') {
                this.authModalOpen = true;
                this.showToast('Please sign in or create an account to use the creative tools.', 'info');
                window.history.replaceState({}, '', window.location.pathname);
            }

            this.initNeuralCanvas('video-neural-canvas');
        },

        initNeuralCanvas(canvasId) {
            this.$nextTick(() => {
                if (window.NeuralPulseAnimation) {
                    this.neuralPulse = new window.NeuralPulseAnimation(canvasId);
                    if (this.isGenerating) {
                        this.neuralPulse.setMode('generating');
                    }
                }
            });
        },

        startMessageCycle() {
            this.messageIndex = 0;
            this.dynamicStatusMessage = this.dynamicMessages[0];
            if (this.messageInterval) clearInterval(this.messageInterval);
            this.messageInterval = setInterval(() => {
                this.messageIndex = (this.messageIndex + 1) % this.dynamicMessages.length;
                this.dynamicStatusMessage = this.dynamicMessages[this.messageIndex];
            }, 2600);
        },

        stopMessageCycle() {
            if (this.messageInterval) {
                clearInterval(this.messageInterval);
                this.messageInterval = null;
            }
        },

        initSupabase() {
            if (window.supabase && this.supabaseUrl && this.supabaseAnonKey) {
                try {
                    this.supabase = window.supabase.createClient(this.supabaseUrl, this.supabaseAnonKey);

                    this.supabase.auth.onAuthStateChange(async (event, session) => {
                        // INITIAL_SESSION fires on every page load.
                        // If the server already provided currentUser via Laravel session,
                        // there is nothing to sync — skip to avoid triggering the login loop.
                        if (event === 'INITIAL_SESSION') {
                            if (!this.user && session && session.user) {
                                await this.syncSessionWithServer(session);
                            }
                            return;
                        }

                        if ((event === 'SIGNED_IN' || event === 'TOKEN_REFRESHED') && session && session.user) {
                            await this.syncSessionWithServer(session);
                        } else if (event === 'SIGNED_OUT') {
                            this.user = null;
                        }
                    });
                } catch (e) {
                    console.warn('Supabase initialization error:', e);
                }
            }
        },

        // Reset video tool controls ONLY on successful generation
        resetToolState() {
            this.prompt = '';
            this.aspectRatio = '16:9';
            this.frameRate = 24;
            this.steps = 30;
            this.denoiseStrength = 0.85;
            this.guidanceScale = 6.0;
            this.flowShift = 9;
            this.crf = 19;
            this.advancedOpen = false;
            this.elapsedSeconds = 0;
            this.statusMessage = '';
        },

        useSamplePrompt(text) {
            if (this.isGenerating) return;
            this.prompt = text;
            this.showToast('Video sample prompt applied', 'success');
        },

        async startGeneration() {
            if (this.isGenerating) {
                this.showToast('A video generation is already in progress.', 'info');
                return;
            }

            // Auth Check: Unauthenticated users must log in / register first
            if (!this.user || !this.user.id) {
                this.showToast('Please sign in or create an account to generate videos.', 'warning');
                this.pendingAction = 'generate';
                this.authModalOpen = true;
                return;
            }

            if (!this.prompt || this.prompt.trim().length < 3) {
                this.showToast('Please enter a descriptive video prompt (at least 3 characters).', 'error');
                return;
            }

            this.stopAllTimers();

            this.isGenerating = true;
            this.generationStatus = 'starting';
            this.statusMessage = 'Submitting prompt to Hunyuan-Video Engine...';
            this.elapsedSeconds = 0;
            this.startMessageCycle();
            if (this.neuralPulse) this.neuralPulse.setMode('generating');

            this.timerInterval = setInterval(() => {
                this.elapsedSeconds++;
            }, 1000);

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                const response = await fetch('/tools/video-generator/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({
                        prompt: this.prompt,
                        aspect_ratio: this.aspectRatio,
                        frame_rate: parseInt(this.frameRate),
                        steps: parseInt(this.steps),
                        denoise_strength: parseFloat(this.denoiseStrength),
                        guidance_scale: parseFloat(this.guidanceScale),
                        flow_shift: parseInt(this.flowShift),
                        crf: parseInt(this.crf),
                    })
                });

                const data = await response.json();

                if (response.status === 401 || (data && data.requires_auth)) {
                    this.user = null;
                    this.authModalOpen = true;
                    this.pendingAction = 'generate';
                    this.isGenerating = false;
                    this.generationStatus = 'idle';
                    this.stopAllTimers();
                    this.stopMessageCycle();
                    if (this.neuralPulse) this.neuralPulse.setMode('idle');
                    this.showToast(data.message || data.error || 'Please sign in to generate videos.', 'warning');
                    return;
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.message || data.error || 'Failed to start video generation.');
                }

                const generation = data.generation;
                this.currentGeneration = generation;
                this.statusMessage = 'Video synthesis queued. Rendering motion frames...';
                this.generationStatus = generation.status || 'starting';

                if (!this.historyList.some(item => item.id === generation.id)) {
                    this.historyList.unshift(generation);
                }

                this.pollGenerationStatus(generation.id);

            } catch (err) {
                console.error('Video Generation Error:', err);
                this.generationStatus = 'failed';
                this.isGenerating = false;
                this.stopAllTimers();
                this.stopMessageCycle();
                if (this.neuralPulse) this.neuralPulse.setMode('idle');
                this.showToast(err.message || 'Video generation failed. Please try again.', 'error');
            }
        },

        async stopGeneration() {
            if (!this.isGenerating || !this.currentGeneration) {
                return;
            }

            const genId = this.currentGeneration.id;

            this.stopAllTimers();
            this.stopMessageCycle();
            if (this.neuralPulse) this.neuralPulse.setMode('idle');
            this.isGenerating = false;
            this.generationStatus = 'cancelled';
            this.statusMessage = 'Video generation cancelled.';

            if (this.currentGeneration) {
                this.currentGeneration.status = 'cancelled';
                this.currentGeneration.error_message = 'Video generation was stopped by user.';
            }

            const hIdx = this.historyList.findIndex(item => item.id === genId);
            if (hIdx !== -1) {
                this.historyList[hIdx].status = 'cancelled';
                this.historyList[hIdx].error_message = 'Video generation was stopped by user.';
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/tools/video-generator/cancel/${genId}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    }
                });

                const data = await res.json();
                if (data.success && data.generation) {
                    this.currentGeneration = data.generation;
                    const idx = this.historyList.findIndex(item => item.id === genId);
                    if (idx !== -1) {
                        this.historyList[idx] = data.generation;
                    }
                }
                this.showToast('Video generation cancelled.', 'info');
            } catch (e) {
                console.warn('Video cancellation notice:', e);
                this.showToast('Video generation stopped locally.', 'info');
            }
        },

        resumePendingGeneration(generation) {
            this.isGenerating = true;
            this.generationStatus = generation.status;
            this.statusMessage = 'Resuming video rendering status check...';
            this.elapsedSeconds = 10;
            this.startMessageCycle();
            if (this.neuralPulse) this.neuralPulse.setMode('generating');

            this.stopAllTimers();
            this.timerInterval = setInterval(() => {
                this.elapsedSeconds++;
            }, 1000);

            this.pollGenerationStatus(generation.id);
        },

        stopAllTimers() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
            this.currentPollGenerationId = null;
        },

        pollGenerationStatus(generationId) {
            this.currentPollGenerationId = generationId;
            const MAX_TIMEOUT_SEC = 600;
            const startTime = Date.now();
            let isPollingRequestInFlight = false;

            const performPoll = async () => {
                if (this.currentPollGenerationId !== generationId) {
                    if (this.pollInterval) {
                        clearInterval(this.pollInterval);
                        this.pollInterval = null;
                    }
                    return;
                }

                const elapsed = (Date.now() - startTime) / 1000;

                if (elapsed > MAX_TIMEOUT_SEC) {
                    this.stopAllTimers();
                    this.stopMessageCycle();
                    if (this.neuralPulse) this.neuralPulse.setMode('idle');
                    this.isGenerating = false;
                    this.generationStatus = 'failed';
                    this.showToast('Video generation timed out after 10 minutes. Please try again.', 'error');
                    return;
                }

                if (isPollingRequestInFlight) {
                    return;
                }

                isPollingRequestInFlight = true;

                try {
                    const res = await fetch(`/tools/video-generator/status/${generationId}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const result = await res.json();

                    if (result.success && result.generation) {
                        const gen = result.generation;

                        if (this.currentPollGenerationId !== generationId || this.generationStatus === 'cancelled') {
                            return;
                        }

                        this.currentGeneration = gen;
                        this.generationStatus = gen.status;

                        const idx = this.historyList.findIndex(item => item.id === gen.id);
                        if (idx !== -1) {
                            this.historyList[idx] = gen;
                        }

                        if (gen.status === 'succeeded' && gen.video_url) {
                            this.stopAllTimers();
                            this.stopMessageCycle();
                            if (this.neuralPulse) this.neuralPulse.setMode('idle');
                            this.isGenerating = false;
                            this.generationStatus = 'succeeded';
                            this.resetToolState();
                            this.showToast('Cinematic AI video generated successfully!', 'success');
                        } else if (gen.status === 'failed') {
                            this.stopAllTimers();
                            this.stopMessageCycle();
                            if (this.neuralPulse) this.neuralPulse.setMode('idle');
                            this.isGenerating = false;
                            this.generationStatus = 'failed';
                            this.showToast(gen.error_message || 'Video generation failed on provider.', 'error');
                        } else if (gen.status === 'cancelled') {
                            this.stopAllTimers();
                            this.stopMessageCycle();
                            if (this.neuralPulse) this.neuralPulse.setMode('idle');
                            this.isGenerating = false;
                            this.generationStatus = 'cancelled';
                            this.showToast('Video generation was cancelled.', 'info');
                        } else {
                            if (gen.status === 'starting') {
                                this.statusMessage = 'Allocating Hunyuan-Video GPU compute node...';
                            } else if (gen.status === 'processing') {
                                if (this.elapsedSeconds > 25) {
                                    this.statusMessage = 'Encoding high-definition motion video frames...';
                                } else {
                                    this.statusMessage = 'Synthesizing temporal diffusion motion passes...';
                                }
                            }
                        }
                    }
                } catch (e) {
                    console.warn('Video polling network notice (retrying in 5s):', e);
                } finally {
                    isPollingRequestInFlight = false;
                }
            };

            this.pollInterval = setInterval(performPoll, 5000);
            
            setTimeout(() => {
                if (this.currentPollGenerationId === generationId && this.isGenerating) {
                    performPoll();
                }
            }, 3000);
        },

        selectGeneration(item) {
            this.currentGeneration = item;
            if (['starting', 'processing'].includes(item.status)) {
                this.resumePendingGeneration(item);
            } else {
                this.isGenerating = false;
                this.stopAllTimers();
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        remixPrompt(item) {
            if (this.isGenerating) return;
            this.prompt = item.prompt;
            this.aspectRatio = item.aspect_ratio || '16:9';
            this.frameRate = item.frame_rate || 24;
            this.steps = item.steps || 30;
            if (item.denoise_strength) this.denoiseStrength = item.denoise_strength;
            if (item.guidance_scale) this.guidanceScale = item.guidance_scale;
            this.showToast('Loaded settings from video into editor', 'info');
        },

        copyPrompt(text) {
            navigator.clipboard.writeText(text);
            this.showToast('Prompt copied to clipboard!', 'success');
        },

        shareGeneration(gen) {
            const url = window.location.origin + '/tools/video-generator';
            if (navigator.share) {
                navigator.share({
                    title: 'AI Generated Video',
                    text: gen.prompt,
                    url: url
                }).catch(() => {});
            } else {
                navigator.clipboard.writeText(gen.video_url || url);
                this.showToast('Video link copied to clipboard!', 'success');
            }
        },

        async deleteGeneration(id) {
            if (!confirm('Are you sure you want to delete this generated video?')) return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(`/tools/video-generator/delete/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    }
                });

                const data = await res.json();
                if (data.success) {
                    this.historyList = this.historyList.filter(item => item.id !== id);
                    if (this.currentGeneration && this.currentGeneration.id === id) {
                        this.currentGeneration = this.historyList[0] || null;
                    }
                    this.showToast('Video deleted successfully', 'info');
                }
            } catch (err) {
                this.showToast('Failed to delete video.', 'error');
            }
        },

        openLightbox(imageUrl) {
            if (!imageUrl) return;
            this.lightboxImage = imageUrl;
            this.lightboxOpen = true;
        },

        closeLightbox() {
            this.lightboxOpen = false;
            this.lightboxImage = null;
        },

        // Supabase Auth Methods
        async handleEmailAuth() {
            if (!this.supabase) {
                this.showToast('Authentication service is running in local mode.', 'info');
                this.authModalOpen = false;
                return;
            }

            this.authLoading = true;
            this.authMessage = null;

            try {
                if (this.authTab === 'signin') {
                    const { data, error } = await this.supabase.auth.signInWithPassword({
                        email: this.authEmail,
                        password: this.authPassword,
                    });
                    if (error) throw error;
                    this.showToast('Signed in successfully!', 'success');
                    this.authModalOpen = false;
                } else if (this.authTab === 'signup') {
                    const { data, error } = await this.supabase.auth.signUp({
                        email: this.authEmail,
                        password: this.authPassword,
                        options: {
                            data: { full_name: this.authFullName }
                        }
                    });
                    if (error) throw error;
                    this.showToast('Account created! Check your email for verification.', 'success');
                    this.authModalOpen = false;
                } else if (this.authTab === 'forgot') {
                    const { data, error } = await this.supabase.auth.resetPasswordForEmail(this.authEmail);
                    if (error) throw error;
                    this.showToast('Password reset link sent to your email.', 'success');
                    this.authTab = 'signin';
                }
            } catch (err) {
                this.authMessage = err.message;
            } finally {
                this.authLoading = false;
            }
        },

        async handleGoogleAuth() {
            if (!this.supabase) {
                this.showToast('Google OAuth is available when Supabase is configured.', 'info');
                return;
            }

            try {
                const { data, error } = await this.supabase.auth.signInWithOAuth({
                    provider: 'google',
                    options: {
                        redirectTo: window.location.origin + '/tools/video-generator'
                    }
                });
                if (error) throw error;
            } catch (err) {
                this.showToast(err.message, 'error');
            }
        },

        async syncSessionWithServer(session) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/auth/sync-session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({
                        access_token: session.access_token,
                        user: session.user
                    })
                });

                const data = await res.json();
                if (data.success && data.user) {
                    const wasLoggedOut = !this.user;
                    this.user = data.user;
                    this.authModalOpen = false;

                    if (wasLoggedOut) {
                        this.showToast('Signed in successfully! Loading your workspace...', 'success');
                        if (this.pendingAction === 'generate') {
                            sessionStorage.setItem('pendingAction', 'generate');
                        }
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        if (this.pendingAction === 'generate') {
                            this.pendingAction = null;
                            this.startGeneration();
                        }
                    }
                } else {
                    console.warn('Session sync response was not successful:', data);
                }
            } catch (e) {
                console.warn('Session sync error (non-fatal):', e);
            }
        },

        async handleLogout() {
            if (this.supabase) {
                await this.supabase.auth.signOut();
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            await fetch('/auth/logout', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            this.user = null;
            this.showToast('Signed out successfully', 'info');
        },

        showToast(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message, type }
            }));
        }
    };
}
window.videoGeneratorApp = videoGeneratorApp;

// ==========================================
// 3. Image-to-Video Generator Store
// ==========================================
function imageToVideoApp(config = {}) {
    return {
        // Config & Auth State
        supabaseUrl: config.supabaseUrl || '',
        supabaseAnonKey: config.supabaseAnonKey || '',
        supabase: null,
        user: config.currentUser || null,
        authModalOpen: false,
        authTab: 'signin',
        authLoading: false,
        authEmail: '',
        authPassword: '',
        authFullName: '',
        authMessage: null,

        // Image-to-Video Parameters
        selectedFile: null,
        imagePreviewUrl: null,
        selectedFileName: '',
        selectedFileSize: '',
        isDragging: false,
        prompt: '',
        aspectRatio: '16:9',
        resolution: '720p',
        numFrames: 81,
        framesPerSecond: 24,
        advancedOpen: false,

        // Generation State
        isGenerating: false,
        generationStatus: 'idle', // idle, starting, processing, succeeded, failed, cancelled
        statusMessage: 'Ready',
        elapsedSeconds: 0,
        timerInterval: null,
        pollInterval: null,
        currentPollGenerationId: null,
        currentGeneration: null,
        historyList: config.initialGenerations || [],

        // Neural Pulse Canvas
        neuralPulse: null,

        // Lightbox
        lightboxOpen: false,
        lightboxImage: null,

        // Sample Motion Prompts
        sampleMotionPrompts: [
            "A sleek car speeds along an open desert highway, camera tracking dynamically from the side with swirling dust trails.",
            "Smooth cinematic camera orbit with volumetric sunlight filtering through morning atmospheric mist.",
            "Gentle natural breeze rustling leaves and fabric, with subtle cinematic depth of field and warm golden hour lighting.",
            "Dynamic motion blur and fluid tracking shot capturing high-speed action with vivid cinematic color grading."
        ],

        init() {
            this.initSupabase();

            // Clean Initial State: Always start in clean idle state on page load / refresh
            this.currentGeneration = null;
            this.currentPollGenerationId = null;
            this.isGenerating = false;
            this.generationStatus = 'idle';
            this.statusMessage = 'Ready';
            this.elapsedSeconds = 0;

            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }

            // Check if redirected with auth required flag
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auth') === 'required' || urlParams.get('auth') === '1') {
                this.authModalOpen = true;
                this.showToast('Please sign in or create an account to use the creative tools.', 'info');
                window.history.replaceState({}, '', window.location.pathname);
            }

            this.initNeuralCanvas('i2v-neural-canvas');
        },

        initNeuralCanvas(canvasId) {
            this.$nextTick(() => {
                if (window.NeuralPulseAnimation) {
                    this.neuralPulse = new window.NeuralPulseAnimation(canvasId);
                    if (this.isGenerating) {
                        this.neuralPulse.setMode('generating');
                    }
                }
            });
        },

        triggerFileInput() {
            if (this.isGenerating) return;
            const input = document.getElementById('source-image-file-input');
            if (input) input.click();
        },

        handleFileSelect(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.processSelectedFile(file);
        },

        handleFileDrop(event) {
            this.isDragging = false;
            if (this.isGenerating) return;
            const file = event.dataTransfer?.files?.[0];
            if (!file) return;
            this.processSelectedFile(file);
        },

        processSelectedFile(file) {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                this.showToast('Please select a JPG, PNG, or WebP image.', 'error');
                return;
            }

            if (file.size > 15 * 1024 * 1024) {
                this.showToast('Image file size must be 15 MB or less.', 'error');
                return;
            }

            if (this.imagePreviewUrl) {
                URL.revokeObjectURL(this.imagePreviewUrl);
            }

            this.selectedFile = file;
            this.selectedFileName = file.name;
            this.selectedFileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            this.imagePreviewUrl = URL.createObjectURL(file);
        },

        removeSelectedImage() {
            if (this.isGenerating) return;
            if (this.imagePreviewUrl) {
                URL.revokeObjectURL(this.imagePreviewUrl);
            }
            this.selectedFile = null;
            this.imagePreviewUrl = null;
            this.selectedFileName = '';
            this.selectedFileSize = '';
            const input = document.getElementById('source-image-file-input');
            if (input) input.value = '';
        },

        useSamplePrompt(sample) {
            this.prompt = sample;
        },

        async createNewVideo() {
            // 1. If currently generating or there is an active generation, cancel on backend & provider
            const activeId = this.currentPollGenerationId || (this.currentGeneration && ['starting', 'processing'].includes(this.currentGeneration.status) ? this.currentGeneration.id : null);

            if (activeId) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                try {
                    await fetch(`/tools/image-to-video/cancel/${activeId}`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || ''
                        }
                    });
                } catch (e) {
                    console.warn('Could not cancel active video generation:', e);
                }
            }

            // 2. Stop all timers and polling intervals
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
            this.stopElapsedTimer();

            // 3. Clear source image and revoke preview URL
            this.isGenerating = false;
            this.removeSelectedImage();

            // 4. Reset prompt & parameters to initial defaults
            this.prompt = '';
            this.aspectRatio = '16:9';
            this.resolution = '720p';
            this.numFrames = 81;
            this.framesPerSecond = 24;
            this.advancedOpen = false;

            // 5. Reset all generation and workspace state
            this.currentGeneration = null;
            this.currentPollGenerationId = null;
            this.generationStatus = 'idle';
            this.statusMessage = 'Ready';
            this.elapsedSeconds = 0;

            // 6. Reset Neural Pulse Canvas animation to idle
            if (this.neuralPulse) {
                this.neuralPulse.setMode('idle');
            }

            this.showToast('Ready for a new video creation', 'info');
        },

        resetToolState() {
            this.createNewVideo();
        },

        async startGeneration() {
            if (this.isGenerating) return;

            // Auth Check: Unauthenticated users must log in / register first
            if (!this.user || !this.user.id) {
                this.showToast('Please sign in or create an account to generate videos.', 'warning');
                this.pendingAction = 'generate';
                this.authModalOpen = true;
                return;
            }

            if (!this.selectedFile) {
                this.showToast('Please select a source image first.', 'warning');
                return;
            }

            if (!this.prompt.trim()) {
                this.showToast('Please enter a motion prompt.', 'warning');
                return;
            }

            this.isGenerating = true;
            this.generationStatus = 'starting';
            this.statusMessage = 'Uploading image to Cloudflare R2...';
            this.elapsedSeconds = 0;
            this.startElapsedTimer();

            if (this.neuralPulse) {
                this.neuralPulse.setMode('generating');
            }

            const formData = new FormData();
            formData.append('image', this.selectedFile);
            formData.append('prompt', this.prompt.trim());
            formData.append('aspect_ratio', this.aspectRatio);
            formData.append('resolution', this.resolution);
            formData.append('num_frames', this.numFrames);
            formData.append('frames_per_second', this.framesPerSecond);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch('/tools/image-to-video/generate', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.status === 401 || (data && data.requires_auth)) {
                    this.user = null;
                    this.authModalOpen = true;
                    this.pendingAction = 'generate';
                    this.isGenerating = false;
                    this.generationStatus = 'idle';
                    this.stopElapsedTimer();
                    if (this.neuralPulse) this.neuralPulse.setMode('idle');
                    this.showToast(data.message || data.error || 'Please sign in to generate videos.', 'warning');
                    return;
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Failed to start image-to-video generation.');
                }

                this.currentGeneration = data.generation;
                this.currentPollGenerationId = data.generation.id;
                this.statusMessage = 'Starting generation...';

                // Add to history list
                this.historyList.unshift(data.generation);

                // Start local database polling for status
                this.pollGenerationStatus(data.generation.id);
            } catch (err) {
                console.error('Image-to-video generation error:', err);
                this.stopElapsedTimer();
                this.isGenerating = false;
                this.generationStatus = 'failed';
                this.currentGeneration = {
                    status: 'failed',
                    error_message: err.message || 'An error occurred while starting generation.'
                };
                if (this.neuralPulse) {
                    this.neuralPulse.setMode('idle');
                }
                this.showToast(err.message || 'Generation failed to start.', 'error');
            }
        },

        resumePendingGeneration(generation) {
            this.isGenerating = true;
            this.generationStatus = generation.status || 'processing';
            this.statusMessage = 'Generating video...';
            this.currentPollGenerationId = generation.id;
            this.startElapsedTimer();

            if (this.neuralPulse) {
                this.neuralPulse.setMode('generating');
            }

            this.pollGenerationStatus(generation.id);
        },

        pollGenerationStatus(generationId) {
            if (this.pollInterval) clearInterval(this.pollInterval);

            this.pollInterval = setInterval(async () => {
                try {
                    const response = await fetch(`/tools/image-to-video/status/${generationId}`, {
                        headers: { 'Accept': 'application/json' }
                    });

                    if (!response.ok) return;

                    const data = await response.json();
                    if (!data.success || !data.generation) return;

                    const gen = data.generation;
                    this.currentGeneration = gen;

                    // Update history item
                    const idx = this.historyList.findIndex(h => h.id === gen.id);
                    if (idx !== -1) {
                        this.historyList[idx] = gen;
                    }

                    if (gen.status === 'succeeded') {
                        clearInterval(this.pollInterval);
                        this.pollInterval = null;
                        this.stopElapsedTimer();
                        this.isGenerating = false;
                        this.generationStatus = 'succeeded';
                        this.statusMessage = 'Generation complete.';

                        if (this.neuralPulse) {
                            this.neuralPulse.setMode('idle');
                        }

                        this.showToast('Video synthesized successfully!', 'success');
                    } else if (gen.status === 'failed') {
                        clearInterval(this.pollInterval);
                        this.pollInterval = null;
                        this.stopElapsedTimer();
                        this.isGenerating = false;
                        this.generationStatus = 'failed';

                        if (this.neuralPulse) {
                            this.neuralPulse.setMode('idle');
                        }

                        this.showToast(gen.error_message || 'Video generation failed.', 'error');
                    } else if (gen.status === 'cancelled') {
                        clearInterval(this.pollInterval);
                        this.pollInterval = null;
                        this.stopElapsedTimer();
                        this.isGenerating = false;
                        this.generationStatus = 'cancelled';

                        if (this.neuralPulse) {
                            this.neuralPulse.setMode('idle');
                        }

                        this.showToast('Generation was stopped.', 'info');
                    } else {
                        // Starting or processing
                        this.generationStatus = gen.status;
                        this.statusMessage = 'Generating video...';
                    }
                } catch (err) {
                    console.warn('Status check poll error:', err);
                }
            }, 1500);
        },

        async stopGeneration() {
            if (!this.currentPollGenerationId) return;

            const genId = this.currentPollGenerationId;
            if (this.pollInterval) clearInterval(this.pollInterval);
            this.stopElapsedTimer();
            this.isGenerating = false;
            this.generationStatus = 'cancelled';

            if (this.neuralPulse) {
                this.neuralPulse.setMode('idle');
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                await fetch(`/tools/image-to-video/cancel/${genId}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    }
                });
            } catch (e) {
                // Ignore cancel errors
            }

            if (this.currentGeneration) {
                this.currentGeneration.status = 'cancelled';
                this.currentGeneration.error_message = 'Video generation was stopped by user.';
            }

            this.showToast('Generation stopped.', 'info');
        },

        startElapsedTimer() {
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.elapsedSeconds = 0;
            this.timerInterval = setInterval(() => {
                this.elapsedSeconds++;
            }, 1000);
        },

        stopElapsedTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        },

        copyPrompt(text) {
            if (!text) return;
            navigator.clipboard.writeText(text).then(() => {
                this.showToast('Prompt copied to clipboard', 'info');
            }).catch(() => {
                this.showToast('Could not copy prompt', 'error');
            });
        },

        remixPrompt(gen) {
            if (!gen) return;
            this.prompt = gen.prompt || '';
            if (gen.aspect_ratio) this.aspectRatio = gen.aspect_ratio;
            if (gen.resolution) this.resolution = gen.resolution;
            if (gen.num_frames) this.numFrames = gen.num_frames;
            if (gen.frame_rate) this.framesPerSecond = gen.frame_rate;
            this.showToast('Parameters copied for remixing', 'info');
        },

        async shareGeneration(gen) {
            if (!gen) return;
            const shareUrl = gen.video_url || window.location.href;
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: 'Cinematic AI Video',
                        text: gen.prompt,
                        url: shareUrl
                    });
                    return;
                } catch (e) {
                    // fall through
                }
            }

            navigator.clipboard.writeText(shareUrl).then(() => {
                this.showToast('Video URL copied to clipboard', 'info');
            });
        },

        async deleteGeneration(id) {
            if (!confirm('Are you sure you want to delete this video creation?')) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const res = await fetch(`/tools/image-to-video/delete/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    }
                });

                if (res.ok) {
                    this.historyList = this.historyList.filter(h => h.id !== id);
                    if (this.currentGeneration?.id === id) {
                        this.currentGeneration = null;
                        this.generationStatus = 'idle';
                    }
                    this.showToast('Video creation deleted', 'info');
                }
            } catch (err) {
                this.showToast('Failed to delete creation', 'error');
            }
        },

        openLightbox(imgUrl) {
            if (!imgUrl) return;
            this.lightboxImage = imgUrl;
            this.lightboxOpen = true;
        },

        closeLightbox() {
            this.lightboxOpen = false;
            this.lightboxImage = null;
        },

        initSupabase() {
            if (this.supabaseUrl && this.supabaseAnonKey && typeof supabase !== 'undefined') {
                try {
                    this.supabase = supabase.createClient(this.supabaseUrl, this.supabaseAnonKey);

                    this.supabase.auth.onAuthStateChange(async (event, session) => {
                        // INITIAL_SESSION fires on every page load.
                        // If the server already provided currentUser via Laravel session,
                        // there is nothing to sync — skip to avoid triggering the login loop.
                        if (event === 'INITIAL_SESSION') {
                            if (!this.user && session && session.user) {
                                await this.syncSessionWithServer(session);
                            }
                            return;
                        }

                        if ((event === 'SIGNED_IN' || event === 'TOKEN_REFRESHED') && session && session.user) {
                            await this.syncSessionWithServer(session);
                        } else if (event === 'SIGNED_OUT') {
                            this.user = null;
                        }
                    });
                } catch (e) {
                    console.warn('Supabase client init error:', e);
                }
            }
        },

        // Supabase Auth Methods
        async handleEmailAuth() {
            if (!this.supabase) {
                this.showToast('Authentication service is running in local mode.', 'info');
                this.authModalOpen = false;
                return;
            }

            this.authLoading = true;
            this.authMessage = null;

            try {
                if (this.authTab === 'signin') {
                    const { data, error } = await this.supabase.auth.signInWithPassword({
                        email: this.authEmail,
                        password: this.authPassword,
                    });
                    if (error) throw error;
                    this.showToast('Signed in successfully!', 'success');
                    this.authModalOpen = false;
                } else if (this.authTab === 'signup') {
                    const { data, error } = await this.supabase.auth.signUp({
                        email: this.authEmail,
                        password: this.authPassword,
                        options: {
                            data: { full_name: this.authFullName }
                        }
                    });
                    if (error) throw error;
                    this.showToast('Account created! Check your email for verification.', 'success');
                    this.authModalOpen = false;
                } else if (this.authTab === 'forgot') {
                    const { data, error } = await this.supabase.auth.resetPasswordForEmail(this.authEmail);
                    if (error) throw error;
                    this.showToast('Password reset link sent to your email.', 'success');
                    this.authTab = 'signin';
                }
            } catch (err) {
                this.authMessage = err.message;
            } finally {
                this.authLoading = false;
            }
        },

        async handleGoogleAuth() {
            if (!this.supabase) {
                this.showToast('Google OAuth is available when Supabase is configured.', 'info');
                return;
            }

            try {
                const { data, error } = await this.supabase.auth.signInWithOAuth({
                    provider: 'google',
                    options: {
                        redirectTo: window.location.origin + '/tools/image-to-video'
                    }
                });
                if (error) throw error;
            } catch (err) {
                this.showToast(err.message, 'error');
            }
        },

        async syncSessionWithServer(session) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/auth/sync-session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({
                        access_token: session.access_token,
                        user: session.user
                    })
                });

                const data = await res.json();
                if (data.success && data.user) {
                    const wasLoggedOut = !this.user;
                    this.user = data.user;
                    this.authModalOpen = false;

                    if (wasLoggedOut) {
                        this.showToast('Signed in successfully! Loading your workspace...', 'success');
                        if (this.pendingAction === 'generate') {
                            sessionStorage.setItem('pendingAction', 'generate');
                        }
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        if (this.pendingAction === 'generate') {
                            this.pendingAction = null;
                            if (this.selectedFile && this.prompt) {
                                this.startGeneration();
                            }
                        }
                    }
                } else {
                    console.warn('Session sync response was not successful:', data);
                }
            } catch (e) {
                console.warn('Session sync error (non-fatal):', e);
            }
        },

        async handleLogout() {
            if (this.supabase) {
                await this.supabase.auth.signOut();
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            await fetch('/auth/logout', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            this.user = null;
            this.showToast('Signed out successfully', 'info');
        },

        showToast(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message, type }
            }));
        }
    };
}
window.imageToVideoApp = imageToVideoApp;

// ==========================================
// 4. User Dashboard & Profile Store
// ==========================================
function profileApp(config = {}) {
    return {
        supabaseUrl: config.supabaseUrl || '',
        supabaseAnonKey: config.supabaseAnonKey || '',
        supabase: null,
        user: config.currentUser || null,
        authModalOpen: false,
        authTab: 'signin',
        authLoading: false,
        authEmail: '',
        authPassword: '',
        authFullName: '',
        authMessage: null,

        // Profile Details
        profileId: config.currentUser?.id || 'local-user',
        profileName: config.currentUser?.name || '',
        profileEmail: config.currentUser?.email || '',
        isSavingProfile: false,
        copiedId: false,

        // Password Form & Visibility
        passwordForm: {
            current: '',
            password: '',
            password_confirmation: ''
        },
        showCurrentPassword: false,
        showPassword: false,
        showConfirmPassword: false,
        isSavingPassword: false,

        // Security & Stats
        security: config.securitySettings || {
            two_factor_enabled: false,
            email_verified: true,
            last_password_change: 'Recently'
        },
        stats: config.stats || {
            total_images: 0,
            total_videos: 0,
            total_creations: 0
        },

        // Preferences
        preferences: {
            auto_download: false
        },

        // UI Tabs & History Search
        activeTab: 'profile', // 'profile', 'security', 'history', 'settings'
        historyFilter: 'all', // 'all', 'images', 'videos'
        historySearch: '',
        allCreations: config.initialGenerations || [],

        // Lightbox
        lightboxOpen: false,
        lightboxImage: null,

        init() {
            this.initSupabase();
        },

        initSupabase() {
            if (window.supabase && this.supabaseUrl && this.supabaseAnonKey) {
                try {
                    this.supabase = window.supabase.createClient(this.supabaseUrl, this.supabaseAnonKey);
                    this.supabase.auth.onAuthStateChange(async (event, session) => {
                        // INITIAL_SESSION fires on every page load.
                        // If the server already provided currentUser via Laravel session,
                        // there is nothing to sync — skip to avoid triggering the login loop.
                        if (event === 'INITIAL_SESSION') {
                            if (!this.user && session && session.user) {
                                await this.syncSessionWithServer(session);
                            }
                            return;
                        }

                        if ((event === 'SIGNED_IN' || event === 'TOKEN_REFRESHED') && session && session.user) {
                            await this.syncSessionWithServer(session);
                        } else if (event === 'SIGNED_OUT') {
                            this.user = null;
                        }
                    });
                } catch (e) {
                    console.warn('Supabase initialization error:', e);
                }
            }
        },

        get imageCreations() {
            return this.allCreations.filter(item => !item.video_url && !item.video_path);
        },

        get videoCreations() {
            return this.allCreations.filter(item => Boolean(item.video_url || item.video_path));
        },

        get filteredCreations() {
            let list = this.allCreations;
            if (this.historyFilter === 'images') {
                list = this.imageCreations;
            } else if (this.historyFilter === 'videos') {
                list = this.videoCreations;
            }

            if (this.historySearch && this.historySearch.trim().length > 0) {
                const query = this.historySearch.toLowerCase().trim();
                list = list.filter(item => item.prompt && item.prompt.toLowerCase().includes(query));
            }

            return list;
        },

        get passwordStrength() {
            const pwd = this.passwordForm.password || '';
            if (!pwd) return { score: 0, label: 'Too short', color: '#64748b', percent: 0 };
            
            let score = 0;
            if (pwd.length >= 6) score += 1;
            if (pwd.length >= 10) score += 1;
            if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score += 1;
            if (/[0-9]/.test(pwd) || /[^A-Za-z0-9]/.test(pwd)) score += 1;

            if (score <= 1) return { score: 1, label: 'Weak', color: '#f87171', percent: 25 };
            if (score === 2) return { score: 2, label: 'Fair', color: '#fbbf24', percent: 50 };
            if (score === 3) return { score: 3, label: 'Good', color: '#60a5fa', percent: 75 };
            return { score: 4, label: 'Strong', color: '#34d399', percent: 100 };
        },

        copyId() {
            if (!this.profileId) return;
            navigator.clipboard.writeText(this.profileId);
            this.copiedId = true;
            setTimeout(() => { this.copiedId = false; }, 2000);
            this.showToast('User UUID copied to clipboard', 'success');
        },

        async saveProfileInfo() {
            this.isSavingProfile = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/profile/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({
                        name: this.profileName,
                        email: this.profileEmail
                    })
                });

                const data = await res.json();
                if (data.success) {
                    if (this.user) {
                        this.user.name = this.profileName;
                        this.user.email = this.profileEmail;
                    }
                    this.showToast('Profile information saved successfully!', 'success');
                } else {
                    this.showToast(data.message || 'Failed to update profile.', 'error');
                }
            } catch (err) {
                this.showToast('Network error updating profile.', 'error');
            } finally {
                this.isSavingProfile = false;
            }
        },

        async savePassword() {
            if (this.passwordForm.password.length < 6) {
                this.showToast('Password must be at least 6 characters.', 'error');
                return;
            }
            if (this.passwordForm.password !== this.passwordForm.password_confirmation) {
                this.showToast('Password confirmation does not match.', 'error');
                return;
            }

            this.isSavingPassword = true;
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/profile/password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({
                        current_password: this.passwordForm.current,
                        password: this.passwordForm.password,
                        password_confirmation: this.passwordForm.password_confirmation
                    })
                });

                const data = await res.json();
                if (data.success) {
                    this.passwordForm = { current: '', password: '', password_confirmation: '' };
                    this.showToast('Password changed successfully!', 'success');
                } else {
                    this.showToast(data.message || 'Failed to update password.', 'error');
                }
            } catch (err) {
                this.showToast('Network error updating password.', 'error');
            } finally {
                this.isSavingPassword = false;
            }
        },

        async toggle2FA(enabled) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/profile/2fa', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({ enabled })
                });

                const data = await res.json();
                if (data.success) {
                    this.security.two_factor_enabled = data.two_factor_enabled;
                    this.showToast(data.message, 'success');
                }
            } catch (err) {
                this.showToast('Could not update 2FA setting.', 'error');
            }
        },

        async deleteItem(item) {
            if (!confirm('Are you sure you want to delete this creation?')) return;

            const isVideo = Boolean(item.video_url || item.video_path);
            const endpoint = isVideo ? `/tools/video-generator/delete/${item.id}` : `/tools/image-generator/delete/${item.id}`;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(endpoint, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    }
                });

                const data = await res.json();
                if (data.success) {
                    this.allCreations = this.allCreations.filter(c => c.id !== item.id);
                    if (isVideo) {
                        this.stats.total_videos = Math.max(0, this.stats.total_videos - 1);
                    } else {
                        this.stats.total_images = Math.max(0, this.stats.total_images - 1);
                    }
                    this.stats.total_creations = Math.max(0, this.stats.total_creations - 1);
                    this.showToast('Item deleted successfully.', 'info');
                }
            } catch (err) {
                this.showToast('Failed to delete item.', 'error');
            }
        },

        async clearAllHistory() {
            if (!confirm('Are you sure you want to clear your entire creation history? This action cannot be undone.')) return;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/profile/clear-history', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({ type: 'all' })
                });

                const data = await res.json();
                if (data.success) {
                    this.allCreations = [];
                    this.stats.total_images = 0;
                    this.stats.total_videos = 0;
                    this.stats.total_creations = 0;
                    this.showToast('All creation history cleared.', 'info');
                }
            } catch (err) {
                this.showToast('Failed to clear history.', 'error');
            }
        },

        copyPrompt(text) {
            navigator.clipboard.writeText(text);
            this.showToast('Prompt copied to clipboard!', 'success');
        },

        openLightbox(imageUrl) {
            if (!imageUrl) return;
            this.lightboxImage = imageUrl;
            this.lightboxOpen = true;
        },

        closeLightbox() {
            this.lightboxOpen = false;
            this.lightboxImage = null;
        },

        async handleEmailAuth() {
            if (!this.supabase) {
                this.showToast('Authentication service is running in local mode.', 'info');
                this.authModalOpen = false;
                return;
            }
            this.authLoading = true;
            this.authMessage = null;
            try {
                if (this.authTab === 'signin') {
                    const { data, error } = await this.supabase.auth.signInWithPassword({
                        email: this.authEmail,
                        password: this.authPassword,
                    });
                    if (error) throw error;
                    this.showToast('Signed in successfully!', 'success');
                    this.authModalOpen = false;
                } else if (this.authTab === 'signup') {
                    const { data, error } = await this.supabase.auth.signUp({
                        email: this.authEmail,
                        password: this.authPassword,
                        options: { data: { full_name: this.authFullName } }
                    });
                    if (error) throw error;
                    this.showToast('Account created! Check your email for verification.', 'success');
                    this.authModalOpen = false;
                }
            } catch (err) {
                this.authMessage = err.message;
            } finally {
                this.authLoading = false;
            }
        },

        async handleGoogleAuth() {
            if (!this.supabase) {
                this.showToast('Google OAuth is available when Supabase is configured.', 'info');
                return;
            }
            try {
                const { data, error } = await this.supabase.auth.signInWithOAuth({
                    provider: 'google',
                    options: { redirectTo: window.location.origin + '/profile' }
                });
                if (error) throw error;
            } catch (err) {
                this.showToast(err.message, 'error');
            }
        },

        async syncSessionWithServer(session) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/auth/sync-session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({
                        access_token: session.access_token,
                        user: session.user
                    })
                });
                const data = await res.json();
                if (data.success && data.user) {
                    const wasLoggedOut = !this.user;
                    this.user = data.user;
                    this.profileName = data.user.name || this.profileName;
                    this.profileEmail = data.user.email || this.profileEmail;

                    if (wasLoggedOut) {
                        // Reload so Laravel session is fully hydrated
                        setTimeout(() => window.location.reload(), 800);
                    }
                } else {
                    console.warn('Session sync response was not successful:', data);
                }
            } catch (e) {
                console.warn('Session sync error (non-fatal):', e);
            }
        },

        async handleLogout() {
            if (this.supabase) {
                await this.supabase.auth.signOut();
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            await fetch('/auth/logout', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            window.location.href = '/tools';
        },

        showToast(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message, type }
            }));
        }
    };
}
window.profileApp = profileApp;

// ==========================================
// 4. Toast Manager Component
// ==========================================
function toastManager() {
    return {
        toasts: [],
        init() {
            window.addEventListener('notify', (e) => {
                const id = Date.now() + Math.random();
                const toast = {
                    id,
                    message: e.detail.message,
                    type: e.detail.type || 'info'
                };
                this.toasts.push(toast);

                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 4000);
            });
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    };
}
window.toastManager = toastManager;

// ==========================================
// Alpine Component Registration Helper
// ==========================================
function registerAlpineStores() {
    if (typeof window !== 'undefined' && window.Alpine) {
        window.Alpine.data('imageGeneratorApp', imageGeneratorApp);
        window.Alpine.data('videoGeneratorApp', videoGeneratorApp);
        window.Alpine.data('imageToVideoApp', imageToVideoApp);
        window.Alpine.data('profileApp', profileApp);
        window.Alpine.data('toastManager', toastManager);
    }
}

document.addEventListener('alpine:init', registerAlpineStores);
if (typeof window !== 'undefined' && window.Alpine) {
    registerAlpineStores();
}

