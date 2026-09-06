@extends('admin.layouts.app')

@section('title', 'API Market Gateway Settings')
@section('breadcrumb', 'AI Engine / API Market')

@section('content')
<div x-data="apiMarketManager()" style="display: flex; flex-direction: column; gap: 28px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                API Market AI Gateway
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Unified AI infrastructure routing image and video generation tasks.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <button type="button" class="btn-admin btn-admin-secondary" @click="testConnection()" :disabled="testing">
                <template x-if="!testing">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="activity" style="width: 15px; height: 15px;"></i>
                        <span>Test Gateway Connection</span>
                    </span>
                </template>
                <template x-if="testing">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="loader-2" style="width: 15px; height: 15px; animation: spin 1s linear infinite;"></i>
                        <span>Contacting API Gateway...</span>
                    </span>
                </template>
            </button>

            <button type="submit" form="api-market-form" class="btn-admin btn-admin-primary">
                <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                <span>Save Gateway Settings</span>
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

    <!-- Main Configuration Form -->
    <form id="api-market-form" method="POST" action="{{ route('admin.api-market.update') }}" style="display: flex; flex-direction: column; gap: 20px;">
        @csrf

        <!-- API Key Card -->
        <div class="admin-card" x-data="{ showKey: false }">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="key-round" style="width: 18px; height: 18px; color: var(--admin-primary);"></i>
                        <span>API Market Gateway Key</span>
                    </h2>
                    <p class="admin-card-subtitle">Master authentication token for all neural prediction endpoints</p>
                </div>

                <div>
                    @if ($isConfigured)
                        <span class="badge badge-success">
                            <i data-lucide="check" style="width: 12px; height: 12px;"></i> Key Configured
                        </span>
                    @else
                        <span class="badge badge-danger">
                            <i data-lucide="x" style="width: 12px; height: 12px;"></i> Key Missing
                        </span>
                    @endif
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                    API_MARKET_KEY <span style="color: #f87171;">*</span>
                </label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input :type="showKey ? 'text' : 'password'" name="api_market_key" value="{{ old('api_market_key', $apiKey) }}" placeholder="e.g. 33df1796-03f4-4a41-8664-..." required class="admin-input" style="flex: 1; font-family: 'JetBrains Mono', monospace; font-size: 0.88rem; color: #f8fafc;">
                    <button type="button" @click="showKey = !showKey" class="btn-admin btn-admin-secondary" style="padding: 10px 14px;">
                        <i :data-lucide="showKey ? 'eye-off' : 'eye'" style="width: 16px; height: 16px;"></i>
                    </button>
                </div>
                <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Obtained from your <a href="https://api.market" target="_blank" style="color: var(--admin-cyan);">API Market</a> dashboard account.</span>
            </div>
        </div>

        <!-- Pipeline Gateway Endpoints Card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="route" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                        <span>Gateway Routing Endpoints</span>
                    </h2>
                    <p class="admin-card-subtitle">Default upstream API targets for each creative generator pipeline</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <!-- Text-to-Image Endpoint -->
                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        Cinematic Text-to-Image Endpoint
                    </label>
                    <input type="text" name="api_market_base_url" value="{{ old('api_market_base_url', $imgUrl) }}" placeholder="https://prod.api.market/api/v1/magicapi/cinematic-text-to-image-generator" class="admin-input" style="width: 100%; box-sizing: border-box; font-family: 'JetBrains Mono', monospace; font-size: 0.86rem; color: var(--admin-cyan);">
                </div>

                <!-- Text-to-Video Endpoint -->
                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        Tencent Hunyuan Video Endpoint
                    </label>
                    <input type="text" name="api_market_video_base_url" value="{{ old('api_market_video_base_url', $vidUrl) }}" placeholder="https://prod.api.market/api/v1/magicapi/hunyuan-video" class="admin-input" style="width: 100%; box-sizing: border-box; font-family: 'JetBrains Mono', monospace; font-size: 0.86rem; color: var(--admin-cyan);">
                </div>

                <!-- Image-to-Video Endpoint -->
                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        Wan 2.2 Image-to-Video Endpoint
                    </label>
                    <input type="text" name="api_market_i2v_base_url" value="{{ old('api_market_i2v_base_url', $i2vUrl) }}" placeholder="https://prod.api.market/api/v1/magicapi/ultra-fast-text-to-image-image-to-video-api" class="admin-input" style="width: 100%; box-sizing: border-box; font-family: 'JetBrains Mono', monospace; font-size: 0.86rem; color: var(--admin-cyan);">
                </div>

                <div style="display: flex; justify-content: flex-end; padding-top: 12px; border-top: 1px solid var(--admin-border);">
                    <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
                        <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                        <span>Save API Gateway Settings</span>
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Connected Pipelines Overview -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">
                    <i data-lucide="cpu" style="width: 18px; height: 18px; color: var(--admin-purple);"></i>
                    <span>Configured Model Pipelines Overview</span>
                </h2>
                <p class="admin-card-subtitle">Active routing states across the system</p>
            </div>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Pipeline Name</th>
                        <th>Pipeline Type</th>
                        <th>Target Gateway Endpoint</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($providers as $provider)
                        <tr>
                            <td style="font-weight: 600; color: #f8fafc;">
                                {{ $provider['name'] }}
                            </td>
                            <td>
                                <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3);">
                                    {{ $provider['type'] }}
                                </span>
                            </td>
                            <td class="font-mono" style="font-size: 0.82rem; color: #94a3b8; word-break: break-all;">
                                {{ $provider['endpoint'] ?: 'Default Upstream' }}
                            </td>
                            <td>
                                @if ($isConfigured)
                                    <span class="badge badge-success">
                                        <i data-lucide="check" style="width: 12px; height: 12px;"></i> Active
                                    </span>
                                @else
                                    <span class="badge badge-danger">
                                        <i data-lucide="alert-circle" style="width: 12px; height: 12px;"></i> Unconfigured
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function apiMarketManager() {
    return {
        testing: false,
        testResult: null,
        async testConnection() {
            this.testing = true;
            this.testResult = null;
            try {
                const res = await fetch("{{ route('admin.api-market.test') }}", {
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
