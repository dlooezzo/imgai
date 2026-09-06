@extends('admin.layouts.app')

@section('title', 'Platform Settings')
@section('breadcrumb', 'System / Settings')

@section('content')
<div x-data="{ currentTab: '{{ $tab }}' }" style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Platform Settings & Environment
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Verified system configurations categorized by subsystem. To customize site logo and branding, visit the Branding page.
            </p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <form method="POST" action="{{ route('admin.settings.migrate') }}">
                @csrf
                <button type="submit" class="btn-admin btn-admin-secondary" style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.35); color: #60a5fa;">
                    <i data-lucide="database" style="width: 15px; height: 15px;"></i>
                    <span>Run Pending Migrations</span>
                </button>
            </form>

            <a href="{{ route('admin.branding.index') }}" class="btn-admin btn-admin-primary">
                <i data-lucide="palette" style="width: 15px; height: 15px;"></i>
                <span>Edit Branding & Logo</span>
            </a>
        </div>
    </div>

    <!-- Category Tabs -->
    <div style="display: flex; gap: 8px; border-bottom: 1px solid var(--admin-border); padding-bottom: 12px; overflow-x: auto;">
        <button type="button" class="btn-admin" :class="currentTab === 'general' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="currentTab = 'general'">
            <i data-lucide="sliders" style="width: 15px; height: 15px;"></i>
            <span>General</span>
        </button>

        <button type="button" class="btn-admin" :class="currentTab === 'database' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="currentTab = 'database'">
            <i data-lucide="database" style="width: 15px; height: 15px;"></i>
            <span>Database</span>
        </button>

        <button type="button" class="btn-admin" :class="currentTab === 'authentication' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="currentTab = 'authentication'">
            <i data-lucide="shield" style="width: 15px; height: 15px;"></i>
            <span>Authentication</span>
        </button>

        <button type="button" class="btn-admin" :class="currentTab === 'ai' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="currentTab = 'ai'">
            <i data-lucide="cpu" style="width: 15px; height: 15px;"></i>
            <span>AI Engine</span>
        </button>

        <button type="button" class="btn-admin" :class="currentTab === 'storage' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="currentTab = 'storage'">
            <i data-lucide="hard-drive" style="width: 15px; height: 15px;"></i>
            <span>Storage</span>
        </button>

        <button type="button" class="btn-admin" :class="currentTab === 'security' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="currentTab = 'security'">
            <i data-lucide="lock" style="width: 15px; height: 15px;"></i>
            <span>Security</span>
        </button>
    </div>

    <!-- Settings Content Cards for each Category -->
    @foreach ($settings as $category => $items)
        <div x-show="currentTab === '{{ $category }}'" class="admin-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 18px 22px; border-bottom: 1px solid var(--admin-border); background: rgba(15, 22, 38, 0.6); display: flex; align-items: center; justify-content: space-between;">
                <h2 style="font-size: 1.05rem; font-weight: 700; color: #f8fafc; text-transform: capitalize;">
                    {{ $category }} Parameters
                </h2>
                <span class="badge badge-user">Read-Only</span>
            </div>

            <div style="display: flex; flex-direction: column;">
                @foreach ($items as $label => $value)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 22px; border-bottom: 1px solid var(--admin-border); gap: 16px; flex-wrap: wrap;">
                        <span style="font-weight: 600; color: #cbd5e1; font-size: 0.88rem;">{{ $label }}</span>
                        <span class="font-mono" style="font-size: 0.84rem; color: #38bdf8; word-break: break-all; max-width: 500px; text-align: right;">
                            {{ $value }}
                        </span>
                    </div>
                @endforeach
            </div>

            @if ($category === 'database')
                <div style="padding: 16px 22px; background: rgba(2, 6, 23, 0.4); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <div style="font-size: 0.82rem; color: #94a3b8;">
                        Synchronize database schema with latest application models and billing tables safely.
                    </div>
                    <form method="POST" action="{{ route('admin.settings.migrate') }}">
                        @csrf
                        <button type="submit" class="btn-admin btn-admin-primary" style="padding: 8px 16px;">
                            <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                            <span>Execute Migrations Now</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @endforeach

</div>
@endsection
