<!-- Supabase Authentication Modal -->
<div class="modal-backdrop" x-show="authModalOpen" x-cloak @click.self="authModalOpen = false" @keydown.escape.window="authModalOpen = false">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title" x-text="authTab === 'signin' ? 'Welcome Back' : (authTab === 'signup' ? 'Create Account' : 'Reset Password')"></h3>
            <button type="button" class="modal-close" @click="authModalOpen = false">&times;</button>
        </div>

        <div class="modal-body">
            <!-- Auth Navigation Tabs -->
            <div class="auth-tabs">
                <button type="button" class="auth-tab-btn" :class="{ 'active': authTab === 'signin' }" @click="authTab = 'signin'; authMessage = null">Sign In</button>
                <button type="button" class="auth-tab-btn" :class="{ 'active': authTab === 'signup' }" @click="authTab = 'signup'; authMessage = null">Register</button>
                <button type="button" class="auth-tab-btn" :class="{ 'active': authTab === 'forgot' }" @click="authTab = 'forgot'; authMessage = null">Reset</button>
            </div>

            <!-- Google OAuth Button -->
            <button type="button" class="btn-oauth" @click="handleGoogleAuth()">
                <svg width="18" height="18" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.17z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.36 24 12 24z"/>
                    <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.99 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/>
                    <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.36 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                </svg>
                <span>Continue with Google</span>
            </button>

            <div class="divider-text">
                <span>or continue with email</span>
            </div>

            <!-- Error Banner -->
            <template x-if="authMessage">
                <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; padding: 10px 14px; border-radius: 8px; font-size: 0.84rem;" x-text="authMessage"></div>
            </template>

            <!-- Email & Password Form -->
            <form @submit.prevent="handleEmailAuth()" style="display: flex; flex-direction: column; gap: 14px;">
                <template x-if="authTab === 'signup'">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-input" x-model="authFullName" placeholder="e.g. Alex Morgan" required>
                    </div>
                </template>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-input" x-model="authEmail" placeholder="name@example.com" required>
                </div>

                <template x-if="authTab !== 'forgot'">
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-input" x-model="authPassword" placeholder="••••••••" required>
                    </div>
                </template>

                <button type="submit" class="btn-generate" style="padding: 12px; margin-top: 6px;" :disabled="authLoading">
                    <span x-show="!authLoading" x-text="authTab === 'signin' ? 'Sign In' : (authTab === 'signup' ? 'Create Free Account' : 'Send Reset Link')"></span>
                    <span x-show="authLoading">Please wait...</span>
                </button>
            </form>
        </div>
    </div>
</div>
