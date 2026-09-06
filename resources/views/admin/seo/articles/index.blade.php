@extends('admin.layouts.app')

@section('title', 'AI Tool SEO Articles')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">
    <!-- Header -->
    <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <span class="badge badge-purple" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em;">SEO Content Engine</span>
                <span style="color: #64748b; font-size: 0.8rem;">•</span>
                <span style="color: #94a3b8; font-size: 0.82rem;">Rich HTML Editorial Below AI Generators</span>
            </div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: #f8fafc; margin: 0; letter-spacing: -0.02em;">
                Tool SEO Articles
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem; margin-top: 4px;">
                Manage comprehensive, crawler-optimized guide articles displayed underneath the 3 AI generation suites.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="{{ route('admin.seo.index') }}" class="btn-action" style="padding: 9px 14px; font-size: 0.84rem;">
                <i data-lucide="arrow-left" style="width: 15px; height: 15px;"></i>
                <span>SEO Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Metric Counters -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
        <div class="admin-card" style="padding: 18px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 0.76rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Total Tools</div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: #f8fafc; margin-top: 4px;">{{ $stats['total'] }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.25); display: flex; align-items: center; justify-content: center; color: var(--admin-cyan);">
                    <i data-lucide="cpu" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
        </div>

        <div class="admin-card" style="padding: 18px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 0.76rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Published Articles</div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: #34d399; margin-top: 4px;">{{ $stats['published'] }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.25); display: flex; align-items: center; justify-content: center; color: #34d399;">
                    <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
        </div>

        <div class="admin-card" style="padding: 18px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 0.76rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Drafts in Progress</div>
                    <div style="font-size: 1.6rem; font-weight: 800; color: #fbbf24; margin-top: 4px;">{{ $stats['drafts'] }}</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.25); display: flex; align-items: center; justify-content: center; color: #fbbf24;">
                    <i data-lucide="edit-3" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
        </div>

        <div class="admin-card" style="padding: 18px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 0.76rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Storage Engine</div>
                    <div style="font-size: 1.05rem; font-weight: 800; color: #c084fc; margin-top: 8px;">Cloudflare R2</div>
                </div>
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(192, 132, 252, 0.1); border: 1px solid rgba(192, 132, 252, 0.25); display: flex; align-items: center; justify-content: center; color: #c084fc;">
                    <i data-lucide="cloud" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tool Articles Table Card -->
    <div class="admin-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 18px 24px; border-bottom: 1px solid var(--admin-border); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h2 style="font-size: 1.05rem; font-weight: 700; color: #f8fafc; margin: 0;">AI Generation Suite Articles</h2>
                <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 2px;">Each AI tool possesses a dedicated editorial article rendered server-side.</p>
            </div>
        </div>

        <div class="admin-table-wrapper" style="border: none; border-radius: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 280px;">AI Tool Suite</th>
                        <th>Article Headline & Scope</th>
                        <th style="width: 140px;">SEO Metadata</th>
                        <th style="width: 120px; text-align: center;">Status</th>
                        <th style="width: 140px;">Last Updated</th>
                        <th style="width: 190px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($toolList as $item)
                        @php
                            $meta = $item['meta'];
                            $article = $item['article'];
                            $status = $item['status'];
                        @endphp
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(15, 23, 42, 0.8); border: 1px solid var(--admin-border); display: flex; align-items: center; justify-content: center;">
                                        @if($meta['key'] === 'image-generator')
                                            <i data-lucide="image" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                                        @elseif($meta['key'] === 'video-generator')
                                            <i data-lucide="video" style="width: 18px; height: 18px; color: #a855f7;"></i>
                                        @else
                                            <i data-lucide="clapperboard" style="width: 18px; height: 18px; color: #34d399;"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: #f8fafc; font-size: 0.9rem;">{{ $meta['name'] }}</div>
                                        <div style="font-size: 0.74rem; color: #64748b; font-family: monospace;">{{ $meta['public_url'] }}</div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                @if($article)
                                    <div style="font-weight: 600; color: #e2e8f0; font-size: 0.88rem; margin-bottom: 2px;">
                                        {{ $article->title }}
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 10px; font-size: 0.76rem; color: #94a3b8;">
                                        <span><strong style="color: #cbd5e1;">{{ number_format($item['word_count']) }}</strong> words</span>
                                        <span>•</span>
                                        <span>~{{ $item['reading_time'] }} min read</span>
                                    </div>
                                @else
                                    <div style="color: #64748b; font-size: 0.84rem; font-style: italic;">
                                        No article created yet. (Click Edit to start drafting)
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if($article && !empty($article->seo_title))
                                    <div style="display: flex; flex-direction: column; gap: 2px;">
                                        <span class="badge badge-cyan" style="font-size: 0.68rem; align-self: flex-start;">Custom SEO Title</span>
                                        <span style="font-size: 0.74rem; color: #94a3b8; max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $article->seo_title }}">
                                            {{ $article->seo_title }}
                                        </span>
                                    </div>
                                @else
                                    <span style="font-size: 0.74rem; color: #64748b;">Default SEO</span>
                                @endif
                            </td>

                            <td style="text-align: center;">
                                @if($status === 'published')
                                    <span class="badge badge-success" style="font-size: 0.74rem;">Published</span>
                                @elseif($status === 'draft')
                                    <span class="badge badge-warning" style="font-size: 0.74rem;">Draft</span>
                                @else
                                    <span class="badge badge-gray" style="font-size: 0.74rem;">Not Created</span>
                                @endif
                            </td>

                            <td>
                                @if($item['updated_at'])
                                    <div style="font-size: 0.82rem; color: #cbd5e1;">{{ $item['updated_at']->format('M d, Y') }}</div>
                                    <div style="font-size: 0.72rem; color: #64748b;">{{ $item['updated_at']->diffForHumans() }}</div>
                                @else
                                    <span style="color: #64748b; font-size: 0.78rem;">—</span>
                                @endif
                            </td>

                            <td style="text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                    <a href="{{ route('admin.seo.articles.edit', $meta['key']) }}" class="btn-micro" title="Open Full Visual & HTML Editor" style="background: rgba(56, 189, 248, 0.1); color: var(--admin-cyan); border-color: rgba(56, 189, 248, 0.3);">
                                        <i data-lucide="edit-2" style="width: 13px; height: 13px;"></i>
                                        <span>Edit</span>
                                    </a>

                                    @if($article)
                                        <a href="{{ route('admin.seo.articles.preview', $meta['key']) }}" target="_blank" class="btn-micro" title="Live Article Preview in Public Layout">
                                            <i data-lucide="eye" style="width: 13px; height: 13px;"></i>
                                        </a>

                                        <form method="POST" action="{{ route('admin.seo.articles.toggle-publish', $meta['key']) }}" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn-micro" title="{{ $status === 'published' ? 'Unpublish to Draft' : 'Publish Article Live' }}" style="{{ $status === 'published' ? 'color: #fbbf24;' : 'color: #34d399;' }}">
                                                <i data-lucide="{{ $status === 'published' ? 'eye-off' : 'check' }}" style="width: 13px; height: 13px;"></i>
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.seo.articles.duplicate', $meta['key']) }}" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn-micro" title="Duplicate as draft backup">
                                                <i data-lucide="copy" style="width: 13px; height: 13px;"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
