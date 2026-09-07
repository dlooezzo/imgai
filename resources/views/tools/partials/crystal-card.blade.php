<!-- Premium AI Tools Glowing Crystal Card (HTML/CSS/SVG) -->
<div class="ai-premium-crystal-card">
    <!-- Ambient card glow orbs -->
    <div class="crystal-card-ambient-glow glow-purple"></div>
    <div class="crystal-card-ambient-glow glow-cyan"></div>

    <div class="crystal-card-inner">
        <!-- Left Side: Content & Action -->
        <div class="crystal-card-content">
            <div class="crystal-card-badge">
                <span class="crystal-badge-dot"></span>
                <span>PREMIUM SUITE</span>
            </div>
            <h3 class="crystal-card-title">Unlock Premium AI Tools</h3>
            <p class="crystal-card-desc">Get unlimited access to all features, cinematic motion video, and premium tools.</p>
            <div class="crystal-card-actions">
                <a href="{{ route('pricing') }}" class="btn-crystal-upgrade">
                    <span>Upgrade Now</span>
                    <i data-lucide="arrow-right" class="upgrade-arrow-icon" style="width: 15px; height: 15px;"></i>
                </a>
            </div>
        </div>

        <!-- Right Side: Glowing Faceted Crystal / Diamond Artwork -->
        <div class="crystal-card-visual" aria-hidden="true">
            <svg class="crystal-svg" viewBox="0 0 200 220" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <!-- Deep layered glow filter for crystal core -->
                    <filter id="crystalGlow" x="-50%" y="-50%" width="200%" height="200%">
                        <feGaussianBlur in="SourceGraphic" stdDeviation="5" result="blur1" />
                        <feGaussianBlur in="SourceGraphic" stdDeviation="12" result="blur2" />
                        <feMerge>
                            <feMergeNode in="blur2" />
                            <feMergeNode in="blur1" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>

                    <!-- Focused neon glow for orbital rings & sparkles -->
                    <filter id="ringGlow" x="-50%" y="-50%" width="200%" height="200%">
                        <feGaussianBlur in="SourceGraphic" stdDeviation="3" result="glow" />
                        <feMerge>
                            <feMergeNode in="glow" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>

                    <!-- Prismatic facet gradients -->
                    <linearGradient id="facetTopApex" x1="100" y1="20" x2="100" y2="78" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0.98" />
                        <stop offset="35%" stop-color="#38bdf8" />
                        <stop offset="100%" stop-color="#3b82f6" />
                    </linearGradient>

                    <linearGradient id="facetTopLeft" x1="65" y1="30" x2="100" y2="78" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#7dd3fc" />
                        <stop offset="60%" stop-color="#0284c7" />
                        <stop offset="100%" stop-color="#2563eb" />
                    </linearGradient>

                    <linearGradient id="facetTopRight" x1="135" y1="30" x2="100" y2="78" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#c084fc" />
                        <stop offset="60%" stop-color="#9333ea" />
                        <stop offset="100%" stop-color="#4f46e5" />
                    </linearGradient>

                    <linearGradient id="facetCenterCore" x1="100" y1="65" x2="100" y2="135" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#bae6fd" />
                        <stop offset="45%" stop-color="#818cf8" />
                        <stop offset="100%" stop-color="#e879f9" />
                    </linearGradient>

                    <linearGradient id="facetCenterLeft" x1="50" y1="90" x2="100" y2="125" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#06b6d4" />
                        <stop offset="100%" stop-color="#6366f1" />
                    </linearGradient>

                    <linearGradient id="facetCenterRight" x1="150" y1="90" x2="100" y2="125" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#a855f7" />
                        <stop offset="100%" stop-color="#ec4899" />
                    </linearGradient>

                    <linearGradient id="facetBottomCore" x1="100" y1="120" x2="100" y2="185" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#f472b6" />
                        <stop offset="50%" stop-color="#e11d48" />
                        <stop offset="100%" stop-color="#fdf2f8" />
                    </linearGradient>

                    <linearGradient id="facetBottomLeft" x1="60" y1="130" x2="100" y2="185" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#4338ca" />
                        <stop offset="70%" stop-color="#9333ea" />
                        <stop offset="100%" stop-color="#f43f5e" />
                    </linearGradient>

                    <linearGradient id="facetBottomRight" x1="140" y1="130" x2="100" y2="185" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#701a75" />
                        <stop offset="60%" stop-color="#be185d" />
                        <stop offset="100%" stop-color="#f43f5e" />
                    </linearGradient>

                    <!-- Neon Light Rings Gradients -->
                    <linearGradient id="neonRingGrad1" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stop-color="#38bdf8" />
                        <stop offset="50%" stop-color="#c084fc" />
                        <stop offset="100%" stop-color="#f472b6" />
                    </linearGradient>

                    <linearGradient id="neonRingGrad2" x1="1" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#ec4899" />
                        <stop offset="60%" stop-color="#8b5cf6" />
                        <stop offset="100%" stop-color="#06b6d4" />
                    </linearGradient>

                    <!-- Base Energy Splash Gradient -->
                    <radialGradient id="splashRadial" cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stop-color="#ec4899" stop-opacity="0.8" />
                        <stop offset="45%" stop-color="#8b5cf6" stop-opacity="0.4" />
                        <stop offset="100%" stop-color="#3b82f6" stop-opacity="0" />
                    </radialGradient>
                </defs>

                <!-- Base Energy Splash Ripples -->
                <ellipse cx="100" cy="186" rx="55" ry="14" fill="url(#splashRadial)" />
                <ellipse cx="100" cy="186" rx="42" ry="9" stroke="url(#neonRingGrad1)" stroke-width="1.5" fill="none" opacity="0.8" filter="url(#ringGlow)" />
                <ellipse cx="100" cy="186" rx="26" ry="5" stroke="#ffffff" stroke-width="1.2" fill="none" opacity="0.9" />

                <!-- Orbital Neon Light Ring 1 (Sweeping Loop) -->
                <g class="crystal-orbit-1">
                    <ellipse cx="100" cy="112" rx="76" ry="24" transform="rotate(-22 100 112)" stroke="url(#neonRingGrad1)" stroke-width="2.6" fill="none" filter="url(#ringGlow)" opacity="0.88" />
                </g>

                <!-- Orbital Neon Light Ring 2 (Counter-Angled Loop) -->
                <g class="crystal-orbit-2">
                    <ellipse cx="100" cy="112" rx="70" ry="18" transform="rotate(24 100 112)" stroke="url(#neonRingGrad2)" stroke-width="2.2" fill="none" filter="url(#ringGlow)" opacity="0.85" />
                </g>

                <!-- Floating Shards & Sparkles -->
                <polygon points="34,80 39,74 44,83 37,88" fill="#38bdf8" opacity="0.8" filter="url(#ringGlow)" class="float-shard-1" />
                <polygon points="160,88 167,82 169,93 162,97" fill="#f472b6" opacity="0.8" filter="url(#ringGlow)" class="float-shard-2" />
                <polygon points="144,48 149,43 153,50 148,54" fill="#c084fc" opacity="0.75" class="float-shard-3" />
                <polygon points="50,146 54,141 58,148 53,152" fill="#818cf8" opacity="0.7" class="float-shard-4" />

                <!-- 4-point AI Star Sparkles -->
                <path d="M136,32 Q136,38 142,38 Q136,38 136,44 Q136,38 130,38 Q136,38 136,32 Z" fill="#ffffff" filter="url(#ringGlow)" class="sparkle-star sp-1" />
                <path d="M42,66 Q42,72 48,72 Q42,72 42,78 Q42,72 36,72 Q42,72 42,66 Z" fill="#7dd3fc" filter="url(#ringGlow)" class="sparkle-star sp-2" />
                <path d="M168,136 Q168,142 174,142 Q168,142 168,148 Q168,142 162,142 Q168,142 168,136 Z" fill="#f472b6" filter="url(#ringGlow)" class="sparkle-star sp-3" />
                <path d="M38,138 Q38,143 43,143 Q38,143 38,148 Q38,143 33,143 Q38,143 38,138 Z" fill="#c084fc" class="sparkle-star sp-4" />
                <path d="M68,26 Q68,29 71,29 Q68,29 68,32 Q68,29 65,29 Q68,29 68,26 Z" fill="#ffffff" class="sparkle-star sp-5" />

                <!-- Main Glowing Crystal Gem Body -->
                <g class="crystal-gem-body" filter="url(#crystalGlow)">
                    <!-- Background Depth Core -->
                    <polygon points="100,20 142,65 146,112 130,154 100,184 70,154 54,112 58,65" fill="#1e1b4b" opacity="0.8" />

                    <!-- Top Apex Facets -->
                    <polygon points="100,20 58,65 74,78 100,72" fill="url(#facetTopLeft)" stroke="#38bdf8" stroke-width="0.75" stroke-opacity="0.6" />
                    <polygon points="100,20 74,78 100,88 126,78" fill="url(#facetTopApex)" stroke="#ffffff" stroke-width="1" stroke-opacity="0.9" />
                    <polygon points="100,20 126,78 142,65" fill="url(#facetTopRight)" stroke="#c084fc" stroke-width="0.75" stroke-opacity="0.6" />

                    <!-- Mid Girdle Facets -->
                    <polygon points="58,65 54,112 76,122 74,78" fill="url(#facetCenterLeft)" stroke="#38bdf8" stroke-width="0.75" stroke-opacity="0.5" />
                    <polygon points="74,78 76,122 100,132 100,88" fill="url(#facetCenterCore)" stroke="#ffffff" stroke-width="0.8" stroke-opacity="0.7" />
                    <polygon points="100,88 100,132 124,122 126,78" fill="url(#facetCenterCore)" stroke="#ffffff" stroke-width="0.8" stroke-opacity="0.7" />
                    <polygon points="126,78 124,122 146,112 142,65" fill="url(#facetCenterRight)" stroke="#ec4899" stroke-width="0.75" stroke-opacity="0.5" />

                    <!-- Bottom Pavilion Facets -->
                    <polygon points="54,112 70,154 84,142 76,122" fill="url(#facetBottomLeft)" stroke="#818cf8" stroke-width="0.75" stroke-opacity="0.5" />
                    <polygon points="76,122 84,142 100,184 100,132" fill="url(#facetBottomCore)" stroke="#f472b6" stroke-width="0.8" stroke-opacity="0.7" />
                    <polygon points="100,132 100,184 116,142 124,122" fill="url(#facetBottomRight)" stroke="#f472b6" stroke-width="0.8" stroke-opacity="0.7" />
                    <polygon points="124,122 116,142 130,154 146,112" fill="url(#facetBottomRight)" stroke="#ec4899" stroke-width="0.75" stroke-opacity="0.5" />

                    <!-- Specular Highlight Lines -->
                    <line x1="100" y1="20" x2="100" y2="184" stroke="#ffffff" stroke-width="1.5" stroke-opacity="0.85" />
                    <line x1="74" y1="78" x2="126" y2="78" stroke="#ffffff" stroke-width="1" stroke-opacity="0.65" />
                    <line x1="76" y1="122" x2="124" y2="122" stroke="#ffffff" stroke-width="1" stroke-opacity="0.65" />

                    <!-- Futuristic Geometric / Runic Etchings -->
                    <path d="M94,92 L100,86 L106,92 M100,86 L100,108 M95,100 L105,100" stroke="#ffffff" stroke-width="1.2" stroke-linecap="round" opacity="0.85" />
                    <path d="M95,124 L100,119 L105,124 M100,119 L100,138" stroke="#ffffff" stroke-width="1.2" stroke-linecap="round" opacity="0.8" />
                    <circle cx="100" cy="108" r="2.2" fill="#ffffff" filter="url(#ringGlow)" />
                    <circle cx="100" cy="138" r="1.8" fill="#ffffff" filter="url(#ringGlow)" />

                    <!-- Luminous Pole Highlights -->
                    <circle cx="100" cy="22" r="3" fill="#ffffff" filter="url(#ringGlow)" />
                    <circle cx="100" cy="183" r="3.5" fill="#ffffff" filter="url(#ringGlow)" />
                </g>

                <!-- Foreground Subtle Orbit Trail -->
                <ellipse cx="100" cy="110" rx="86" ry="30" transform="rotate(-12 100 110)" stroke="url(#neonRingGrad1)" stroke-width="1.4" stroke-dasharray="8 6" fill="none" opacity="0.55" filter="url(#ringGlow)" />
            </svg>
        </div>
    </div>
</div>
