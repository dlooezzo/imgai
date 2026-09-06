@extends('admin.layouts.app')

@section('title', 'Cloudflare R2 Storage Settings')
@section('breadcrumb', 'Storage / Cloudflare R2')

@section('content')
<div x-data="r2Manager()" style="display: flex; flex-direction: column; gap: 28px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Cloudflare R2 Object Storage
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                High-performance S3-compatible cloud storage for persistent video and image media assets.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <button type="button" class="btn-admin btn-admin-secondary" @click="testConnection()" :disabled="testing">
                <template x-if="!testing">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="refresh-cw" style="width: 15px; height: 15px;"></i>
                        <span>Test R2 Connection</span>
                    </span>
                </template>
                <template x-if="testing">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="loader-2" style="width: 15px; height: 15px; animation: spin 1s linear infinite;"></i>
                        <span>Probing Gateway...</span>
                    </span>
                </template>
            </button>

            <button type="submit" form="r2-settings-form" class="btn-admin btn-admin-primary">
                <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                <span>Save R2 Settings</span>
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

    <!-- Usage Metrics -->
    <div class="stats-grid" style="margin-bottom: 0;">
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Total R2 Media Files</span>
                <span class="stat-value">{{ number_format($totalR2Files) }}</span>
                <span class="stat-hint">Active objects in bucket</span>
            </div>
            <div class="stat-icon-wrapper" style="color: var(--admin-cyan); background: rgba(6, 182, 212, 0.12); border-color: rgba(6, 182, 212, 0.25);">
                <i data-lucide="folder-archive"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Stored Videos</span>
                <span class="stat-value">{{ number_format($r2VideoCount) }}</span>
                <span class="stat-hint">Hunyuan & Wan 2.2 MP4s</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #c084fc; background: rgba(139, 92, 246, 0.12); border-color: rgba(139, 92, 246, 0.25);">
                <i data-lucide="film"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Source Images</span>
                <span class="stat-value">{{ number_format($r2SourceImageCount) }}</span>
                <span class="stat-hint">Uploaded for Image-to-Video</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #34d399; background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.25);">
                <i data-lucide="image"></i>
            </div>
        </div>
    </div>

    <!-- Configuration Form Card -->
    <form id="r2-settings-form" method="POST" action="{{ route('admin.storage.update') }}">
        @csrf
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title">
                        <i data-lucide="hard-drive" style="width: 18px; height: 18px; color: var(--admin-primary);"></i>
                        <span>Cloudflare R2 Parameters & Endpoints</span>
                    </h2>
                    <p class="admin-card-subtitle">Modify and save your bucket, endpoint, and access credentials dynamically</p>
                </div>

                <div>
                    @if ($isConfigured)
                        <span class="badge badge-success">
                            <i data-lucide="check" style="width: 12px; height: 12px;"></i> Configured
                        </span>
                    @else
                        <span class="badge badge-warning">
                            <i data-lucide="alert-triangle" style="width: 12px; height: 12px;"></i> Needs Configuration
                        </span>
                    @endif
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 18px;">
                <!-- Bucket Name -->
                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        R2 Bucket Name <span style="color: #f87171;">*</span>
                    </label>
                    <input type="text" name="r2_bucket" value="{{ old('r2_bucket', $bucket) }}" placeholder="e.g. imgai-media" required class="admin-input" style="width: 100%; box-sizing: border-box; font-family: 'JetBrains Mono', monospace; font-size: 0.88rem;">
                    <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">The name of the target Cloudflare R2 bucket.</span>
                </div>

                <!-- Endpoint URL -->
                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        R2 S3 API Endpoint <span style="color: #f87171;">*</span>
                    </label>
                    <input type="text" name="r2_endpoint" value="{{ old('r2_endpoint', $endpoint) }}" placeholder="https://<account_id>.r2.cloudflarestorage.com" required class="admin-input" style="width: 100%; box-sizing: border-box; font-family: 'JetBrains Mono', monospace; font-size: 0.88rem; color: var(--admin-cyan);">
                    <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Your Cloudflare Account S3 Gateway URL found in the Cloudflare R2 dashboard.</span>
                </div>

                <!-- Public URL / CDN -->
                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        R2 Public Access Domain / CDN URL (Optional)
                    </label>
                    <input type="text" name="r2_public_url" value="{{ old('r2_public_url', $publicUrl) }}" placeholder="https://media.yourdomain.com or https://pub-<id>.r2.dev" class="admin-input" style="width: 100%; box-sizing: border-box; font-family: 'JetBrains Mono', monospace; font-size: 0.88rem;">
                    <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Custom domain or public r2.dev URL for serving assets directly to web clients.</span>
                </div>

                <!-- Region -->
                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        R2 Storage Region
                    </label>
                    <input type="text" name="r2_region" value="{{ old('r2_region', $region) }}" placeholder="auto" class="admin-input" style="width: 100%; box-sizing: border-box; font-family: 'JetBrains Mono', monospace; font-size: 0.88rem;">
                    <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Default is <code>auto</code>. Cloudflare R2 automatically routes to the closest region.</span>
                </div>

                <!-- Access Key ID -->
                <div>
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        R2 Access Key ID <span style="color: #f87171;">*</span>
                    </label>
                    <input type="text" name="r2_access_key_id" value="{{ old('r2_access_key_id', $accessKeyId) }}" placeholder="e.g. 7f8a9b0c..." class="admin-input" style="width: 100%; box-sizing: border-box; font-family: 'JetBrains Mono', monospace; font-size: 0.88rem;">
                    <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Generated under R2 &rarr; Manage R2 API Tokens in Cloudflare.</span>
                </div>

                <!-- Secret Access Key -->
                <div x-data="{ showSecret: false }">
                    <label style="display: block; font-size: 0.84rem; font-weight: 700; color: #f8fafc; margin-bottom: 6px;">
                        R2 Secret Access Key <span style="color: #f87171;">*</span>
                    </label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input :type="showSecret ? 'text' : 'password'" name="r2_secret_access_key" value="{{ old('r2_secret_access_key', $secretAccessKey) }}" placeholder="Secret token key..." class="admin-input" style="flex: 1; font-family: 'JetBrains Mono', monospace; font-size: 0.88rem;">
                        <button type="button" @click="showSecret = !showSecret" class="btn-admin btn-admin-secondary" style="padding: 10px 14px;">
                            <i :data-lucide="showSecret ? 'eye-off' : 'eye'" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>
                    <span style="font-size: 0.74rem; color: #64748b; margin-top: 4px; display: block;">Stored encrypted in database / environment.</span>
                </div>

                <div style="display: flex; justify-content: flex-end; padding-top: 12px; border-top: 1px solid var(--admin-border);">
                    <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
                        <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                        <span>Save & Apply R2 Configuration</span>
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>

<script>
function r2Manager() {
    return {
        testing: false,
        testResult: null,
        async testConnection() {
            this.testing = true;
            this.testResult = null;
            try {
                const res = await fetch("{{ route('admin.storage.test') }}", {
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
