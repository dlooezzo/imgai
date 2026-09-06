<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $adminSiteTitle = \App\Models\SiteSetting::get('site_title', config('app.name', 'IMGAI'));
        $adminFavicon = \App\Models\SiteSetting::get('site_favicon');
    @endphp
    <title>@yield('title', 'Admin Control Center') — {{ $adminSiteTitle }}</title>
    <meta name="robots" content="noindex, nofollow">

    @if(!empty($adminFavicon))
        <link rel="icon" href="{{ $adminFavicon }}">
        <link rel="apple-touch-icon" href="{{ $adminFavicon }}">
    @else
        <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="/css/app.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Supabase JS Client SDK -->
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

    <!-- Alpine.js Core -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    @stack('styles')

    <style>
        :root {
            --admin-bg: #f8fafc;
            --admin-card: #ffffff;
            --admin-card-hover: #f1f5f9;
            --admin-border: #e2e8f0;
            --admin-border-subtle: #f1f5f9;
            --admin-border-accent: rgba(99, 102, 241, 0.4);
            --admin-text-primary: #0f172a;
            --admin-text-secondary: #334155;
            --admin-text-muted: #64748b;
            --admin-primary: #6366f1;
            --admin-primary-hover: #4f46e5;
            --admin-cyan: #0891b2;
            --admin-purple: #7c3aed;
            --admin-emerald: #059669;
            --admin-rose: #e11d48;
            --admin-amber: #d97706;
        }

        body {
            background-color: var(--admin-bg);
            background-image: 
                radial-gradient(circle at 10% 0%, rgba(99, 102, 241, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 90% 10%, rgba(6, 182, 212, 0.03) 0%, transparent 35%),
                radial-gradient(circle at 50% 100%, rgba(139, 92, 246, 0.03) 0%, transparent 45%);
            color: var(--admin-text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Admin Sidebar */
        .admin-sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid var(--admin-border);
            box-shadow: 2px 0 16px rgba(0, 0, 0, 0.03);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .admin-sidebar-header {
            padding: 20px 22px;
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--admin-text-primary);
        }

        .admin-brand-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #06b6d4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.3);
        }

        .admin-brand-text {
            display: flex;
            flex-direction: column;
        }

        .admin-brand-title {
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: -0.02em;
            color: #0f172a;
            background: none;
            -webkit-text-fill-color: initial;
        }

        .admin-brand-badge {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #4f46e5;
        }

        .admin-nav {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
            background: #ffffff;
        }

        .admin-nav-section-title {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            padding: 0 12px 6px;
        }

        .admin-nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 0;
            margin: 0;
        }

        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 12px;
            border-radius: 8px;
            color: #475569;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .admin-nav-link:hover {
            color: #0f172a;
            background: #f1f5f9;
            transform: translateX(2px);
        }

        .admin-nav-link.active {
            color: #4338ca;
            background: #eef2ff;
            border-left: 3px solid var(--admin-primary);
            font-weight: 600;
        }

        .admin-nav-link i {
            width: 18px;
            height: 18px;
        }

        .admin-nav-link.active i {
            color: var(--admin-primary);
        }

        .admin-sidebar-footer {
            padding: 14px;
            border-top: 1px solid var(--admin-border);
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .admin-user-card-block {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid var(--admin-border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            width: 100%;
            box-sizing: border-box;
        }

        .admin-avatar {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.88rem;
            color: #fff;
            flex-shrink: 0;
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.2);
        }

        .admin-user-info {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            flex-grow: 1;
        }

        .admin-user-name {
            font-size: 0.84rem;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .admin-user-role {
            font-size: 0.72rem;
            color: #059669;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }

        .admin-role-indicator {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 6px rgba(16, 185, 129, 0.4);
        }

        .btn-admin-logout-full {
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            outline: none !important;
            width: 100% !important;
            box-sizing: border-box !important;
            background: #fee2e2 !important;
            border: 1px solid #fca5a5 !important;
            color: #dc2626 !important;
            padding: 8px 12px !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 7px !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            font-family: inherit !important;
            transition: all 0.2s ease !important;
        }

        .btn-admin-logout-full:hover {
            background: #ef4444 !important;
            border-color: #dc2626 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25) !important;
        }

        .btn-admin-logout-full:focus,
        .btn-admin-logout-full:active {
            outline: none !important;
            background: #dc2626 !important;
            color: #ffffff !important;
        }

        /* Main Content Shell */
        .admin-main {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: var(--admin-bg);
        }

        /* Top Header */
        .admin-topbar {
            height: 64px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .admin-breadcrumbs {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            color: #64748b;
        }

        .admin-breadcrumbs a {
            color: #64748b;
            text-decoration: none;
            transition: color 0.2s;
        }

        .admin-breadcrumbs a:hover {
            color: #0f172a;
        }

        .admin-breadcrumbs .current {
            color: #0f172a;
            font-weight: 700;
        }

        .admin-topbar-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .admin-btn-app {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.82rem;
            color: #475569;
            background: #ffffff;
            border: 1px solid var(--admin-border);
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .admin-btn-app:hover {
            color: #0f172a;
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .admin-content {
            padding: 32px;
            flex: 1;
            background: var(--admin-bg);
        }

        /* Cards */
        .admin-card {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }

        .admin-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .admin-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .admin-card-title {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-card-subtitle {
            font-size: 0.82rem;
            color: #64748b;
            margin-top: 2px;
        }

        /* Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--admin-primary), var(--admin-cyan));
            opacity: 0;
            transition: opacity 0.3s;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-content {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #0f172a;
        }

        .stat-hint {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 4px;
        }

        .stat-icon-wrapper {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(99, 102, 241, 0.1);
            color: var(--admin-primary);
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        /* Modern Tables */
        .admin-table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--admin-border);
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
        }

        .admin-table th {
            padding: 12px 18px;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--admin-border);
        }

        .admin-table td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--admin-border);
            color: #334155;
            vertical-align: middle;
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table tr:hover td {
            background: #f8fafc;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .badge-admin {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #4f46e5;
        }

        .badge-user {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #475569;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #059669;
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #d97706;
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }

        .badge-info {
            background: rgba(6, 182, 212, 0.12);
            border: 1px solid rgba(6, 182, 212, 0.3);
            color: #0891b2;
        }

        /* Buttons */
        .btn-admin {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border: none;
        }

        .btn-admin-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
        }

        .btn-admin-primary * {
            color: #ffffff !important;
        }

        .btn-admin-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
        }

        .btn-admin-secondary {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .btn-admin-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
        }

        .btn-admin-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }

        .btn-admin-danger:hover {
            background: #dc2626;
            color: #ffffff;
        }

        /* Search & Filters */
        .admin-filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .admin-search-box {
            position: relative;
            min-width: 260px;
            flex: 1;
            max-width: 400px;
        }

        .admin-search-box input {
            width: 100%;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 9px 14px 9px 38px;
            color: #0f172a;
            font-size: 0.88rem;
            outline: none;
            transition: border-color 0.2s;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .admin-search-box input:focus {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .admin-search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: #64748b;
        }

        .admin-form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        .admin-label {
            font-size: 0.86rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
            display: block;
        }

        .admin-input,
        .admin-textarea,
        .admin-select {
            width: 100%;
            box-sizing: border-box;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            color: #0f172a;
            font-size: 0.88rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .admin-input:focus,
        .admin-textarea:focus,
        .admin-select:focus {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        /* Code/Mono text */
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Modals */
        .admin-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(8px);
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .admin-modal-content {
            background: #ffffff;
            border: 1px solid var(--admin-border);
            border-radius: 14px;
            max-width: 580px;
            width: 100%;
            padding: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            color: #0f172a;
        }

        /* Toast notifications */
        .admin-toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 300;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .admin-toast {
            background: #ffffff;
            border: 1px solid var(--admin-border);
            border-radius: 10px;
            padding: 12px 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.88rem;
            color: #0f172a;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Multi-column Grid Utilities */
        .admin-grid-two-col {
            display: grid;
            grid-template-columns: 2.2fr 1fr;
            gap: 24px;
            align-items: start;
        }

        /* Mobile Menu Toggle Button */
        .btn-admin-mobile-toggle {
            display: none;
            background: #ffffff;
            border: 1px solid var(--admin-border);
            color: #475569;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-admin-mobile-toggle:hover {
            color: #0f172a;
            background: #f1f5f9;
        }

        /* Scoped overrides to eliminate dark artifacts in admin child views */
        .admin-content h1,
        .admin-content h2,
        .admin-content h3,
        .admin-content h4 {
            color: #0f172a;
        }

        .admin-content p {
            color: #475569;
        }

        .admin-content a:not(.btn-admin):not(.admin-btn-app) {
            color: #4f46e5;
        }

        /* Convert dark inline card subpanels in forms to clean light containers */
        .admin-content [style*="background: rgba(0, 0, 0"],
        .admin-content [style*="background: rgba(15, 23, 42"],
        .admin-content [style*="background: #07090e"],
        .admin-content [style*="background: #0f1422"],
        .admin-content [style*="background: #0a0e18"] {
            background: #ffffff !important;
            border-color: #e2e8f0 !important;
        }

        .admin-content [style*="background: rgba(88, 28, 135"] {
            background: #faf5ff !important;
            border-color: #e9d5ff !important;
        }

        .admin-content [style*="color: #f8fafc"],
        .admin-content [style*="color: #ffffff"]:not(.btn-admin-primary):not(.btn-admin-primary *):not(.badge-admin):not(.admin-avatar):not(.admin-brand-icon *) {
            color: #0f172a !important;
        }

        .admin-content [style*="color: #cbd5e1"] {
            color: #334155 !important;
        }

        .admin-content [style*="color: #94a3b8"] {
            color: #64748b !important;
        }

        .admin-content [style*="color: #34d399"] {
            color: #059669 !important;
        }

        .admin-content [style*="color: #f87171"] {
            color: #dc2626 !important;
        }

        .admin-content [style*="color: #fbbf24"] {
            color: #d97706 !important;
        }

        .admin-content [style*="color: #c084fc"] {
            color: #7c3aed !important;
        }

        .admin-content [style*="color: #818cf8"] {
            color: #4f46e5 !important;
        }

        .admin-content code {
            background: #f1f5f9 !important;
            color: #4338ca !important;
            border: 1px solid #e2e8f0;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1024px) {
            .btn-admin-mobile-toggle {
                display: flex !important;
                align-items: center;
                justify-content: center;
            }

            .admin-sidebar {
                transform: translateX(-100%);
                z-index: 150;
                box-shadow: none;
            }

            .admin-sidebar.mobile-open {
                transform: translateX(0);
                box-shadow: 0 0 50px rgba(0, 0, 0, 0.15);
            }

            .admin-sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.4);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                z-index: 140;
            }

            .admin-main {
                margin-left: 0 !important;
                width: 100% !important;
            }

            .admin-topbar {
                padding: 0 16px;
            }

            .admin-content {
                padding: 20px 16px;
            }

            .admin-grid-two-col {
                grid-template-columns: 1fr !important;
            }
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="adminApp()">

    <!-- Mobile Sidebar Backdrop -->
    <div class="admin-sidebar-backdrop" 
         x-show="sidebarOpen" 
         @click="sidebarOpen = false" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak></div>

    <div class="admin-layout">
        <!-- Persistent Sidebar -->
        @include('admin.layouts.sidebar')

        <!-- Main Body -->
        <main class="admin-main">
            <!-- Top Navigation Bar -->
            <header class="admin-topbar">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button type="button" @click="sidebarOpen = !sidebarOpen" class="btn-admin-mobile-toggle" title="Toggle Sidebar">
                        <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
                    </button>
                    <div class="admin-breadcrumbs">
                        <a href="{{ route('admin.dashboard') }}">Admin</a>
                        <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
                        <span class="current">@yield('breadcrumb', 'Overview')</span>
                    </div>
                </div>

                <div class="admin-topbar-actions">
                    <a href="{{ route('tools.overview') }}" target="_blank" class="admin-btn-app">
                        <i data-lucide="external-link" style="width: 14px; height: 14px;"></i>
                        <span>Open User Studio</span>
                    </a>
                </div>
            </header>

            <!-- Main Page Content -->
            <div class="admin-content">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; padding: 12px 18px; border-radius: 10px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                        <i data-lucide="check-circle" style="width: 18px; height: 18px; color: #059669;"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 12px 18px; border-radius: 10px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                        <i data-lucide="alert-circle" style="width: 18px; height: 18px; color: #dc2626;"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if (session('info'))
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; padding: 12px 18px; border-radius: 10px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                        <i data-lucide="info" style="width: 18px; height: 18px; color: #2563eb;"></i>
                        <span>{{ session('info') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <!-- Admin App Alpine Logic -->
    <script>
        function adminApp() {
            return {
                sidebarOpen: false,
                async handleLogout() {
                    if (!confirm('Are you sure you want to log out from the Admin panel?')) return;
                    try {
                        // Supabase client logout if initialized
                        if (window.supabase) {
                            const supabaseUrl = "{{ config('services.supabase.url', env('SUPABASE_URL')) }}";
                            const supabaseAnon = "{{ config('services.supabase.anon_key', env('SUPABASE_ANON_KEY', env('SUPABASE_KEY'))) }}";
                            if (supabaseUrl && supabaseAnon) {
                                const sb = window.supabase.createClient(supabaseUrl, supabaseAnon);
                                await sb.auth.signOut();
                            }
                        }
                        const res = await fetch("{{ route('auth.logout') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });
                        window.location.href = "{{ route('tools.index') }}";
                    } catch (e) {
                        window.location.href = "{{ route('tools.index') }}";
                    }
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
