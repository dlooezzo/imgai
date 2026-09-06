<div class="tool-seo-article-wrapper" style="margin-top: 56px; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 48px;">
    <article class="tool-seo-article" style="max-width: 960px; margin: 0 auto;">
        <!-- Article Header -->
        <header style="margin-bottom: 32px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                <span class="badge badge-purple" style="font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.05em; background: rgba(168, 85, 247, 0.15); border: 1px solid rgba(168, 85, 247, 0.3); color: #c084fc; padding: 3px 10px; border-radius: 6px; font-weight: 700;">
                    Technical Guide & Documentation
                </span>
                <span style="color: #64748b; font-size: 0.8rem;">•</span>
                <span style="color: #94a3b8; font-size: 0.8rem; display: flex; align-items: center; gap: 4px;">
                    <i data-lucide="clock" style="width: 13px; height: 13px; color: var(--brand-cyan);"></i>
                    <span>{{ $article->reading_time }} min read</span>
                </span>
                <span style="color: #64748b; font-size: 0.8rem;">•</span>
                <span style="color: #94a3b8; font-size: 0.8rem;">
                    Updated {{ $article->updated_at ? $article->updated_at->format('F Y') : date('F Y') }}
                </span>
            </div>

            <h2 style="font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 800; color: #f8fafc; line-height: 1.25; letter-spacing: -0.02em; margin: 0 0 16px 0; border-bottom: none; padding-bottom: 0;">
                {{ $article->title }}
            </h2>

            @if(!empty($article->excerpt))
                <p style="font-size: 1.05rem; color: #cbd5e1; line-height: 1.6; margin: 0; font-weight: 400; border-left: 3px solid var(--brand-cyan); padding-left: 16px; background: rgba(56, 189, 248, 0.03); border-radius: 0 8px 8px 0; padding-top: 8px; padding-bottom: 8px;">
                    {{ $article->excerpt }}
                </p>
            @endif
        </header>

        <!-- Article Content Body -->
        <div class="article-content-body">
            {!! $article->content_html !!}
        </div>

        <!-- Article Footer / CTA -->
        <footer style="margin-top: 48px; padding-top: 24px; border-top: 1px solid rgba(255, 255, 255, 0.08); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div style="font-size: 0.82rem; color: #64748b;">
                Published by <strong style="color: #cbd5e1;">IMGAI AI Creative Studio</strong> &bull; Wan 2.2 & Tencent Hunyuan Architecture
            </div>
            <a href="#top" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;" style="font-size: 0.82rem; color: var(--brand-cyan); text-decoration: none; display: flex; align-items: center; gap: 6px;">
                <span>Back to AI Generator</span>
                <i data-lucide="arrow-up" style="width: 14px; height: 14px;"></i>
            </a>
        </footer>
    </article>
</div>

<style>
.article-content-body {
    color: #cbd5e1;
    font-size: 1rem;
    line-height: 1.75;
}
.article-content-body h2 {
    font-size: clamp(1.3rem, 2.5vw, 1.65rem);
    font-weight: 800;
    color: #f8fafc;
    margin-top: 40px;
    margin-bottom: 16px;
    letter-spacing: -0.02em;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.article-content-body h2::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 1.1em;
    background: linear-gradient(180deg, var(--brand-cyan), #a855f7);
    border-radius: 2px;
}
.article-content-body h3 {
    font-size: clamp(1.15rem, 2vw, 1.35rem);
    font-weight: 700;
    color: #e2e8f0;
    margin-top: 32px;
    margin-bottom: 12px;
    letter-spacing: -0.01em;
}
.article-content-body h4 {
    font-size: 1.08rem;
    font-weight: 600;
    color: #cbd5e1;
    margin-top: 24px;
    margin-bottom: 8px;
}
.article-content-body p {
    margin-bottom: 20px;
    color: #94a3b8;
    font-size: 0.98rem;
}
.article-content-body strong {
    color: #f8fafc;
    font-weight: 700;
}
.article-content-body em {
    color: #cbd5e1;
}
.article-content-body a {
    color: var(--brand-cyan);
    text-decoration: underline;
    text-underline-offset: 3px;
    transition: opacity 0.2s ease;
}
.article-content-body a:hover {
    opacity: 0.8;
}
.article-content-body ul, .article-content-body ol {
    margin-bottom: 24px;
    padding-left: 28px;
}
.article-content-body li {
    margin-bottom: 8px;
    color: #cbd5e1;
}
.article-content-body blockquote {
    border-left: 3px solid #a855f7;
    padding: 16px 24px;
    margin: 28px 0;
    background: rgba(168, 85, 247, 0.05);
    border-radius: 0 10px 10px 0;
    color: #e2e8f0;
    font-style: italic;
}
.article-content-body blockquote p:last-child {
    margin-bottom: 0;
}
.article-content-body figure {
    margin: 32px 0;
    text-align: center;
    background: rgba(15, 20, 34, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 12px;
}
.article-content-body img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    display: block;
    margin: 0 auto;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}
.article-content-body figcaption {
    font-size: 0.82rem;
    color: #64748b;
    margin-top: 10px;
    font-style: italic;
    text-align: center;
}
.article-content-body pre {
    background: rgba(8, 11, 20, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 18px;
    overflow-x: auto;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.88rem;
    color: var(--brand-cyan);
    margin: 24px 0;
    line-height: 1.6;
}
.article-content-body code {
    font-family: 'JetBrains Mono', monospace;
    background: rgba(255, 255, 255, 0.08);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.85em;
    color: #c084fc;
}
.article-content-body pre code {
    background: none;
    padding: 0;
    color: inherit;
}
.article-content-body hr.article-divider, .article-content-body hr {
    border: none;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    margin: 36px 0;
}
.article-content-body table {
    width: 100%;
    border-collapse: collapse;
    margin: 24px 0;
    font-size: 0.9rem;
}
.article-content-body th {
    background: rgba(15, 23, 42, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 10px 14px;
    color: #f8fafc;
    text-align: left;
    font-weight: 700;
}
.article-content-body td {
    border: 1px solid rgba(255, 255, 255, 0.08);
    padding: 10px 14px;
    color: #cbd5e1;
}
.article-content-body tr:nth-child(even) td {
    background: rgba(15, 20, 34, 0.4);
}
</style>
