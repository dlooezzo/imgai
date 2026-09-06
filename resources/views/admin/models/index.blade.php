@extends('admin.layouts.app')

@section('title', 'AI Models Configuration')
@section('breadcrumb', 'AI Engine / AI Models')

@section('content')
<div x-data="modelsManager()" style="display: flex; flex-direction: column; gap: 28px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                AI Generation Models
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Customize and manage endpoints and model version identifiers for all generation pipelines.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="{{ route('admin.api-market.index') }}" class="btn-admin btn-admin-secondary">
                <i data-lucide="key-round" style="width: 15px; height: 15px;"></i>
                <span>API Market Settings</span>
            </a>

            <button type="submit" form="ai-models-form" class="btn-admin btn-admin-primary">
                <i data-lucide="save" style="width: 15px; height: 15px;"></i>
                <span>Save All AI Models</span>
            </button>
        </div>
    </div>

    <!-- Models Form -->
    <form id="ai-models-form" method="POST" action="{{ route('admin.models.update') }}" style="display: flex; flex-direction: column; gap: 20px;">
        @csrf

        @foreach ($models as $key => $model)
            <div class="admin-card">
                <div class="admin-card-header">
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <h2 class="admin-card-title">{{ $model['name'] }}</h2>
                            <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3);">
                                {{ $model['category'] }}
                            </span>
                        </div>
                        <p class="admin-card-subtitle">Output Specs: {{ $model['output_format'] }}</p>
                    </div>

                    <div>
                        <button type="button" class="btn-admin btn-admin-secondary" style="padding: 7px 14px; font-size: 0.8rem;" @click="testModel('{{ $key }}')" :disabled="loadingModel === '{{ $key }}'">
                            <span x-show="loadingModel !== '{{ $key }}'" style="display: flex; align-items: center; gap: 6px;">
                                <i data-lucide="activity" style="width: 14px; height: 14px;"></i>
                                <span>Test Endpoint</span>
                            </span>
                            <span x-show="loadingModel === '{{ $key }}'" style="display: flex; align-items: center; gap: 6px;">
                                <i data-lucide="loader-2" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i>
                                <span>Testing...</span>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Live Test Result Banner for this model -->
                <template x-if="results['{{ $key }}']">
                    <div :style="results['{{ $key }}'].success ? 'background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399;' : 'background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171;'" style="padding: 10px 14px; border-radius: 8px; font-size: 0.84rem; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-weight: 600;" x-text="results['{{ $key }}'].message"></span>
                            <span style="font-size: 0.78rem; opacity: 0.8; margin-left: 6px;" x-text="'(' + results['{{ $key }}'].latency_ms + ' ms)'"></span>
                        </div>
                        <button type="button" @click="results['{{ $key }}'] = null" style="background: none; border: none; color: inherit; cursor: pointer;">&times;</button>
                    </div>
                </template>

                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <!-- Model Display Name -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">
                            Model Display Name
                        </label>
                        <input type="text" name="ai_model_{{ $key }}_name" value="{{ old('ai_model_'.$key.'_name', $model['name']) }}" class="admin-input" style="width: 100%; box-sizing: border-box; font-size: 0.88rem;">
                    </div>

                    <!-- Base URL Endpoint -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">
                            API Gateway Endpoint URL <span style="color: #f87171;">*</span>
                        </label>
                        <input type="text" name="ai_model_{{ $key }}_url" value="{{ old('ai_model_'.$key.'_url', $model['base_url']) }}" placeholder="https://prod.api.market/api/v1/magicapi/..." required class="admin-input" style="width: 100%; box-sizing: border-box; font-family: 'JetBrains Mono', monospace; font-size: 0.86rem; color: var(--admin-cyan);">
                    </div>

                    <!-- Model Version Hash -->
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 600; color: #cbd5e1; margin-bottom: 4px;">
                            Model Version ID / Checkpoint Hash (Optional)
                        </label>
                        <input type="text" name="ai_model_{{ $key }}_version" value="{{ old('ai_model_'.$key.'_version', $model['version']) }}" placeholder="e.g. 16e15e913fcc71c1a5defb335ea84739f99731fa1ee17995117c7d9adc6d176c" class="admin-input" style="width: 100%; box-sizing: border-box; font-family: 'JetBrains Mono', monospace; font-size: 0.84rem;">
                    </div>
                </div>
            </div>
        @endforeach

        <div style="display: flex; justify-content: flex-end; margin-top: 8px;">
            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
                <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                <span>Save All AI Model Configurations</span>
            </button>
        </div>
    </form>

</div>

<script>
function modelsManager() {
    return {
        loadingModel: null,
        results: {},
        async testModel(key) {
            this.loadingModel = key;
            this.results[key] = null;
            try {
                const res = await fetch("{{ route('admin.models.test') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ model: key })
                });
                this.results[key] = await res.json();
            } catch (e) {
                this.results[key] = {
                    success: false,
                    message: 'Error testing endpoint: ' + e.message,
                    latency_ms: 0
                };
            } finally {
                this.loadingModel = null;
                this.$nextTick(() => {
                    if (window.lucide) lucide.createIcons();
                });
            }
        }
    };
}
</script>
@endsection
