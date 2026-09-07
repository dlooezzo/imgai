<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Dynamic SEO System -->
    @include('layouts.seo-head')

    <!-- Stylesheets -->
    <link rel="stylesheet" href="/css/app.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Supabase JS Client SDK -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

    <!-- App JavaScript -->
    <script src="/js/app.js"></script>

    <!-- Alpine.js Core -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
@php
    $appStore = match($activeTool ?? 'image-generator') {
        'image-to-video' => 'imageToVideoApp',
        'video-generator' => 'videoGeneratorApp',
        'profile', 'library' => 'profileApp',
        default => 'imageGeneratorApp'
    };

    $initialGens = match($activeTool ?? 'image-generator') {
        'image-to-video', 'video-generator' => ($videoGenerations ?? []),
        'profile', 'library' => array_merge(
            is_array($imageGenerations ?? null) ? $imageGenerations : (($imageGenerations ?? null) ? $imageGenerations->toArray() : []),
            is_array($videoGenerations ?? null) ? $videoGenerations : (($videoGenerations ?? null) ? $videoGenerations->toArray() : [])
        ),
        default => ($generations ?? [])
    };

    $authModelUser = Auth::user();
    if ($authModelUser) {
        $authoritativeUser = [
            'id' => $authModelUser->id,
            'email' => $authModelUser->email,
            'name' => $authModelUser->name,
            'role' => $authModelUser->role,
            'credit_balance' => (int) $authModelUser->credit_balance,
            'avatar' => session('supabase_user.avatar') ?? null,
        ];
    } elseif (!empty($currentUser)) {
        $authoritativeUser = $currentUser;
    } else {
        $authoritativeUser = null;
    }

    $appConfig = [
        'supabaseUrl' => $supabaseConfig['url'] ?? '',
        'supabaseAnonKey' => $supabaseConfig['anonKey'] ?? '',
        'currentUser' => $authoritativeUser,
        'initialGenerations' => $initialGens,
        'securitySettings' => $securitySettings ?? null,
        'stats' => $stats ?? null,
    ];
@endphp
<body x-data="{{ $appStore }}({{ json_encode($appConfig) }})">

    <div class="app-shell">
        <!-- Top Navigation Header -->
        <header class="app-header">
            <div style="display: flex; align-items: center; gap: 32px;">
                @php
                    $publicSiteTitle = \App\Models\SiteSetting::get('site_title', config('app.name', 'Cinematic Studio'));
                    $publicSiteTagline = \App\Models\SiteSetting::get('site_tagline', 'AI Generation Suite');
                    $publicSiteLogo = \App\Models\SiteSetting::get('site_logo');
                    $publicSiteLogoIcon = \App\Models\SiteSetting::get('site_logo_icon');
                @endphp
                <a href="{{ route('tools.overview') }}" class="header-brand">
                    @if ($publicSiteLogo)
                        <img src="{{ $publicSiteLogo }}" alt="{{ $publicSiteTitle }}" style="max-height: 38px; width: auto; object-fit: contain;">
                    @else
                        @if ($publicSiteLogoIcon)
                            <img src="{{ $publicSiteLogoIcon }}" alt="{{ $publicSiteTitle }}" style="width: 34px; height: 34px; border-radius: 8px; object-fit: contain;">
                        @else
                            <div class="brand-logo-icon">
                                <i data-lucide="sparkles"></i>
                            </div>
                        @endif
                        <div class="brand-info">
                            <span class="brand-title">{{ $publicSiteTitle }}</span>
                            <span class="brand-subtitle">{{ $publicSiteTagline }}</span>
                        </div>
                    @endif
                </a>

                <!-- Dynamic Navigation Links -->
                @php
                    try {
                        $appNavPages = \App\Models\Page::published()->inNavigation()->where('slug', '!=', 'pricing')->orderBy('navigation_order')->get();
                    } catch (\Throwable $e) {
                        $appNavPages = collect([]);
                    }
                @endphp
                <nav class="header-nav-cms" style="display: flex; align-items: center; gap: 20px;">
                    <a href="{{ route('tools.overview') }}" class="header-cms-link {{ request()->routeIs('tools.overview') ? 'active' : '' }}" style="color: #94a3b8; text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: color 0.15s;">
                        Overview
                    </a>
                    <a href="{{ route('pricing') }}" class="header-cms-link {{ request()->routeIs('pricing') ? 'active' : '' }}" style="color: #94a3b8; text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: color 0.15s;">
                        Pricing
                    </a>
                    @foreach ($appNavPages as $navItem)
                        @if ($navItem->slug !== 'pricing')
                            <a href="{{ url($navItem->slug) }}" class="header-cms-link {{ request()->is($navItem->slug) ? 'active' : '' }}" style="color: #94a3b8; text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: color 0.15s;">
                                {{ $navItem->nav_label }}
                            </a>
                        @endif
                    @endforeach
                </nav>
            </div>

            <div class="header-actions" style="display: flex; align-items: center; gap: 14px;">
                @if (Auth::check())
                    <a href="{{ route('pricing') }}" class="header-credit-badge" title="Live Available Credits — Click to Get More" style="display: inline-flex; align-items: center; gap: 7px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15)); border: 1px solid rgba(139, 92, 246, 0.35); color: #c084fc; padding: 6px 14px; border-radius: 9999px; text-decoration: none; font-size: 0.85rem; font-weight: 700; transition: all 0.2s ease; box-shadow: 0 0 15px rgba(139, 92, 246, 0.12);">
                        <i data-lucide="zap" style="width: 15px; height: 15px; fill: #fbbf24; color: #fbbf24;"></i>
                        <span x-text="user && user.credit_balance !== undefined ? Number(user.credit_balance).toLocaleString() + ' Credits' : '{{ number_format(Auth::user()->credit_balance) }} Credits'">
                            {{ number_format(Auth::user()->credit_balance) }} Credits
                        </span>
                    </a>
                @endif
            </div>
        </header>

        <!-- Main Body Area -->
        <div class="app-body">
            @yield('content')
        </div>

        <!-- Supabase Auth Modal -->
        @include('layouts.auth-modal')

        <!-- Lightbox Modal for Full-res Image Inspection -->
        <div class="modal-backdrop" x-show="lightboxOpen" x-cloak @click.self="closeLightbox()" @keydown.escape.window="closeLightbox()">
            <div style="max-width: 90vw; max-height: 90vh; position: relative;">
                <button type="button" @click="closeLightbox()" style="position: absolute; top: -40px; right: 0; background: none; border: none; color: #fff; font-size: 2rem; cursor: pointer;">
                    &times;
                </button>
                <img :src="lightboxImage" alt="Full resolution generation" style="max-width: 90vw; max-height: 85vh; border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,0.8); object-fit: contain;">
            </div>
        </div>

        <!-- Toast Notifications Component -->
        <div class="toast-container" x-data="toastManager">
            <template x-for="t in toasts" :key="t.id">
                <div class="toast-msg" :class="'toast-' + t.type">
                    <template x-if="t.type === 'success'">
                        <i data-lucide="check-circle-2" style="width: 18px; height: 18px; color: #34d399;"></i>
                    </template>
                    <template x-if="t.type === 'error'">
                        <i data-lucide="alert-circle" style="width: 18px; height: 18px; color: #f87171;"></i>
                    </template>
                    <template x-if="t.type === 'info'">
                        <i data-lucide="info" style="width: 18px; height: 18px; color: #60a5fa;"></i>
                    </template>
                    <span x-text="t.message"></span>
                </div>
            </template>
        </div>
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>
