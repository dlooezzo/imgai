<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>[PREVIEW] {{ $article->title }} — IMGAI AI Studio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body style="background: #060913; color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; min-height: 100vh;">

    <!-- Admin Preview Banner -->
    <div style="background: linear-gradient(90deg, #6366f1, #a855f7); padding: 10px 20px; color: #fff; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="background: rgba(0,0,0,0.3); padding: 2px 8px; border-radius: 4px; font-weight: 800; font-size: 0.72rem; text-transform: uppercase;">Admin Preview</span>
            <span>You are previewing the SEO Article for <strong>{{ $toolMeta['name'] }}</strong> ({{ $article->status === 'published' ? 'Published' : 'Draft' }}).</span>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="{{ route('admin.seo.articles.edit', $toolKey) }}" style="color: #fff; text-decoration: underline; font-weight: 600;">Edit Article</a>
            <button onclick="window.close()" style="background: rgba(0,0,0,0.3); border: none; color: #fff; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 0.78rem;">Close Preview</button>
        </div>
    </div>

    <div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
        <!-- Simulated AI Generator Section Placeholder -->
        <div style="background: rgba(15, 20, 34, 0.7); border: 1px dashed rgba(56, 189, 248, 0.3); border-radius: 16px; padding: 36px; text-align: center; margin-bottom: 40px;">
            <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.25); padding: 6px 14px; border-radius: 100px; color: var(--brand-cyan); font-size: 0.8rem; margin-bottom: 12px;">
                <i data-lucide="cpu" style="width: 14px; height: 14px;"></i>
                <span>{{ $toolMeta['model'] }}</span>
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0; color: #f8fafc;">{{ $toolMeta['name'] }} (Generator Area)</h2>
            <p style="color: #94a3b8; font-size: 0.88rem; margin-top: 6px;">[Interactive Neural Diffusion Canvas active on live page]</p>
        </div>

        <!-- Rendered SEO Article Section -->
        @include('tools.partials.seo-article', ['article' => $article])
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
