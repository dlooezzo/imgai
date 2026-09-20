<style>
.public-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    background: #06080c;
    padding: 40px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    width: 100%;
}
@media (max-width: 768px) {
    .public-footer {
        padding: 30px 16px 88px; /* Extra padding for mobile bottom nav */
        align-items: flex-start;
        background: rgba(6, 8, 12, 0.9);
    }
    .public-footer > div:last-child {
        display: flex !important; 
        flex-wrap: wrap; 
        gap: 10px 16px !important;
    }
}
</style>

<footer class="public-footer">
    <div style="display: flex; align-items: center; gap: 10px;">
        <div class="brand-logo-icon" style="width: 28px; height: 28px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #06b6d4 100%); display: flex; align-items: center; justify-content: center; border-radius: 6px; box-shadow: 0 0 10px rgba(99, 102, 241, 0.4);">
            <i data-lucide="sparkles" style="width: 15px; height: 15px; color: #ffffff;"></i>
        </div>
        <span style="font-size: 0.85rem; color: #64748b;">
            &copy; {{ date('Y') }} {{ \App\Models\SiteSetting::get('site_title', config('app.name', 'Cinematic Studio')) }}. All rights reserved.
        </span>
    </div>

    <!-- Footer Links -->
    <div style="display: flex; gap: 20px; font-size: 0.82rem;">
        <a href="{{ url('terms') }}" style="color: #94a3b8; text-decoration: none; transition: color 0.15s;" onmouseover="this.style.color='#f8fafc'" onmouseout="this.style.color='#94a3b8'">Terms</a>
        <a href="{{ url('privacy') }}" style="color: #94a3b8; text-decoration: none; transition: color 0.15s;" onmouseover="this.style.color='#f8fafc'" onmouseout="this.style.color='#94a3b8'">Privacy</a>
    </div>
</footer>
