<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Dynamic SEO System -->
    @include('layouts.seo-head')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        .public-page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: var(--bg-canvas, #07090e);
            color: var(--text-primary, #f8fafc);
            font-family: var(--font-sans, 'Inter', sans-serif);
        }

        .public-navbar {
            height: 72px;
            background: rgba(11, 15, 26, 0.8);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .public-nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .public-nav-link {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
            padding: 6px 0;
        }

        .public-nav-link:hover {
            color: #f8fafc;
        }

        .public-nav-link.active {
            color: #38bdf8;
            font-weight: 700;
        }

        .public-nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #38bdf8;
            border-radius: 2px;
            box-shadow: 0 0 8px #38bdf8;
        }

        .public-content-container {
            flex: 1;
            max-width: 880px;
            width: 100%;
            margin: 0 auto;
            padding: 48px 24px 80px;
        }

        .public-header-card {
            margin-bottom: 40px;
            position: relative;
        }

        .public-header-glow {
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            width: 320px;
            height: 180px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, rgba(6, 182, 212, 0.1) 60%, transparent 80%);
            filter: blur(40px);
            pointer-events: none;
            z-index: 0;
        }

        .public-page-title {
            font-size: 2.6rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #ffffff;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .public-page-excerpt {
            font-size: 1.15rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }

        .public-body-content {
            background: rgba(15, 20, 34, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 40px 48px;
            line-height: 1.75;
            color: #cbd5e1;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
        }

        .public-body-content h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-top: 32px;
            margin-bottom: 14px;
            letter-spacing: -0.02em;
        }

        .public-body-content h2:first-child {
            margin-top: 0;
        }

        .public-body-content h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-top: 24px;
            margin-bottom: 10px;
        }

        .public-body-content h4 {
            font-size: 1.05rem;
            font-weight: 600;
            color: #e2e8f0;
            margin-top: 20px;
            margin-bottom: 8px;
        }

        .public-body-content p {
            margin-bottom: 18px;
            font-size: 0.98rem;
        }

        .public-body-content ul, .public-body-content ol {
            margin-bottom: 20px;
            padding-left: 24px;
        }

        .public-body-content li {
            margin-bottom: 8px;
            font-size: 0.96rem;
        }

        .public-body-content a {
            color: #38bdf8;
            text-decoration: underline;
            text-underline-offset: 3px;
            transition: color 0.15s;
        }

        .public-body-content a:hover {
            color: #7dd3fc;
        }

        .public-body-content blockquote {
            border-left: 4px solid #6366f1;
            background: rgba(99, 102, 241, 0.08);
            margin: 24px 0;
            padding: 14px 20px;
            border-radius: 0 8px 8px 0;
            color: #e0e7ff;
            font-style: italic;
        }

        .public-body-content code {
            font-family: var(--font-mono, 'JetBrains Mono', monospace);
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.88em;
            color: #38bdf8;
        }

        .public-body-content pre {
            background: rgba(8, 11, 20, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 18px;
            border-radius: 10px;
            overflow-x: auto;
            margin-bottom: 20px;
        }

        .cms-divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 32px 0;
        }

        .public-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: #06080c;
            padding: 40px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .public-navbar {
                padding: 0 16px;
            }
            .public-nav-links {
                display: none;
            }
            .public-body-content {
                padding: 24px 20px;
            }
            .public-page-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="public-page-wrapper">

    @if (!empty($isAdminPreview))
        <!-- Admin Preview Mode Banner -->
        <div style="background: linear-gradient(90deg, #854d0e 0%, #b45309 100%); color: #fef08a; padding: 10px 24px; font-size: 0.84rem; font-weight: 700; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i data-lucide="shield-alert" style="width: 16px; height: 16px;"></i>
                <span>ADMIN PREVIEW MODE — This page is currently a <strong>DRAFT</strong> and is hidden from public visitors.</span>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="{{ route('admin.pages.edit', $page->id) }}" style="color: #ffffff; background: rgba(0,0,0,0.3); padding: 4px 12px; border-radius: 6px; text-decoration: none; font-size: 0.78rem;">
                    Edit in CMS
                </a>
            </div>
        </div>
    @endif

    <!-- Top Navigation Header -->
    <header class="public-navbar">
        <a href="{{ route('tools.overview') }}" class="header-brand" style="text-decoration: none;">
            <div class="brand-logo-icon">
                <i data-lucide="sparkles"></i>
            </div>
            <div class="brand-info">
                <span class="brand-title">Cinematic Studio</span>
                <span class="brand-subtitle">AI Generation Suite</span>
            </div>
        </a>

        <!-- Dynamic CMS Navigation Links -->
        <nav>
            <ul class="public-nav-links">
                <li>
                    <a href="{{ route('tools.overview') }}" class="public-nav-link">
                        Home
                    </a>
                </li>
                <li>
                    <a href="{{ route('tools.image.index') }}" class="public-nav-link">
                        Tools
                    </a>
                </li>
                @if (isset($navPages))
                    @foreach ($navPages as $navPage)
                        <li>
                            <a href="{{ url($navPage->slug) }}" class="public-nav-link {{ $navPage->slug === $page->slug ? 'active' : '' }}">
                                {{ $navPage->nav_label }}
                            </a>
                        </li>
                    @endforeach
                @endif
            </ul>
        </nav>

        <!-- Action CTAs -->
        <div style="display: flex; align-items: center; gap: 12px;">
            @if (auth()->check() && auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="btn-studio-secondary" style="padding: 8px 14px; font-size: 0.82rem; text-decoration: none;">
                    <i data-lucide="shield" style="width: 14px; height: 14px;"></i>
                    <span>Admin Panel</span>
                </a>
            @endif

            <a href="{{ route('tools.image.index') }}" class="btn-studio-primary" style="padding: 8px 16px; font-size: 0.86rem; text-decoration: none;">
                <i data-lucide="sparkles" style="width: 15px; height: 15px;"></i>
                <span>Open Studio</span>
            </a>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="public-content-container">
        
        <!-- Header Banner -->
        <div class="public-header-card">
            <div class="public-header-glow"></div>
            
            <div class="studio-pill-badge" style="margin-bottom: 14px;">
                <span class="studio-pulse-dot"></span>
                <span class="studio-pill-text">IMGAI PLATFORM</span>
            </div>

            <h1 class="public-page-title">{{ $page->title }}</h1>
            
            @if ($page->excerpt)
                <p class="public-page-excerpt">{{ $page->excerpt }}</p>
            @endif

            <div style="display: flex; align-items: center; gap: 14px; font-size: 0.8rem; color: #64748b;">
                <span>Published on {{ $page->created_at ? $page->created_at->format('F d, Y') : 'Recent' }}</span>
                <span>&bull;</span>
                <span>{{ ceil(str_word_count(strip_tags($page->content)) / 200) }} min read</span>
            </div>
        </div>

        <!-- Rendered Rich Content Body -->
        <article class="public-body-content">
            {!! $page->content !!}
        </article>

    </main>

    <!-- Public Footer -->
    <footer class="public-footer">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div class="brand-logo-icon" style="width: 28px; height: 28px;">
                <i data-lucide="sparkles" style="width: 15px; height: 15px;"></i>
            </div>
            <span style="font-size: 0.85rem; color: #64748b;">
                &copy; {{ date('Y') }} IMGAI Studio. All rights reserved.
            </span>
        </div>

        <!-- Footer Links -->
        <div style="display: flex; gap: 20px; font-size: 0.82rem;">
            @if (isset($navPages))
                @foreach ($navPages as $navPage)
                    <a href="{{ url($navPage->slug) }}" style="color: #94a3b8; text-decoration: none;">
                        {{ $navPage->nav_label }}
                    </a>
                @endforeach
            @endif
            <a href="{{ url('terms') }}" style="color: #94a3b8; text-decoration: none;">Terms</a>
            <a href="{{ url('privacy') }}" style="color: #94a3b8; text-decoration: none;">Privacy</a>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
