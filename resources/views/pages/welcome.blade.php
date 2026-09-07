@extends('layouts.app')

@section('title', 'Welcome to ' . ($siteTitle ?? 'IMGAI') . ' — Subscription Confirmed')

@section('content')
<div class="welcome-container" style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 48px 24px;">
    <div style="max-width: 620px; width: 100%; text-align: center; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(168, 85, 247, 0.3); box-shadow: 0 0 40px rgba(168, 85, 247, 0.15); border-radius: 24px; padding: 48px 36px;">
        <!-- Success Animated Check Icon -->
        <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; box-shadow: 0 0 25px rgba(16, 185, 129, 0.4);">
            <svg style="width: 36px; height: 36px; color: #ffffff;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 9999px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.25); color: #34d399; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px;">
            Payment Completed Successfully
        </div>

        <h1 style="font-size: 2.2rem; font-weight: 800; color: #ffffff; letter-spacing: -0.02em; margin-bottom: 12px;">
            Welcome to the Studio!
        </h1>

        <p style="color: #94a3b8; font-size: 1rem; line-height: 1.6; margin-bottom: 24px;">
            Your subscription has been processed and activated. Your AI generation credits and priority access are now active on your account.
        </p>

        @if (!empty($userEmail))
            <div style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 12px 18px; margin-bottom: 28px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; color: #cbd5e1;">
                <span style="color: #94a3b8;">Registered Email:</span>
                <strong style="color: #f8fafc;">{{ $userEmail }}</strong>
            </div>
        @endif

        <div style="margin-bottom: 24px;">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 16px; border-radius: 9999px; background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.3); color: #c084fc; font-size: 0.85rem; font-weight: 600;">
                <i data-lucide="loader-2" class="spin-icon" style="width: 14px; height: 14px; animation: spin 1s linear infinite;"></i>
                <span>Returning to Dashboard in <strong id="welcome-countdown" style="color: #ffffff; font-weight: 800; font-size: 0.95rem;">3</strong>s...</span>
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: center; gap: 14px; flex-wrap: wrap;">
            <a href="{{ route('tools.overview') }}" class="btn-primary" style="padding: 14px 32px; border-radius: 14px; font-size: 1rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: linear-gradient(135deg, #7c3aed 0%, #a855f7 50%, #c026d3 100%); color: #ffffff; box-shadow: 0 4px 20px rgba(168, 85, 247, 0.45); transition: all 0.2s ease;">
                <span>Continue to Dashboard</span>
                <i data-lucide="arrow-right" style="width: 18px; height: 18px;"></i>
            </a>

            <a href="{{ route('tools.index') }}" class="btn-secondary" style="padding: 14px 24px; border-radius: 14px; font-size: 0.95rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #cbd5e1;">
                <span>Launch Image Studio</span>
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let remainingSeconds = 3;
        const countdownElement = document.getElementById('welcome-countdown');
        const redirectTimer = setInterval(() => {
            remainingSeconds--;
            if (countdownElement) {
                countdownElement.textContent = String(remainingSeconds);
            }
            if (remainingSeconds <= 0) {
                clearInterval(redirectTimer);
                window.location.href = "{{ route('tools.overview') }}";
            }
        }, 1000);
    });
</script>
<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
@endsection
