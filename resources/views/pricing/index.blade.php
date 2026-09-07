@extends('layouts.app')

@section('title', 'Pricing Plans & Subscriptions — ' . config('app.name', 'IMGAI'))

@section('content')
<!-- Paddle Billing v2 Script -->
<script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>

<style>
/* ==========================================================================
   PRICING PAGE STYLES
   Clean, robust classes to prevent Alpine.js :style string hydration clashes
   ========================================================================== */
.pricing-page-wrapper {
    min-height: 100vh;
    width: 100%;
    flex: 1;
    background: #030712;
    color: #f8fafc;
    padding: 72px 20px;
    position: relative;
    overflow: hidden;
    font-family: 'Plus Jakarta Sans', sans-serif;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}

.pricing-ambient-top {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
    max-width: 1200px;
    height: 480px;
    background: radial-gradient(ellipse at top, rgba(168, 85, 247, 0.22) 0%, rgba(99, 102, 241, 0.12) 40%, transparent 70%);
    pointer-events: none;
    z-index: 0;
    filter: blur(40px);
}

.pricing-ambient-bottom {
    position: absolute;
    bottom: 5%;
    right: 5%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(6, 182, 212, 0.15) 0%, transparent 70%);
    pointer-events: none;
    z-index: 0;
    filter: blur(50px);
}

.pricing-container {
    max-width: 1240px;
    width: 100%;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

.pricing-header {
    text-align: center;
    max-width: 760px;
    margin: 0 auto 56px;
}

.pricing-pill-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 9999px;
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.25);
    color: #c084fc;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 18px;
}

.pricing-pill-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #c084fc;
    box-shadow: 0 0 8px #c084fc;
}

