@extends('admin.layouts.app')

@section('title', 'System Health & Infrastructure Status')
@section('breadcrumb', 'System / System Status')

@section('content')
<div x-data="systemHealthManager()" style="display: flex; flex-direction: column; gap: 28px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                System Health Matrix
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Real-time operational health checks across databases, authentication, object storage, and AI generation clusters.
            </p>
        </div>

        <button type="button" class="btn-admin btn-admin-primary" @click="runLiveChecks()" :disabled="refreshing">
            <template x-if="!refreshing">
                <span style="display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="refresh-cw" style="width: 15px; height: 15px;"></i>
                    <span>Run Live Health Probes</span>
                </span>
            </template>
            <template x-if="refreshing">
                <span style="display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="loader-2" style="width: 15px; height: 15px; animation: spin 1s linear infinite;"></i>
                    <span>Testing 7 Infrastructure Nodes...</span>
                </span>
            </template>
        </button>
    </div>

    <!-- Health Services Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px;">
        <template x-for="(service, key) in services" :key="key">
            <div class="admin-card" style="display: flex; flex-direction: column; justify-content: space-between; gap: 14px;">
                <div>
                    <!-- Card Header -->
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 8px;">
                        <div>
                            <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: #64748b;" x-text="service.category"></span>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #f8fafc; margin-top: 2px;" x-text="service.name"></h3>
                        </div>

                        <!-- Status Badge -->
                        <div>
                            <template x-if="service.status === 'connected'">
                                <span class="badge badge-success">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #34d399; box-shadow: 0 0 6px #34d399;"></span>
                                    <span x-text="service.status_label"></span>
                                </span>
                            </template>
                            <template x-if="service.status === 'warning'">
                                <span class="badge badge-warning">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #fbbf24;"></span>
                                    <span x-text="service.status_label"></span>
                                </span>
                            </template>
                            <template x-if="service.status === 'unconfigured'">
                                <span class="badge badge-user">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #94a3b8;"></span>
                                    <span>Unconfigured</span>
                                </span>
                            </template>
                            <template x-if="service.status === 'failed'">
                                <span class="badge badge-danger">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #f87171;"></span>
                                    <span>Disconnected</span>
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Details & Description -->
                    <div style="font-size: 0.82rem; color: #94a3b8; line-height: 1.4;" x-text="service.details"></div>

                    <!-- Error message if present -->
                    <template x-if="service.error">
                        <div style="margin-top: 10px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 6px; padding: 8px 10px; font-size: 0.75rem; color: #f87171; font-family: 'JetBrains Mono', monospace; word-break: break-all;" x-text="service.error"></div>
                    </template>
                </div>

                <!-- Latency & Check Info -->
                <div style="border-top: 1px solid var(--admin-border); padding-top: 10px; display: flex; align-items: center; justify-content: space-between; font-size: 0.76rem; color: #64748b;">
                    <span>Gateway Ping:</span>
                    <span class="font-mono" :style="service.latency_ms > 0 ? 'color: #38bdf8; font-weight: 600;' : 'color: #64748b;'" x-text="service.latency_ms > 0 ? service.latency_ms + ' ms' : 'N/A'"></span>
                </div>
            </div>
        </template>
    </div>

</div>

<script>
    function systemHealthManager() {
        return {
            refreshing: false,
            services: @json($services),
            async runLiveChecks() {
                this.refreshing = true;
                try {
                    const res = await fetch("{{ route('admin.system-status.live') }}", {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.services) {
                        this.services = data.services;
                    }
                } catch (e) {
                    console.error('System health check failed:', e);
                } finally {
                    this.refreshing = false;
                    setTimeout(() => { if (window.lucide) lucide.createIcons(); }, 50);
                }
            }
        }
    }
</script>
@endsection
