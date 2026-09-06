<aside class="admin-sidebar" :class="{ 'mobile-open': sidebarOpen }">
    <!-- Sidebar Header / Brand -->
    @php
        $sidebarSiteTitle = \App\Models\SiteSetting::get('site_title', 'IMGAI');
        $sidebarSiteLogo = \App\Models\SiteSetting::get('site_logo_icon') ?: \App\Models\SiteSetting::get('site_logo');
    @endphp
    <div class="admin-sidebar-header">
        <a href="{{ route('admin.dashboard') }}" class="admin-brand">
            @if ($sidebarSiteLogo)
                <img src="{{ $sidebarSiteLogo }}" alt="{{ $sidebarSiteTitle }}" style="max-height: 28px; max-width: 28px; border-radius: 6px; object-fit: contain;">
            @else
                <div class="admin-brand-icon">
                    <i data-lucide="sparkles" style="width: 20px; height: 20px; color: #fff;"></i>
                </div>
            @endif
            <div class="admin-brand-text">
                <span class="admin-brand-title">{{ $sidebarSiteTitle }}</span>
                <span class="admin-brand-badge">Control Center</span>
            </div>
        </a>
    </div>

    <!-- Sidebar Navigation Menu -->
    <nav class="admin-nav">
        <!-- Overview -->
        <div>
            <div class="admin-nav-section-title">Overview</div>
            <ul class="admin-nav-list">
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.branding.index') }}" class="admin-nav-link {{ request()->routeIs('admin.branding.*') ? 'active' : '' }}">
                        <i data-lucide="palette"></i>
                        <span>Branding & Logo</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.hero-showcase.index') }}" class="admin-nav-link {{ request()->routeIs('admin.hero-showcase.*') ? 'active' : '' }}">
                        <i data-lucide="clapperboard"></i>
                        <span>Hero Showcase</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Billing & Pricing -->
        <div>
            <div class="admin-nav-section-title">Billing & Pricing</div>
            <ul class="admin-nav-list">
                <li>
                    <a href="{{ route('admin.pricing.index') }}" class="admin-nav-link {{ request()->routeIs('admin.pricing.*') ? 'active' : '' }}">
                        <i data-lucide="tag"></i>
                        <span>Pricing Plans</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.subscriptions.index') }}" class="admin-nav-link {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
                        <i data-lucide="repeat"></i>
                        <span>Subscriptions</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.transactions.index') }}" class="admin-nav-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
                        <i data-lucide="receipt"></i>
                        <span>Transactions</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.credits.index') }}" class="admin-nav-link {{ request()->routeIs('admin.credits.*') ? 'active' : '' }}">
                        <i data-lucide="coins"></i>
                        <span>Credit Ledger</span>
                    </a>
                </li>
            </ul>
        </div>


        <!-- Users -->
        <div>
            <div class="admin-nav-section-title">Users</div>
            <ul class="admin-nav-list">
                <li>
                    <a href="{{ route('admin.users.index') }}" class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i data-lucide="users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.admins.index') }}" class="admin-nav-link {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                        <i data-lucide="shield-check"></i>
                        <span>Admin Management</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Pages (CMS) & SEO -->
        <div>
            <div class="admin-nav-section-title">Content & SEO</div>
            <ul class="admin-nav-list">
                <li>
                    <a href="{{ route('admin.pages.index') }}" class="admin-nav-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">
                        <i data-lucide="file-code"></i>
                        <span>Pages</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.seo.index') }}" class="admin-nav-link {{ (request()->routeIs('admin.seo.*') && !request()->routeIs('admin.seo.articles.*')) ? 'active' : '' }}">
                        <i data-lucide="search"></i>
                        <span>SEO Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.seo.articles.index') }}" class="admin-nav-link {{ request()->routeIs('admin.seo.articles.*') ? 'active' : '' }}">
                        <i data-lucide="newspaper"></i>
                        <span>Tool Articles</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Generations -->
        <div>
            <div class="admin-nav-section-title">Generations</div>
            <ul class="admin-nav-list">
                <li>
                    <a href="{{ route('admin.generations.index') }}" class="admin-nav-link {{ request()->routeIs('admin.generations.*') ? 'active' : '' }}">
                        <i data-lucide="layers"></i>
                        <span>All Generations</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.images.index') }}" class="admin-nav-link {{ request()->routeIs('admin.images.*') ? 'active' : '' }}">
                        <i data-lucide="image"></i>
                        <span>Images</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.videos.index') }}" class="admin-nav-link {{ request()->routeIs('admin.videos.*') ? 'active' : '' }}">
                        <i data-lucide="video"></i>
                        <span>Videos</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Storage -->
        <div>
            <div class="admin-nav-section-title">Storage</div>
            <ul class="admin-nav-list">
                <li>
                    <a href="{{ route('admin.storage.index') }}" class="admin-nav-link {{ request()->routeIs('admin.storage.*') ? 'active' : '' }}">
                        <i data-lucide="hard-drive"></i>
                        <span>Cloudflare R2</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- AI -->
        <div>
            <div class="admin-nav-section-title">AI Engine</div>
            <ul class="admin-nav-list">
                <li>
                    <a href="{{ route('admin.models.index') }}" class="admin-nav-link {{ request()->routeIs('admin.models.*') ? 'active' : '' }}">
                        <i data-lucide="cpu"></i>
                        <span>AI Models</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.api-market.index') }}" class="admin-nav-link {{ request()->routeIs('admin.api-market.*') ? 'active' : '' }}">
                        <i data-lucide="key-round"></i>
                        <span>API Market</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- System -->
        <div>
            <div class="admin-nav-section-title">System</div>
            <ul class="admin-nav-list">
                <li>
                    <a href="{{ route('admin.supabase.index') }}" class="admin-nav-link {{ request()->routeIs('admin.supabase.*') ? 'active' : '' }}">
                        <i data-lucide="database"></i>
                        <span>Supabase Auth</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.logs.index') }}" class="admin-nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                        <i data-lucide="file-text"></i>
                        <span>System Logs</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.system-status.index') }}" class="admin-nav-link {{ request()->routeIs('admin.system-status.*') ? 'active' : '' }}">
                        <i data-lucide="activity"></i>
                        <span>System Status</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.settings.index') }}" class="admin-nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <i data-lucide="settings"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar Footer with Current Admin -->
    @php
        $currentAuthUser = auth()->user() ?? (session('supabase_user') ? (object)[
            'name' => session('supabase_user')['name'] ?? 'Admin',
            'email' => session('supabase_user')['email'] ?? 'admin@imgai.local',
        ] : (object)['name' => 'Admin User', 'email' => 'admin@imgai.local']);
        $initial = strtoupper(substr($currentAuthUser->name ?? 'A', 0, 1));
    @endphp
    <div class="admin-sidebar-footer">
        <div class="admin-user-card-block">
            <div class="admin-avatar">{{ $initial }}</div>
            <div class="admin-user-info">
                <span class="admin-user-name" title="{{ $currentAuthUser->name ?? 'Admin' }}">{{ $currentAuthUser->name ?? 'Admin' }}</span>
                <span class="admin-user-role">
                    <span class="admin-role-indicator"></span>
                    <span>Admin Profile</span>
                </span>
            </div>
        </div>

        <button type="button" 
                class="btn-admin-logout-full" 
                @click="handleLogout()" 
                title="Logout from Admin"
                style="appearance: none; -webkit-appearance: none; -moz-appearance: none; outline: none; border: 1px solid rgba(239, 68, 68, 0.25); background: rgba(239, 68, 68, 0.08); color: #f87171; font-family: inherit; font-size: 0.8rem; font-weight: 600; padding: 8px 12px; border-radius: 8px; width: 100%; box-sizing: border-box; display: flex; align-items: center; justify-content: center; gap: 7px; cursor: pointer; transition: all 0.2s ease;">
            <i data-lucide="log-out" style="width: 15px; height: 15px; color: #f87171;"></i>
            <span>Sign Out</span>
        </button>
    </div>
</aside>
