@extends('admin.layouts.app')

@section('title', 'Supabase Auth Integration')
@section('breadcrumb', 'System / Supabase Auth')

@section('content')
<div x-data="supabaseManager()" style="display: flex; flex-direction: column; gap: 28px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Supabase Authentication & Identity
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Cloud identity provider handling user registration, JWT sessions, and Google OAuth.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <button type="button" class="btn-admin btn-admin-secondary" @click="testConnection()" :disabled="testing">
                <template x-if="!testing">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="shield-check" style="width: 15px; height: 15px;"></i>
                        <span>Test Supabase Auth</span>
                    </span>
                </template>
                <template x-if="testing">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="loader-2" style="width: 15px; height: 15px; animation: spin 1s linear infinite;"></i>
                        <span>Connecting to Supabase...</span>
                    </span>
                </template>
            </button>

            <button type="submit" form="supabase-form" class="btn-admin btn-admin-primary">
                <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                <span>Save Supabase Settings</span>
            </button>
        </div>
    </div>

    <!-- Live Test Result Banner -->
    <template x-if="testResult">
        <div :style="testResult.success ? 'background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #34d399;' : 'background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171;'" style="padding: 14px 18px; border-radius: 10px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i :data-lucide="testResult.success ? 'check-circle-2' : 'alert-circle'" style="width: 20px; height: 20px;"></i>
                <div>
                    <span style="font-weight: 600;" x-text="testResult.message"></span>
                    <span style="font-size: 0.8rem; opacity: 0.8; margin-left: 8px;" x-text="'(Latency: ' + testResult.latency_ms + 'ms)'"></span>
                </div>
            </div>
            <button type="button" @click="testResult = null" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 1.2rem;">&times;</button>
        </div>
    </template>

    <!-- Stats -->
    <div class="stats-grid" style="margin-bottom: 0;">
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Synced MySQL Users</span>
                <span class="stat-value">{{ number_format($syncedUsersCount) }}</span>
                <span class="stat-hint">Active local accounts</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #38bdf8; background: rgba(6, 182, 212, 0.12); border-color: rgba(6, 182, 212, 0.25);">
                <i data-lucide="users"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Administrators</span>
                <span class="stat-value">{{ number_format($adminsCount) }}</span>
                <span class="stat-hint">role = admin in MySQL</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #818cf8; background: rgba(99, 102, 241, 0.12); border-color: rgba(99, 102, 241, 0.25);">
                <i data-lucide="shield-check"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Auth Sync Mode</span>
                <span class="stat-value" style="font-size: 1.25rem; font-weight: 700; color: #34d399;">Hybrid JWT</span>
                <span class="stat-hint">Supabase JWT &rarr; MySQL Sync</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #34d399; background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.25);">
                <i data-lucide="refresh-cw"></i>
            </div>
        </div>
    </div>

    <!-- Configuration Form -->
    <form id="supabase-form" method="POST" action="{{ route('admin.supabase.update') }}">
        @csrf
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="database" style="width: 18px; height: 18px; color: var(--admin-emerald);"></i>
                        <span>Supabase Infrastructure Parameters</span>
                    </h2>
                    <p class="admin-card-subtitle">Edit and save your project URL, anon key, and service role key</p>
                </div>

                <div>
                    @if ($isConfigured)
                        <span class="badge badge-success">
                            <i data-lucide="check" style="width: 12px; height: 12px;"></i> Connected
                        </span>
                    @else
                        <span class="badge badge-danger">
                            <i data-lucide="alert-circle" style="width: 12px; height: 12px;"></i> Incomplete Setup
                        </span>
                    @endif
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 18px;">
                <!-- Project URL -->
                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        SUPABASE_URL (Project REST & Auth Gateway) <span style="color: #f87171;">*</span>
                    </label>
                    <input type="text" name="supabase_url" value="{{ old('supabase_url', $url) }}" placeholder="https://<project-id>.supabase.co" required class="admin-input" style="width: 100%; box-sizing: border-box; font-family: 'JetBrains Mono', monospace; font-size: 0.88rem; color: var(--admin-cyan);">
                    <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Found in your Supabase Project Settings &rarr; API &rarr; Project URL.</span>
                </div>

                <!-- Anon Public Key -->
                <div x-data="{ showAnon: false }">
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        SUPABASE_ANON_KEY (Public Client Token) <span style="color: #f87171;">*</span>
                    </label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input :type="showAnon ? 'text' : 'password'" name="supabase_anon_key" value="{{ old('supabase_anon_key', $anonKey) }}" placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." required class="admin-input" style="flex: 1; font-family: 'JetBrains Mono', monospace; font-size: 0.86rem; color: #f8fafc;">
                        <button type="button" @click="showAnon = !showAnon" class="btn-admin btn-admin-secondary" style="padding: 10px 14px;">
                            <i :data-lucide="showAnon ? 'eye-off' : 'eye'" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>
                    <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Used for client-side authentication in browser scripts and user registration.</span>
                </div>

                <!-- Service Role Secret Key -->
                <div x-data="{ showService: false }">
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        SUPABASE_SERVICE_ROLE_KEY (Privileged Admin Token)
                    </label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input :type="showService ? 'text' : 'password'" name="supabase_service_role_key" value="{{ old('supabase_service_role_key', $serviceKey) }}" placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..." class="admin-input" style="flex: 1; font-family: 'JetBrains Mono', monospace; font-size: 0.86rem; color: #f8fafc;">
                        <button type="button" @click="showService = !showService" class="btn-admin btn-admin-secondary" style="padding: 10px 14px;">
                            <i :data-lucide="showService ? 'eye-off' : 'eye'" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>
                    <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Enables server-side user lookup, automated role elevation, and admin overrides.</span>
                </div>

                <div style="display: flex; justify-content: flex-end; padding-top: 12px; border-top: 1px solid var(--admin-border);">
                    <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
                        <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                        <span>Save & Apply Supabase Settings</span>
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>

<script>
function supabaseManager() {
    return {
        testing: false,
        testResult: null,
        async testConnection() {
            this.testing = true;
            this.testResult = null;
            try {
                const res = await fetch("{{ route('admin.supabase.test') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                this.testResult = await res.json();
            } catch (e) {
                this.testResult = {
                    success: false,
                    message: 'Network error communicating with server: ' + e.message,
                    latency_ms: 0
                };
            } finally {
                this.testing = false;
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            }
        }
    };
}
</script>
@endsection
