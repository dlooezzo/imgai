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
        <header class="app-header" x-data="{ mobileMenuOpen: false }">
            <div style="display: flex; align-items: center; gap: 24px;">
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

                <!-- Dynamic Navigation Links (Desktop/Tablet) -->
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

            <div class="header-actions" style="display: flex; align-items: center; gap: 10px;">
                @if (Auth::check())
                    <a href="{{ route('pricing') }}" class="header-credit-badge" title="Live Available Credits — Click to Get More" style="display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15)); border: 1px solid rgba(139, 92, 246, 0.35); color: #c084fc; padding: 6px 14px; border-radius: 9999px; text-decoration: none; font-size: 0.82rem; font-weight: 700; transition: all 0.2s ease; box-shadow: 0 0 15px rgba(139, 92, 246, 0.12);">
                        <i data-lucide="zap" style="width: 14px; height: 14px; fill: #fbbf24; color: #fbbf24;"></i>
                        <span x-text="user && user.credit_balance !== undefined ? Number(user.credit_balance).toLocaleString() + ' Credits' : '{{ number_format(Auth::user()->credit_balance) }} Credits'">
                            {{ number_format(Auth::user()->credit_balance) }} Credits
                        </span>
                    </a>
                @endif

                <!-- Mobile Menu Button (Visible only on mobile <= 768px) -->
                <button type="button" class="mobile-header-menu-btn" @click="mobileMenuOpen = true" title="Menu" aria-label="Open Navigation Menu">
                    <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
                </button>
            </div>

            <!-- Mobile Slide-out Drawer (Visible on tap on mobile screens) -->
            <div class="mobile-drawer-backdrop" x-show="mobileMenuOpen" x-cloak @click="mobileMenuOpen = false" @keydown.escape.window="mobileMenuOpen = false">
                <div class="mobile-drawer-sheet" @click.stop>
                    <div class="mobile-drawer-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="brand-logo-icon" style="width: 32px; height: 32px;">
                                <i data-lucide="sparkles" style="width: 16px; height: 16px;"></i>
                            </div>
                            <span style="font-weight: 800; color: #ffffff; font-size: 1.05rem;">{{ $publicSiteTitle }}</span>
                        </div>
                        <button type="button" @click="mobileMenuOpen = false" class="mobile-drawer-close-btn" aria-label="Close Menu">&times;</button>
                    </div>

                    <div class="mobile-drawer-body">
                        @if (Auth::check())
                            <div class="mobile-drawer-user-card">
                                <div class="user-avatar" style="width: 38px; height: 38px; font-size: 0.95rem; flex-shrink: 0;">
                                    {{ strtoupper(substr(Auth::user()->name ?: Auth::user()->email, 0, 1)) }}
                                </div>
                                <div style="display: flex; flex-direction: column; overflow: hidden;">
                                    <span style="font-weight: 700; color: #ffffff; font-size: 0.92rem; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                        {{ Auth::user()->name ?: explode('@', Auth::user()->email)[0] }}
                                    </span>
                                    <span style="color: #94a3b8; font-size: 0.78rem; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">{{ Auth::user()->email }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="mobile-drawer-links">
                            <a href="{{ route('tools.overview') }}" class="mobile-drawer-link {{ request()->routeIs('tools.overview') ? 'active' : '' }}" @click="mobileMenuOpen = false">
                                <i data-lucide="sparkles" style="width: 18px; height: 18px;"></i>
                                <span>Studio Overview</span>
                            </a>
                            <a href="{{ route('tools.image.index') }}" class="mobile-drawer-link {{ (request()->routeIs('tools.image.index') || request()->routeIs('tools.index')) && !request()->routeIs('tools.overview') ? 'active' : '' }}" @click="mobileMenuOpen = false">
                                <i data-lucide="image" style="width: 18px; height: 18px;"></i>
                                <span>Text to Image</span>
                            </a>
                            <a href="{{ route('tools.video.index') }}" class="mobile-drawer-link {{ request()->routeIs('tools.video.index') ? 'active' : '' }}" @click="mobileMenuOpen = false">
                                <i data-lucide="video" style="width: 18px; height: 18px;"></i>
                                <span>Text to Video</span>
                            </a>
                            <a href="{{ route('tools.image-to-video.index') }}" class="mobile-drawer-link {{ request()->routeIs('tools.image-to-video.index') ? 'active' : '' }}" @click="mobileMenuOpen = false">
                                <i data-lucide="clapperboard" style="width: 18px; height: 18px;"></i>
                                <span>Image to Video</span>
                            </a>
                            <a href="{{ route('library.index') }}" class="mobile-drawer-link {{ request()->routeIs('library.*') ? 'active' : '' }}" @click="mobileMenuOpen = false">
                                <i data-lucide="film" style="width: 18px; height: 18px;"></i>
                                <span>Media Library</span>
                            </a>
                            <a href="{{ route('pricing') }}" class="mobile-drawer-link {{ request()->routeIs('pricing') ? 'active' : '' }}" @click="mobileMenuOpen = false">
                                <i data-lucide="credit-card" style="width: 18px; height: 18px; color: #a855f7;"></i>
                                <span>Pricing & Plans</span>
                            </a>

                            @foreach ($appNavPages as $navItem)
                                @if ($navItem->slug !== 'pricing')
                                    <a href="{{ url($navItem->slug) }}" class="mobile-drawer-link {{ request()->is($navItem->slug) ? 'active' : '' }}" @click="mobileMenuOpen = false">
                                        <i data-lucide="file-text" style="width: 18px; height: 18px;"></i>
                                        <span>{{ $navItem->nav_label }}</span>
                                    </a>
                                @endif
                            @endforeach

                            @if (Auth::check() && Auth::user()->isAdmin())
                                <div style="height: 1px; background: rgba(255,255,255,0.08); margin: 8px 0;"></div>
                                <a href="{{ route('admin.dashboard') }}" class="mobile-drawer-link" style="color: #818cf8;" @click="mobileMenuOpen = false">
                                    <i data-lucide="shield-check" style="width: 18px; height: 18px; color: #818cf8;"></i>
                                    <span>Admin Panel</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="mobile-drawer-footer">
                        @if (Auth::check())
                            <button type="button" @click="handleLogout(); mobileMenuOpen = false;" class="mobile-drawer-logout-btn">
                                <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                                <span>Sign Out</span>
                            </button>
                        @else
                            <button type="button" @click="mobileMenuOpen = false; authModalOpen = true;" class="btn-action btn-action-primary" style="width: 100%; justify-content: center; padding: 12px; font-weight: 700;">
                                <i data-lucide="user" style="width: 16px; height: 16px;"></i>
                                <span>Sign In / Register</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body Area -->
        <div class="app-body">
            @yield('content')
        </div>

        <!-- Mobile Bottom Navigation Dock (Visible only on mobile screens <= 768px) -->
        <nav class="mobile-bottom-nav">
            <a href="{{ route('tools.overview') }}" class="mobile-nav-item {{ request()->routeIs('tools.overview') ? 'active' : '' }}" title="Overview">
                <i data-lucide="home" class="mobile-nav-icon"></i>
                <span class="mobile-nav-label">Home</span>
            </a>

            <a href="{{ route('tools.image.index') }}" class="mobile-nav-item {{ (request()->routeIs('tools.image.index') || request()->routeIs('tools.index')) && !request()->routeIs('tools.overview') ? 'active' : '' }}" title="Text to Image">
                <i data-lucide="sparkles" class="mobile-nav-icon"></i>
                <span class="mobile-nav-label">Images</span>
            </a>

            <!-- Center Elevated Action Button (+) -->
            <a href="{{ route('tools.image.index') }}" class="mobile-nav-create-btn" title="Create New">
                <i data-lucide="plus"></i>
            </a>

            <a href="{{ route('tools.video.index') }}" class="mobile-nav-item {{ request()->routeIs('tools.video.index') || request()->routeIs('tools.image-to-video.index') ? 'active' : '' }}" title="Text to Video">
                <i data-lucide="clapperboard" class="mobile-nav-icon"></i>
                <span class="mobile-nav-label">Videos</span>
            </a>

            <a href="{{ Auth::check() ? route('profile.index') : 'javascript:void(0)' }}" 
               @click="{{ Auth::check() ? '' : 'authModalOpen = true' }}"
               class="mobile-nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" 
               title="{{ Auth::check() ? 'Profile' : 'Sign In' }}">
                <i data-lucide="user" class="mobile-nav-icon"></i>
                <span class="mobile-nav-label">{{ Auth::check() ? 'Profile' : 'Account' }}</span>
            </a>
        </nav>

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
        document.addEventListener('alpine:initialized', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>
