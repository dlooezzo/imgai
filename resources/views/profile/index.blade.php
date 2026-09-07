@extends('layouts.app')

@section('content')
<!-- Left Navigation Sidebar -->
@include('layouts.sidebar')

<!-- Right Tool Workspace: User Dashboard & Profile -->
<main class="app-workspace" style="max-width: 1280px; margin: 0 auto; width: 100%;">
    <!-- Profile Hero Card (Glassmorphism Dark SaaS) -->
    <div class="profile-hero-card">
        <div class="profile-hero-content">
            <div class="profile-avatar-large">
                <span x-text="profileName ? profileName.charAt(0).toUpperCase() : (user && user.email ? user.email.charAt(0).toUpperCase() : 'U')"></span>
                <div class="avatar-status-dot" title="Account Active & Connected"></div>
            </div>

            <div class="profile-hero-meta">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h1 class="profile-hero-name" x-text="profileName || (user && user.email ? user.email.split('@')[0] : 'AI Studio Creator')"></h1>
                    <span class="badge-creator-pro">
                        <i data-lucide="sparkles" style="width: 12px; height: 12px;"></i>
                        <span>Pro Creator</span>
                    </span>
                </div>

                <p class="profile-hero-email" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="mail" style="width: 14px; height: 14px; color: var(--text-muted);"></i>
                    <span x-text="profileEmail || (user ? user.email : 'creator@studiolocal.ai')"></span>
                    <span style="display: inline-flex; align-items: center; gap: 3px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; font-size: 0.72rem; font-weight: 700; padding: 2px 7px; border-radius: 999px; margin-left: 4px;">
                        <i data-lucide="check-circle-2" style="width: 11px; height: 11px;"></i>
                        <span>Verified</span>
                    </span>
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Stats Telemetry Grid (4 Columns) -->
    <div class="dashboard-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
        <div class="stat-card stat-amber" style="border-color: rgba(245, 158, 11, 0.35); background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.03));">
            <div class="stat-icon-wrap" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24;">
                <i data-lucide="zap" style="width: 24px; height: 24px; fill: #fbbf24;"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label" style="color: #fbbf24; font-weight: 700;">Available Credits</div>
                <div class="stat-value" style="color: #fff;" x-text="user && user.credit_balance !== undefined ? Number(user.credit_balance).toLocaleString() : '{{ Auth::check() ? number_format(Auth::user()->credit_balance) : 0 }}'">
                    {{ Auth::check() ? number_format(Auth::user()->credit_balance) : 0 }}
                </div>
                <div class="stat-subtext" style="display: flex; align-items: center; justify-content: space-between; margin-top: 4px;">
                    <span>Live RDS Balance</span>
                    <a href="{{ route('pricing') }}" style="color: #fbbf24; text-decoration: none; font-weight: 600; font-size: 0.76rem; border-bottom: 1px dotted #fbbf24;">Add Credits &rarr;</a>
                </div>
            </div>
        </div>

        <div class="stat-card stat-indigo">
            <div class="stat-icon-wrap" style="background: rgba(99, 102, 241, 0.15); color: var(--brand-primary);">
                <i data-lucide="layers" style="width: 24px; height: 24px;"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Creations</div>
                <div class="stat-value" x-text="stats.total_creations"></div>
                <div class="stat-subtext">Synthesized Media Assets</div>
            </div>
        </div>

        <div class="stat-card stat-cyan">
            <div class="stat-icon-wrap" style="background: rgba(6, 182, 212, 0.15); color: var(--brand-cyan);">
                <i data-lucide="image" style="width: 24px; height: 24px;"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Images Synthesized</div>
                <div class="stat-value" x-text="stats.total_images"></div>
                <div class="stat-subtext">Wan 2.2 2MP High-Res</div>
            </div>
        </div>

        <div class="stat-card stat-purple">
            <div class="stat-icon-wrap" style="background: rgba(168, 85, 247, 0.15); color: #c084fc;">
                <i data-lucide="video" style="width: 24px; height: 24px;"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Videos Rendered</div>
                <div class="stat-value" x-text="stats.total_videos"></div>
                <div class="stat-subtext">Hunyuan Video Engine</div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs Bar (Dock / Segmented Pill Style) -->
    <div class="dashboard-tabs-bar">
        <button type="button" class="dash-tab-btn" :class="{ 'active': activeTab === 'profile' }" @click="activeTab = 'profile'">
            <i data-lucide="user" style="width: 16px; height: 16px;"></i>
            <span>Profile & Account</span>
        </button>

        <button type="button" class="dash-tab-btn" :class="{ 'active': activeTab === 'security' }" @click="activeTab = 'security'">
            <i data-lucide="lock" style="width: 16px; height: 16px;"></i>
            <span>Security & 2FA</span>
        </button>

        <button type="button" class="dash-tab-btn" :class="{ 'active': activeTab === 'settings' }" @click="activeTab = 'settings'">
            <i data-lucide="sliders" style="width: 16px; height: 16px;"></i>
            <span>Account Settings</span>
        </button>
    </div>

    <!-- TAB 1: Profile & Account Information -->
    <div x-show="activeTab === 'profile'" x-transition>
        <div class="dashboard-grid-2col">
            <!-- Left: Personal Profile Form Card -->
            <div class="dashboard-section-card">
                <div class="section-card-header">
                    <div>
                        <h2 class="section-title">Personal Profile Information</h2>
                        <p class="section-subtitle">Update your personal details and public creator display alias.</p>
                    </div>
                    <div class="stat-icon-wrap" style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.15); color: var(--brand-primary);">
                        <i data-lucide="user-check" style="width: 20px; height: 20px;"></i>
                    </div>
                </div>

                <form @submit.prevent="saveProfileInfo()" class="dash-form">
                    <div class="form-group">
                        <label class="form-label">Full Name / Creator Alias</label>
                        <div class="input-with-icon-wrap">
                            <span class="input-icon-prefix">
                                <i data-lucide="user" style="width: 17px; height: 17px;"></i>
                            </span>
                            <input type="text" class="form-input form-input-with-icon" x-model="profileName" placeholder="e.g. Elena Rostova" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <label class="form-label" style="margin-bottom: 0;">Email Address</label>
                            <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; font-size: 0.74rem; font-weight: 700; padding: 2px 8px; border-radius: 999px;">
                                <i data-lucide="check-circle-2" style="width: 12px; height: 12px;"></i>
                                <span>Verified</span>
                            </span>
                        </div>
                        <div class="input-with-icon-wrap">
                            <span class="input-icon-prefix">
                                <i data-lucide="mail" style="width: 17px; height: 17px;"></i>
                            </span>
                            <input type="email" class="form-input form-input-with-icon" x-model="profileEmail" placeholder="creator@example.com" required>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
                        <button type="submit" class="btn-action btn-action-primary" :disabled="isSavingProfile">
                            <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                            <span x-text="isSavingProfile ? 'Saving...' : 'Save Profile Changes'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right: Studio Membership & Session Details Card -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div class="dashboard-section-card">
                    <div class="section-card-header">
                        <div>
                            <h2 class="section-title">Studio Creator Overview</h2>
                            <p class="section-subtitle">Active subscription privileges and generation capabilities.</p>
                        </div>
                        <i data-lucide="sparkles" style="color: #c084fc; width: 22px; height: 22px;"></i>
                    </div>

                    <div class="studio-meta-card">
                        <div class="studio-meta-row">
                            <div class="studio-meta-label">
                                <i data-lucide="award" style="width: 16px; height: 16px; color: #c084fc;"></i>
                                <span>Creator Plan Tier</span>
                            </div>
                            <div class="studio-meta-val">
                                <span class="badge-creator-pro">Studio Member</span>
                            </div>
                        </div>

                        <div class="studio-meta-row">
                            <div class="studio-meta-label">
                                <i data-lucide="image" style="width: 16px; height: 16px; color: var(--brand-cyan);"></i>
                                <span>Image Synthesis Engine</span>
                            </div>
                            <div class="studio-meta-val">Wan 2.2 (2MP Native)</div>
                        </div>

                        <div class="studio-meta-row">
                            <div class="studio-meta-label">
                                <i data-lucide="video" style="width: 16px; height: 16px; color: var(--brand-primary);"></i>
                                <span>Video Synthesis Engine</span>
                            </div>
                            <div class="studio-meta-val">Tencent Hunyuan (720p/1080p)</div>
                        </div>

                        <div class="studio-meta-row">
                            <div class="studio-meta-label">
                                <i data-lucide="database" style="width: 16px; height: 16px; color: #34d399;"></i>
                                <span>Storage Retention</span>
                            </div>
                            <div class="studio-meta-val">Cloudflare R2 & SSD Cache</div>
                        </div>

                        <div class="studio-meta-row">
                            <div class="studio-meta-label">
                                <i data-lucide="shield" style="width: 16px; height: 16px; color: #60a5fa;"></i>
                                <span>Session Status</span>
                            </div>
                            <div class="studio-meta-val" style="color: #34d399; font-size: 0.8rem; font-weight: 700;">● Active Studio Session</div>
                        </div>
                    </div>
                </div>

                <!-- Subscription & Plan Card -->
                <div class="dashboard-section-card">
                    <div class="section-card-header">
                        <div>
                            <h2 class="section-title">Subscription & Plan</h2>
                            <p class="section-subtitle">Manage your studio creation tier and capabilities.</p>
                        </div>
                        <div class="stat-icon-wrap" style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.15); color: #818cf8;">
                            <i data-lucide="tag" style="width: 20px; height: 20px;"></i>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <div style="padding: 12px 14px; border-radius: 10px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08);">
                            <div style="font-size: 0.88rem; font-weight: 600; color: var(--text-primary);">
                                Studio Access
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                Explore available subscription plans to unlock high-resolution renders and priority GPU queues.
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <a href="{{ route('pricing') }}" class="btn-action btn-action-primary" style="flex: 1; text-decoration: none; justify-content: center; gap: 8px;">
                                <i data-lucide="sparkles" style="width: 15px; height: 15px;"></i>
                                <span>View Pricing Plans</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: Security & Password Settings -->
    <div x-show="activeTab === 'security'" x-transition>
        <div class="dashboard-grid-2col">
            <!-- Password Update Card -->
            <div class="dashboard-section-card">
                <div class="section-card-header">
                    <div>
                        <h2 class="section-title">Change Password</h2>
                        <p class="section-subtitle">Ensure your account is using a secure, strong password.</p>
                    </div>
                    <div class="stat-icon-wrap" style="width: 40px; height: 40px; background: rgba(99, 102, 241, 0.15); color: var(--brand-primary);">
                        <i data-lucide="key" style="width: 20px; height: 20px;"></i>
                    </div>
                </div>

                <form @submit.prevent="savePassword()" class="dash-form">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <div class="input-with-icon-wrap">
                            <span class="input-icon-prefix">
                                <i data-lucide="lock" style="width: 17px; height: 17px;"></i>
                            </span>
                            <input :type="showCurrentPassword ? 'text' : 'password'" class="form-input form-input-with-icon" x-model="passwordForm.current" placeholder="••••••••">
                            <button type="button" class="input-icon-suffix" @click="showCurrentPassword = !showCurrentPassword" :title="showCurrentPassword ? 'Hide password' : 'Show password'">
                                <i data-lucide="eye" style="width: 16px; height: 16px;" x-show="!showCurrentPassword"></i>
                                <i data-lucide="eye-off" style="width: 16px; height: 16px;" x-show="showCurrentPassword"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="input-with-icon-wrap">
                            <span class="input-icon-prefix">
                                <i data-lucide="shield-alert" style="width: 17px; height: 17px;"></i>
                            </span>
                            <input :type="showPassword ? 'text' : 'password'" class="form-input form-input-with-icon" x-model="passwordForm.password" placeholder="At least 6 characters" required>
                            <button type="button" class="input-icon-suffix" @click="showPassword = !showPassword" :title="showPassword ? 'Hide password' : 'Show password'">
                                <i data-lucide="eye" style="width: 16px; height: 16px;" x-show="!showPassword"></i>
                                <i data-lucide="eye-off" style="width: 16px; height: 16px;" x-show="showPassword"></i>
                            </button>
                        </div>

                        <!-- Real-time Password Strength Meter -->
                        <template x-if="passwordForm.password.length > 0">
                            <div class="pwd-meter-wrap">
                                <div class="pwd-meter-header">
                                    <span style="color: var(--text-muted);">Password Strength</span>
                                    <span :style="{ color: passwordStrength.color, fontWeight: '700' }" x-text="passwordStrength.label"></span>
                                </div>
                                <div class="pwd-meter-track">
                                    <div class="pwd-meter-fill" :style="{ width: passwordStrength.percent + '%', backgroundColor: passwordStrength.color }"></div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-with-icon-wrap">
                            <span class="input-icon-prefix">
                                <i data-lucide="shield-check" style="width: 17px; height: 17px;"></i>
                            </span>
                            <input :type="showConfirmPassword ? 'text' : 'password'" class="form-input form-input-with-icon" x-model="passwordForm.password_confirmation" placeholder="Repeat new password" required>
                            <button type="button" class="input-icon-suffix" @click="showConfirmPassword = !showConfirmPassword" :title="showConfirmPassword ? 'Hide password' : 'Show password'">
                                <i data-lucide="eye" style="width: 16px; height: 16px;" x-show="!showConfirmPassword"></i>
                                <i data-lucide="eye-off" style="width: 16px; height: 16px;" x-show="showConfirmPassword"></i>
                            </button>
                        </div>
                        <template x-if="passwordForm.password && passwordForm.password_confirmation && passwordForm.password !== passwordForm.password_confirmation">
                            <span style="font-size: 0.76rem; color: #f87171; margin-top: 4px; display: block;">Passwords do not match.</span>
                        </template>
                    </div>

                    <button type="submit" class="btn-action btn-action-primary" :disabled="isSavingPassword" style="width: 100%; justify-content: center; margin-top: 8px;">
                        <i data-lucide="lock" style="width: 16px; height: 16px;"></i>
                        <span x-text="isSavingPassword ? 'Updating Password...' : 'Update Password'"></span>
                    </button>
                </form>
            </div>

            <!-- Two-Factor Authentication Card -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div class="dashboard-section-card">
                    <div class="section-card-header">
                        <div>
                            <h2 class="section-title">Two-Factor Authentication (2FA)</h2>
                            <p class="section-subtitle">Add an extra layer of security to protect against unauthorized access.</p>
                        </div>
                        <div class="stat-icon-wrap" style="width: 40px; height: 40px; background: rgba(6, 182, 212, 0.15); color: var(--brand-cyan);">
                            <i data-lucide="shield" style="width: 20px; height: 20px;"></i>
                        </div>
                    </div>

                    <div class="security-toggle-box">
                        <div class="security-toggle-info">
                            <span class="security-toggle-title">Authentication App (TOTP)</span>
                            <span class="security-toggle-desc">Require an authenticator verification code upon signing in from new devices.</span>
                        </div>
                        <label class="switch-toggle">
                            <input type="checkbox" :checked="security.two_factor_enabled" @change="toggle2FA($event.target.checked)">
                            <span class="slider-round"></span>
                        </label>
                    </div>

                    <div class="info-alert-box" style="margin-top: 14px;">
                        <i data-lucide="info" style="width: 16px; height: 16px; flex-shrink: 0; color: var(--brand-cyan);"></i>
                        <span>When enabled, you will be prompted for an authentication code when signing into new browsers.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: Account Settings & Danger Zone -->
    <div x-show="activeTab === 'settings'" x-transition>
        <!-- Generation Preferences Card -->
        <div class="dashboard-section-card">
            <div class="section-card-header">
                <div>
                    <h2 class="section-title">Generation Preferences</h2>
                    <p class="section-subtitle">Customize default studio presets and automated generation behaviors.</p>
                </div>
                <i data-lucide="sliders" style="color: var(--brand-cyan); width: 22px; height: 22px;"></i>
            </div>

            <div class="dash-form">
                <div class="security-toggle-box">
                    <div>
                        <span class="security-toggle-title">Automated Server Storage Cleanup</span>
                        <span class="security-toggle-desc">Automatically purge expired temporary cached media from storage after 24 hours.</span>
                    </div>
                    <span class="badge-val" style="background: rgba(16, 185, 129, 0.15); color: #34d399; font-weight: 700; border: 1px solid rgba(16,185,129,0.3); padding: 4px 10px; border-radius: var(--radius-full); font-size: 0.76rem;">Active (24h Retention)</span>
                </div>

                <div class="security-toggle-box">
                    <div>
                        <span class="security-toggle-title">Auto-Download Generated Media</span>
                        <span class="security-toggle-desc">Prompt browser to initiate download immediately after each generation completes.</span>
                    </div>
                    <label class="switch-toggle">
                        <input type="checkbox" x-model="preferences.auto_download">
                        <span class="slider-round"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Danger Zone Card -->
        <div class="danger-zone-card">
            <div class="section-card-header" style="border-bottom-color: rgba(239, 68, 68, 0.2);">
                <div>
                    <h2 class="section-title" style="color: #f87171;">Danger Zone</h2>
                    <p class="section-subtitle" style="color: rgba(248, 113, 113, 0.8);">Irreversible actions regarding your account creations and active sessions.</p>
                </div>
                <div class="stat-icon-wrap" style="width: 40px; height: 40px; background: rgba(239, 68, 68, 0.15); color: #f87171;">
                    <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="danger-action-row">
                    <div>
                        <div style="font-weight: 700; color: var(--text-primary); font-size: 0.94rem;">Clear All Creation History</div>
                        <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">Permanently removes all synthesized image and video history records.</div>
                    </div>
                    <button type="button" class="btn-action btn-action-danger" @click="clearAllHistory()">
                        <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i>
                        <span>Clear History</span>
                    </button>
                </div>

                <div class="danger-action-row">
                    <div>
                        <div style="font-weight: 700; color: var(--text-primary); font-size: 0.94rem;">Sign Out All Sessions</div>
                        <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">Invalidates the current session token and redirects to the studio portal.</div>
                    </div>
                    <button type="button" class="btn-action btn-action-danger" @click="handleLogout()">
                        <i data-lucide="log-out" style="width: 15px; height: 15px;"></i>
                        <span>Sign Out Now</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
