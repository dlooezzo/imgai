@extends('layouts.app')

@php $hideMobileBottomNav = true; @endphp

@section('content')
<style>
    .app-body.app-body--no-dock {
        display: block !important;
    }
    .public-page-scroll-area {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
        width: 100%;
        background-color: var(--bg-canvas, #07090e);
    }
    
    .public-content-container {
        flex: 1 0 auto;
        max-width: 880px;
        width: 100%;
        box-sizing: border-box;
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
        box-sizing: border-box;
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .public-body-content > * { max-width: 100%; box-sizing: border-box; }
    .public-body-content img,
    .public-body-content video,
    .public-body-content audio,
    .public-body-content canvas,
    .public-body-content svg {
        display: block;
        width: auto;
        max-width: 100%;
        height: auto;
    }

    .public-body-content figure { max-width: 100%; overflow: hidden; }
    .public-body-content table {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        border-collapse: collapse;
    }
    .public-body-content pre { max-width: 100%; overflow-x: auto; white-space: pre; }
    .public-body-content iframe,
    .public-body-content embed,
    .public-body-content object {
        display: block;
        width: 100%;
        max-width: 100%;
        border: 0;
    }
    .public-body-content a { overflow-wrap: anywhere; word-break: break-word; }

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

    @media (max-width: 768px) {
        .public-page-scroll-area {
            --mobile-ink: #f8fafc;
            --mobile-muted: #94a3b8;
            --mobile-soft: #64748b;
            --mobile-line: rgba(148, 163, 184, 0.16);
            --mobile-panel: rgba(17, 24, 39, 0.86);
            background:
                radial-gradient(circle at 100% 0%, rgba(14, 165, 233, 0.12), transparent 34%),
                radial-gradient(circle at 0% 22%, rgba(99, 102, 241, 0.1), transparent 30%),
                #080b12;
        }

        .public-content-container {
            padding: 30px 14px 64px;
        }

        .public-header-card {
            margin: 4px 2px 24px;
            padding: 4px 2px 0;
        }

        .public-header-glow {
            top: -66px;
            left: 76%;
            width: 230px;
            height: 150px;
            opacity: 0.7;
        }

        .public-header-card .studio-pill-badge {
            display: inline-flex;
            padding: 6px 10px;
            margin-bottom: 16px !important;
            border: 1px solid rgba(56, 189, 248, 0.22);
            border-radius: 999px;
            background: rgba(14, 165, 233, 0.08);
        }

        .public-page-title {
            max-width: 13ch;
            margin-bottom: 14px;
            color: var(--mobile-ink);
            font-size: clamp(2rem, 10vw, 2.8rem);
            font-weight: 800;
            letter-spacing: -0.045em;
            line-height: 1.02;
            text-wrap: balance;
        }

        .public-page-excerpt {
            max-width: 34rem;
            margin-bottom: 18px;
            color: #a9b7ca;
            font-size: 0.98rem;
            line-height: 1.65;
        }

        .public-header-card > div:last-child {
            display: inline-flex !important;
            flex-wrap: wrap;
            gap: 7px !important;
            padding: 7px 10px;
            border: 1px solid var(--mobile-line);
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.55);
            color: var(--mobile-soft) !important;
            font-size: 0.7rem !important;
        }

        .public-body-content {
            padding: 28px 18px 34px;
            border: 1px solid var(--mobile-line);
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.92), rgba(10, 15, 25, 0.94));
            box-shadow: 0 22px 50px rgba(0, 0, 0, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.035);
            overflow: hidden;
            font-size: 1rem;
            line-height: 1.82;
        }

        .public-body-content > * { max-width: 100%; }
        .public-body-content p { margin: 0 0 1.2em; color: #c8d2e1; }
        .public-body-content h2,
        .public-body-content h3,
        .public-body-content h4 {
            color: #f8fafc;
            letter-spacing: -0.025em;
            text-wrap: balance;
        }
        .public-body-content h2 {
            margin: 2em -2px 0.75em;
            padding-top: 1.15em;
            border-top: 1px solid var(--mobile-line);
            border-bottom: 0;
            font-size: 1.42rem;
            line-height: 1.18;
        }
        .public-body-content h2:first-child { padding-top: 0; border-top: 0; }
        .public-body-content h3 { margin-top: 1.6em; font-size: 1.15rem; line-height: 1.28; }
        .public-body-content h4 { font-size: 1rem; line-height: 1.35; }
        .public-body-content ul,
        .public-body-content ol { margin: 0 0 1.35em; padding-left: 1.35em; }
        .public-body-content li { margin-bottom: 0.55em; color: #c8d2e1; }

        .public-body-content figure {
            margin: 1.7rem -2px;
            padding: 8px;
            border: 1px solid rgba(148, 163, 184, 0.14);
            border-radius: 16px;
            background: rgba(2, 6, 23, 0.46);
        }
        .public-body-content figure img {
            width: 100%;
            border-radius: 10px;
            box-shadow: none;
        }
        .public-body-content figcaption { padding: 7px 5px 2px; font-size: 0.72rem; line-height: 1.5; }

        .public-body-content blockquote {
            margin: 1.5rem 0;
            padding: 16px 16px 16px 18px;
            border-left-width: 3px;
            border-radius: 0 14px 14px 0;
            background: linear-gradient(110deg, rgba(99, 102, 241, 0.16), rgba(14, 165, 233, 0.07));
            color: #dbeafe;
        }

        .public-body-content pre {
            margin: 1.4rem -2px;
            padding: 15px;
            border-radius: 14px;
            font-size: 0.76rem;
            line-height: 1.65;
        }
        .public-body-content table { margin: 1.4rem 0; border: 1px solid var(--mobile-line); border-radius: 12px; }
        .public-body-content iframe { min-height: 220px; border-radius: 14px; }
    }
</style>

<div class="public-page-scroll-area">
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

    <!-- Unified Global Footer -->
    @include('layouts.footer')
</div>
@endsection
