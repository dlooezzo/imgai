@extends('admin.layouts.app')

@section('title', 'Pages Management (CMS)')
@section('breadcrumb', 'Content / Pages')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Pages Management (CMS)
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Create, edit, and organize dynamic website content, landing pages, and navbar links.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="{{ route('admin.pages.create') }}" class="btn-admin btn-admin-primary">
                <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i>
                <span>Create New Page</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats Row -->
    <div class="stats-grid" style="margin-bottom: 0;">
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Total Pages</span>
                <span class="stat-value">{{ number_format($stats['total']) }}</span>
                <span class="stat-hint">CMS database records</span>
            </div>
            <div class="stat-icon-wrapper" style="color: var(--admin-cyan); background: rgba(6, 182, 212, 0.12); border-color: rgba(6, 182, 212, 0.25);">
                <i data-lucide="file-code"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Published</span>
                <span class="stat-value" style="color: #34d399;">{{ number_format($stats['published']) }}</span>
                <span class="stat-hint">Live on public website</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #34d399; background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.25);">
                <i data-lucide="check-circle-2"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Drafts</span>
                <span class="stat-value" style="color: #fbbf24;">{{ number_format($stats['draft']) }}</span>
                <span class="stat-hint">Admin-only visibility</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #fbbf24; background: rgba(245, 158, 11, 0.12); border-color: rgba(245, 158, 11, 0.25);">
                <i data-lucide="clock"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">In Navigation</span>
                <span class="stat-value" style="color: #c084fc;">{{ number_format($stats['in_navigation']) }}</span>
                <span class="stat-hint">Featured in main navbar</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #c084fc; background: rgba(139, 92, 246, 0.12); border-color: rgba(139, 92, 246, 0.25);">
                <i data-lucide="compass"></i>
            </div>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <form method="GET" action="{{ route('admin.pages.index') }}" class="admin-filter-bar">
        <div class="admin-search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by page title, slug, or excerpt...">
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <select name="status" class="admin-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="published" {{ $status === 'published' ? 'selected' : '' }}>Published Only</option>
                <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Drafts Only</option>
            </select>

            <select name="navigation" class="admin-select" onchange="this.form.submit()">
                <option value="">All Navigation</option>
                <option value="in_nav" {{ $navigation === 'in_nav' ? 'selected' : '' }}>Shown in Navbar</option>
                <option value="not_in_nav" {{ $navigation === 'not_in_nav' ? 'selected' : '' }}>Hidden from Navbar</option>
            </select>

            <select name="sort" class="admin-select" onchange="this.form.submit()">
                <option value="navigation_order" {{ $sort === 'navigation_order' ? 'selected' : '' }}>Sort: Nav Order</option>
                <option value="updated" {{ $sort === 'updated' ? 'selected' : '' }}>Sort: Recently Updated</option>
                <option value="title" {{ $sort === 'title' ? 'selected' : '' }}>Sort: Title (A-Z)</option>
            </select>

            @if ($search || $status || $navigation || $sort !== 'navigation_order')
                <a href="{{ route('admin.pages.index') }}" class="btn-admin btn-admin-secondary" style="padding: 8px 12px; font-size: 0.82rem;">
                    Clear Filters
                </a>
            @endif
        </div>
    </form>

    <!-- Pages Table -->
    <div class="admin-card" style="padding: 0; overflow: hidden;">
        <div class="admin-table-container" style="border: none; border-radius: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Page Title</th>
                        <th>URL Slug</th>
                        <th>Status</th>
                        <th>Navbar Display</th>
                        <th>Order</th>
                        <th>Last Modified</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            <td>
                                <div style="display: flex; flex-direction: column;">
                                    <a href="{{ route('admin.pages.edit', $page->id) }}" style="color: #f8fafc; font-weight: 700; text-decoration: none; font-size: 0.92rem;">
                                        {{ $page->title }}
                                    </a>
                                    @if ($page->excerpt)
                                        <span style="font-size: 0.76rem; color: #64748b; margin-top: 2px; max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $page->excerpt }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <a href="{{ url($page->slug) }}" target="_blank" class="font-mono" style="font-size: 0.82rem; color: var(--admin-cyan); text-decoration: none;">
                                    /{{ $page->slug }}
                                </a>
                            </td>
                            <td>
                                @if ($page->status === 'published')
                                    <form method="POST" action="{{ route('admin.pages.toggle-status', $page->id) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="badge badge-success" style="cursor: pointer; border: none;" title="Click to unpublish (switch to draft)">
                                            <i data-lucide="check-circle-2" style="width: 12px; height: 12px;"></i> Published
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.pages.toggle-status', $page->id) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="badge badge-warning" style="cursor: pointer; border: none;" title="Click to publish">
                                            <i data-lucide="clock" style="width: 12px; height: 12px;"></i> Draft
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                @if ($page->show_in_navigation)
                                    <span class="badge" style="background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.35); color: #c084fc;">
                                        <i data-lucide="eye" style="width: 12px; height: 12px;"></i> {{ $page->navigation_label ?: $page->title }}
                                    </span>
                                @else
                                    <span class="badge badge-user">Hidden</span>
                                @endif
                            </td>
                            <td class="font-mono" style="font-size: 0.82rem; color: #cbd5e1;">
                                {{ $page->navigation_order }}
                            </td>
                            <td style="font-size: 0.78rem; color: #94a3b8; white-space: nowrap;">
                                {{ $page->updated_at ? $page->updated_at->format('M d, Y') : '—' }}
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="{{ route('admin.pages.preview', $page->id) }}" target="_blank" class="btn-admin btn-admin-secondary" style="padding: 5px 9px; font-size: 0.74rem;" title="Preview page">
                                        <i data-lucide="eye" style="width: 13px; height: 13px;"></i>
                                    </a>

                                    <a href="{{ route('admin.pages.edit', $page->id) }}" class="btn-admin btn-admin-primary" style="padding: 5px 10px; font-size: 0.74rem;">
                                        <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                                        <span>Edit</span>
                                    </a>

                                    <form method="POST" action="{{ route('admin.pages.destroy', $page->id) }}" onsubmit="return confirm('Permanently delete page \'{{ $page->title }}\'?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-admin btn-admin-danger" style="padding: 5px 9px; font-size: 0.74rem;" title="Delete page">
                                            <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 48px; color: #64748b;">
                                <i data-lucide="file-x" style="width: 36px; height: 36px; margin-bottom: 10px; color: #475569;"></i>
                                <p>No CMS pages found. Click <strong>Create New Page</strong> to get started.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pages->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--admin-border);">
                {{ $pages->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
