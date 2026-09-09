<!-- Left Navigation Sidebar -->
<aside class="app-sidebar">
    <!-- STUDIO Section -->
    <div class="sidebar-heading">STUDIO</div>
    <nav class="tool-nav">
        <!-- Studio Overview -->
        <a href="{{ route('tools.overview') }}" class="tool-nav-item {{ ($activeTool ?? '') === 'overview' ? 'active' : '' }}">
            <i data-lucide="sparkles" class="tool-icon"></i>
            <span>Studio Overview</span>
        </a>
    </nav>

    <div class="sidebar-divider"></div>

    <!-- TOOLS Section -->
    <div class="sidebar-heading">TOOLS</div>
    <nav class="tool-nav">
        <!-- Image Generator -->
        <a href="{{ route('tools.image.index') }}" class="tool-nav-item {{ ($activeTool ?? '') === 'image-generator' ? 'active' : '' }}">
            <i data-lucide="image" class="tool-icon"></i>
            <span>Image Generator</span>
        </a>

        <!-- Text to Video Audio Generator -->
        <a href="{{ route('tools.video.index') }}" class="tool-nav-item {{ ($activeTool ?? '') === 'video-generator' ? 'active' : '' }}">
            <i data-lucide="video" class="tool-icon"></i>
            <span>Text to Video Audio</span>
        </a>

        <!-- Image to Video Generator -->
        <a href="{{ route('tools.image-to-video.index') }}" class="tool-nav-item {{ ($activeTool ?? '') === 'image-to-video' ? 'active' : '' }}">
            <i data-lucide="clapperboard" class="tool-icon"></i>
            <span>Image to Video</span>
        </a>
    </nav>

    <div class="sidebar-divider"></div>

    <!-- LIBRARY Section -->
    <div class="sidebar-heading">LIBRARY</div>
    <nav class="tool-nav">
        <a href="{{ route('library.index') }}" class="tool-nav-item {{ ($activeTool ?? '') === 'library' ? 'active' : '' }}">
            <i data-lucide="film" class="tool-icon"></i>
            <span>Library</span>
        </a>
    </nav>

    @if (auth()->check() && auth()->user()->isAdmin())
        <div class="sidebar-divider"></div>
        <div class="sidebar-heading" style="color: #818cf8;">ADMINISTRATION</div>
        <nav class="tool-nav">
            <a href="{{ route('admin.dashboard') }}" class="tool-nav-item" style="color: #818cf8;">
                <i data-lucide="shield-check" class="tool-icon" style="color: #818cf8;"></i>
                <span>Admin Panel</span>
            </a>
        </nav>
    @endif

    <!-- Sidebar Footer: User Profile & Session Controls (Column Layout) -->
    <div class="sidebar-footer">
        <template x-if="user">
            <div class="sidebar-user-block">
                <!-- User Avatar & Profile Link (Top) -->
                <a href="{{ route('profile.index') }}" class="sidebar-user-link {{ ($activeTool ?? '') === 'profile' ? 'active' : '' }}" title="View Profile">
                    <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.85rem; flex-shrink: 0;" x-text="user.name ? user.name.charAt(0).toUpperCase() : (user.email ? user.email.charAt(0).toUpperCase() : 'U')"></div>
                    <div class="sidebar-user-info">
                        <span class="sidebar-user-name" x-text="user.name || (user.email ? user.email.split('@')[0] : 'Creator')"></span>
                        <span class="sidebar-user-role">
                            <span class="sidebar-user-status-dot"></span>
                            <span>Profile</span>
                        </span>
                    </div>
                </a>

                <!-- Live Credits Badge from RDS User Balance -->
                <a href="{{ route('pricing') }}" class="sidebar-credit-pill" title="Available Credits — Click to Upgrade" style="display: flex; align-items: center; justify-content: space-between; background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: var(--radius-md); padding: 7px 10px; margin: 4px 0 6px 0; text-decoration: none; color: #a5b4fc; font-size: 0.8rem; font-weight: 600; transition: all 0.2s ease;">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="zap" style="width: 13px; height: 13px; fill: #fbbf24; color: #fbbf24;"></i>
                        <span>Credits</span>
                    </span>
                    <span style="font-weight: 700; color: #f8fafc;" x-text="user && user.credit_balance !== undefined ? Number(user.credit_balance).toLocaleString() : '{{ Auth::check() ? number_format(Auth::user()->credit_balance) : 0 }}'">
                        {{ Auth::check() ? number_format(Auth::user()->credit_balance) : 0 }}
                    </span>
                </a>

                <!-- Sign Out Button Directly Underneath (Bottom) -->
                <button type="button" 
                        class="sidebar-logout-btn" 
                        @click="handleLogout()" 
                        title="Sign Out of Session"
                        style="appearance: none; -webkit-appearance: none; -moz-appearance: none; outline: none; border: 1px solid rgba(255, 255, 255, 0.07); background: rgba(255, 255, 255, 0.03); color: var(--text-secondary); font-family: inherit; font-size: 0.8rem; font-weight: 600; padding: 7px 10px; border-radius: var(--radius-md); width: 100%; box-sizing: border-box; display: flex; align-items: center; justify-content: center; gap: 7px; cursor: pointer; transition: all 0.2s ease;">
                    <i data-lucide="log-out" style="width: 14px; height: 14px; color: var(--text-muted);"></i>
                    <span>Sign Out</span>
                </button>
            </div>
        </template>

        <template x-if="!user">
            <button type="button" class="btn-action btn-action-primary" @click="authModalOpen = true" style="width: 100%; justify-content: center; font-size: 0.82rem; padding: 8px 12px;">
                <i data-lucide="user" style="width: 14px; height: 14px;"></i>
                <span>Sign In / Register</span>
            </button>
        </template>
    </div>
</aside>