.pricing-main-title {
    font-size: clamp(2rem, 4vw, 3.2rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.15;
    margin-bottom: 16px;
    color: #ffffff;
}

.pricing-gradient-text {
    background: linear-gradient(135deg, #c084fc 0%, #818cf8 50%, #38bdf8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.pricing-subtitle {
    color: #94a3b8;
    font-size: 1.05rem;
    line-height: 1.6;
    margin-bottom: 20px;
}

/* Monthly / Yearly Toggle */
.pricing-toggle-container {
    display: flex;
    justify-content: center;
    margin-bottom: 60px;
}

.pricing-toggle-pill {
    background: rgba(15, 23, 42, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 9999px;
    padding: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(12px);
}

.pricing-toggle-btn {
    padding: 8px 22px;
    border-radius: 9999px;
    font-size: 0.88rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
    background: transparent;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: inherit;
    outline: none;
}

.pricing-toggle-btn.active {
    background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
}

.pricing-discount-badge {
    font-size: 0.7rem;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 9999px;
    background: rgba(168, 85, 247, 0.25);
    color: #e9d5ff;
    border: 1px solid rgba(168, 85, 247, 0.4);
}

/* Pricing Grid */
.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 32px;
    align-items: stretch;
}

.pricing-card {
    border-radius: 24px;
    padding: 36px 32px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(15, 23, 42, 0.6);
    box-sizing: border-box;
}

.pricing-card:hover {
    border-color: rgba(255, 255, 255, 0.16);
    transform: translateY(-4px);
}

.pricing-card.popular {
    border: 1px solid rgba(168, 85, 247, 0.5);
    box-shadow: 0 0 25px rgba(168, 85, 247, 0.18);
    background: linear-gradient(180deg, rgba(30, 27, 75, 0.4) 0%, rgba(15, 23, 42, 0.85) 100%);
    transform: translateY(-8px);
}

.pricing-card.popular:hover {
    box-shadow: 0 0 35px rgba(168, 85, 247, 0.3);
    transform: translateY(-12px);
}

.pricing-badge-wrapper {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
}

.pricing-badge-pill {
    background: linear-gradient(135deg, #a855f7 0%, #6366f1 100%);
    color: #ffffff;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 5px 16px;
    border-radius: 9999px;
    box-shadow: 0 4px 12px rgba(168, 85, 247, 0.4);
    display: flex;
    align-items: center;
    gap: 5px;
}

.pricing-tier-name {
    font-size: 1.6rem;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.02em;
    margin-bottom: 8px;
}

.pricing-tier-desc {
    color: #94a3b8;
    font-size: 0.88rem;
    line-height: 1.5;
    min-height: 48px;
    margin-bottom: 24px;
}

.pricing-price-wrap {
    margin-bottom: 28px;
}

.pricing-price-row {
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.pricing-price-val {
    font-size: 2.8rem;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.03em;
    line-height: 1;
}

.pricing-price-cycle {
    font-size: 0.9rem;
    color: #94a3b8;
    font-weight: 500;
}

.pricing-price-hint {
    font-size: 0.78rem;
    color: #64748b;
    margin-top: 6px;
}

.pricing-card-divider {
    width: 100%;
    height: 1px;
    background: rgba(255, 255, 255, 0.08);
    margin-bottom: 28px;
}

.pricing-features-wrap {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-bottom: 36px;
}

.pricing-features-heading {
    font-size: 0.74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
}

.pricing-feature-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.pricing-feature-check {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}

.pricing-feature-check.icon-pro {
    background: rgba(168, 85, 247, 0.15);
    color: #c084fc;
}

.pricing-feature-check.icon-default {
    background: rgba(99, 102, 241, 0.15);
    color: #818cf8;
}

.pricing-feature-text {
    font-size: 0.88rem;
    color: #cbd5e1;
    line-height: 1.45;
}

.pricing-cta-btn {
    width: 100%;
    padding: 14px 20px;
    border-radius: 14px;
    font-size: 0.94rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-family: inherit;
    box-sizing: border-box;
}

.pricing-cta-btn.btn-pro {
    background: linear-gradient(135deg, #9333ea 0%, #4f46e5 100%);
    color: #ffffff;
    box-shadow: 0 4px 20px rgba(147, 51, 234, 0.4);
    border: none;
}

.pricing-cta-btn.btn-pro:hover {
    box-shadow: 0 6px 25px rgba(147, 51, 234, 0.6);
    transform: translateY(-1px);
}

.pricing-cta-btn.btn-default {
    background: rgba(255, 255, 255, 0.08);
    color: #f8fafc;
    border: 1px solid rgba(255, 255, 255, 0.15);
}

.pricing-cta-btn.btn-default:hover {
    background: rgba(255, 255, 255, 0.14);
    border-color: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
}

/* Trust Badges */
.pricing-trust-footer {
    margin-top: 80px;
    padding-top: 40px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    text-align: center;
}

.pricing-trust-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.pricing-trust-title {
    font-weight: 700;
    font-size: 0.92rem;
    color: #f8fafc;
    margin-bottom: 4px;
}

.pricing-trust-desc {
    font-size: 0.8rem;
    color: #94a3b8;
    line-height: 1.45;
}

/* Responsive Media Queries */
@media (max-width: 900px) {
    .pricing-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
    }
    .pricing-page-wrapper {
        padding: 56px 16px;
    }
}

@media (max-width: 640px) {
    .pricing-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    .pricing-card.popular {
        transform: none;
    }
    .pricing-card.popular:hover {
        transform: translateY(-4px);
    }
    .pricing-toggle-container {
        margin-bottom: 40px;
    }
}
</style>

<div x-data="pricingManager({
    userEmail: {{ json_encode($userEmail) }},
    userId: {{ json_encode($userId) }},
    isLoggedIn: {{ json_encode($isLoggedIn) }},
    paddleClientToken: {{ json_encode($paddleClientToken) }},
    paddleEnv: {{ json_encode($paddleEnv) }},
    tiers: {{ json_encode($tiers) }}
})" class="pricing-page-wrapper">

    <!-- Background Ambient Radial Glows -->
    <div class="pricing-ambient-top"></div>
    <div class="pricing-ambient-bottom"></div>

    <div class="pricing-container">

        <!-- Header -->
        <div class="pricing-header">
            <div class="pricing-pill-badge">
                <span class="pricing-pill-dot"></span>
                <span>Studio Plans & Subscriptions</span>
            </div>

            <h1 class="pricing-main-title">
                Transparent pricing for <br>
                <span class="pricing-gradient-text">
                    limitless creative power
                </span>
            </h1>

            <p class="pricing-subtitle">
                Generate photorealistic 2MP cinematic imagery and high-definition 24 FPS videos with ultra-fast GPU queues.
            </p>
        </div>

        <!-- Monthly / Yearly Billing Toggle Switch -->
        <div class="pricing-toggle-container">
            <div class="pricing-toggle-pill">
                <!-- Monthly Option -->
                <button type="button"
                        @click="toggleBillingCycle('month')"
                        class="pricing-toggle-btn"
                        :class="{ 'active': billingCycle === 'month' }">
                    Monthly Billing
                </button>

                <!-- Yearly Option -->
                <button type="button"
                        @click="toggleBillingCycle('year')"
                        class="pricing-toggle-btn"
                        :class="{ 'active': billingCycle === 'year' }">
                    <span>Yearly Billing</span>
                    <span class="pricing-discount-badge">-20%</span>
                </button>
            </div>
        </div>

        <!-- Dynamic Pricing Grid -->
        <div class="pricing-grid">
            <template x-for="tier in tiers" :key="tier.name">
                <div class="pricing-card" :class="{ 'popular': tier.isPopular }">

                    <!-- Custom Badge / Popular Badge -->
                    <template x-if="tier.badge || tier.isPopular">
                        <div class="pricing-badge-wrapper">
                            <span class="pricing-badge-pill">
                                <i data-lucide="sparkles" style="width: 12px; height: 12px;"></i>
                                <span x-text="tier.badge || 'Studio Choice'"></span>
                            </span>
                        </div>
                    </template>

                    <div>
                        <!-- Tier Name & Tagline -->
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <h2 class="pricing-tier-name" x-text="tier.name"></h2>
                        </div>

                        <p class="pricing-tier-desc" x-text="tier.description"></p>

                        <!-- Price Container -->
                        <div class="pricing-price-wrap">
                            <div class="pricing-price-row">
                                <span class="pricing-price-val" x-text="getPriceForTier(tier)"></span>
                                <span class="pricing-price-cycle" x-text="'/' + (billingCycle === 'month' ? 'month' : 'year')"></span>
                            </div>

                            <div class="pricing-price-hint">
                                <span x-show="billingCycle === 'year'">Billed annually • Includes 20% discount</span>
                                <span x-show="billingCycle === 'month'">Billed monthly • Cancel anytime</span>
                            </div>
                        </div>

                        <div class="pricing-card-divider"></div>

                        <!-- Feature List -->
                        <div class="pricing-features-wrap">
                            <div class="pricing-features-heading">
                                What's included:
                            </div>
                            <template x-for="feat in tier.features" :key="feat">
                                <div class="pricing-feature-row">
                                    <div class="pricing-feature-check" :class="tier.name === 'Pro' ? 'icon-pro' : 'icon-default'">
                                        <i data-lucide="check" style="width: 12px; height: 12px;"></i>
                                    </div>
                                    <span class="pricing-feature-text" x-text="feat"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Action Button: Open Paddle Checkout -->
                    <div>
                        <button type="button"
                                @click="subscribe(tier)"
                                class="pricing-cta-btn"
                                :class="tier.name === 'Pro' ? 'btn-pro' : 'btn-default'">
                            <span>Subscribe to <span x-text="tier.name"></span></span>
                            <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>

                </div>
            </template>
        </div>

        <!-- Trust Badges Footer -->
        <div class="pricing-trust-footer">
            <div class="pricing-trust-item">
                <i data-lucide="shield-check" style="width: 24px; height: 24px; color: #a855f7; margin-bottom: 10px;"></i>
                <div class="pricing-trust-title">Studio-Grade Performance</div>
                <div class="pricing-trust-desc">High-speed GPU clusters delivering ultra-fast generations and dedicated pipeline queues.</div>
            </div>

            <div class="pricing-trust-item">
                <i data-lucide="zap" style="width: 24px; height: 24px; color: #38bdf8; margin-bottom: 10px;"></i>
                <div class="pricing-trust-title">Instant Activation</div>
                <div class="pricing-trust-desc">Credits and priority generation quotas unlock immediately upon account setup.</div>
            </div>

            <div class="pricing-trust-item">
                <i data-lucide="refresh-cw" style="width: 24px; height: 24px; color: #34d399; margin-bottom: 10px;"></i>
                <div class="pricing-trust-title">Flexible Tiers</div>
                <div class="pricing-trust-desc">Switch between tiers or billing cycles anytime to match your creative needs.</div>
            </div>
        </div>

    </div>

    <!-- Post-Purchase Success Modal Overlay -->
    <div class="modal-backdrop" x-show="checkoutSuccess" x-cloak style="position: fixed; inset: 0; background: rgba(3, 7, 18, 0.88); backdrop-filter: blur(16px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div style="max-width: 480px; width: 100%; background: #0f172a; border: 1px solid rgba(168, 85, 247, 0.4); box-shadow: 0 0 50px rgba(168, 85, 247, 0.25); border-radius: 24px; padding: 36px 28px; text-align: center; position: relative;">
            <div style="width: 68px; height: 68px; border-radius: 50%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 0 30px rgba(16, 185, 129, 0.5);">
                <svg style="width: 34px; height: 34px; color: #ffffff;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>

            <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 9999px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">
                Payment Successful
            </div>

            <h2 style="font-size: 1.75rem; font-weight: 800; color: #ffffff; margin-bottom: 8px; letter-spacing: -0.02em;">
                Subscription Activated!
            </h2>

            <p style="color: #94a3b8; font-size: 0.95rem; line-height: 1.5; margin-bottom: 24px;">
                Your subscription is confirmed and your AI generation credits are now available in your studio account.
            </p>

            <div style="margin-bottom: 24px;">
                <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px; border-radius: 9999px; background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.3); color: #c084fc; font-size: 0.85rem; font-weight: 600;">
                    <span>Returning to Dashboard in <strong x-text="redirectCountdown" style="color: #ffffff; font-weight: 800; font-size: 0.95rem;">3</strong>s...</span>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="{{ route('tools.overview') }}" class="btn-primary" style="padding: 14px 28px; border-radius: 14px; font-size: 1rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; background: linear-gradient(135deg, #7c3aed 0%, #a855f7 50%, #c026d3 100%); color: #ffffff; box-shadow: 0 4px 20px rgba(168, 85, 247, 0.45); transition: all 0.2s ease;">
                    <span>Continue to Dashboard</span>
                    <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
                </a>
                <a href="{{ route('tools.index') }}" style="color: #94a3b8; font-size: 0.88rem; text-decoration: none; padding: 8px; font-weight: 500;">
                    Go to Image Studio
                </a>
            </div>
        </div>
    </div>

</div>

<script>
function pricingManager(config) {
    return {
        userEmail: config.userEmail,
        userId: config.userId,
        isLoggedIn: config.isLoggedIn,
        paddleClientToken: config.paddleClientToken,
        paddleEnv: config.paddleEnv || 'sandbox',
        tiers: config.tiers,
        billingCycle: 'month', // 'month' or 'year'
        paddleInitialized: false,
        checkoutSuccess: false,
        redirectCountdown: 3,
        redirectTimer: null,

        init() {
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });

            this.initPaddle();
        },

        initPaddle() {
            if (this.paddleInitialized) return;

            if (typeof Paddle !== 'undefined' && this.paddleClientToken) {
                try {
                    // Sandbox environment must be set before Initialize
                    if (this.paddleEnv === 'sandbox') {
                        Paddle.Environment.set('sandbox');
                    }
                    Paddle.Initialize({
                        token: this.paddleClientToken,
                        eventCallback: (event) => {
                            console.log('[Paddle Event]', event);
                            if (event && (event.name === 'checkout.completed' || event.name === 'transaction.completed')) {
                                this.handleCheckoutSuccess();
                            }
                        }
                    });
                    this.paddleInitialized = true;
                } catch (e) {
                    console.error('Failed to initialize Paddle:', e);
                }
            }
        },

        handleCheckoutSuccess() {
            this.checkoutSuccess = true;
            this.redirectCountdown = 3;
            if (this.redirectTimer) clearInterval(this.redirectTimer);
            this.redirectTimer = setInterval(() => {
                this.redirectCountdown--;
                if (this.redirectCountdown <= 0) {
                    clearInterval(this.redirectTimer);
                    window.location.href = "{{ route('tools.overview') }}";
                }
            }, 1000);
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        toggleBillingCycle(cycle) {
            this.billingCycle = cycle;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        getPriceForTier(tier) {
            return this.billingCycle === 'month' 
                ? (tier.monthly_price || '$0') 
                : (tier.yearly_price || '$0');
        },

        getPriceIdForTier(tier) {
            return this.billingCycle === 'month'
                ? tier.monthly_price_id
                : tier.yearly_price_id;
        },

        subscribe(tier) {
            // If user is not logged in, prompt sign-in / registration
            if (!this.isLoggedIn) {
                // Open application Supabase authentication modal
                window.dispatchEvent(new CustomEvent('open-auth-modal', { detail: { tab: 'signup' } }));
                const modal = document.querySelector('.modal-backdrop');
                if (modal) {
                    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
                }
                alert('Please sign in or create an account before subscribing.');
                return;
            }

            const priceId = this.getPriceIdForTier(tier);
            if (!priceId) {
                alert('Paddle Price ID has not been configured for this plan yet. Please configure it in the Admin Dashboard.');
                return;
            }

            if (typeof Paddle === 'undefined') {
                alert('Payment system is loading. Please refresh and try again.');
                return;
            }

            if (!this.paddleInitialized) {
                this.initPaddle();
            }

            // Open Paddle Billing Checkout safely
            const checkoutOptions = {
                items: [{
                    priceId: priceId,
                    quantity: 1
                }],
                settings: {
                    successUrl: "{{ route('welcome') }}"
                }
            };

            if (this.userEmail) {
                checkoutOptions.customer = {
                    email: this.userEmail
                };
            }

            if (this.userId) {
                checkoutOptions.customData = {
                    user_id: String(this.userId)
                };
            }

            console.log('[Paddle Checkout] Opening checkout with priceId:', priceId, 'Environment:', this.paddleEnv);
            Paddle.Checkout.open(checkoutOptions);
        }
    };
}
</script>
@endsection
