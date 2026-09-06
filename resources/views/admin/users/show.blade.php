@extends('admin.layouts.app')

@section('title', "User: {$user->name}")
@section('breadcrumb', 'Users / ' . $user->name)

@section('content')
<div x-data="{ activeTab: 'images' }" style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Back button & Top bar -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
        <a href="{{ route('admin.users.index') }}" class="btn-admin btn-admin-secondary" style="padding: 6px 12px; font-size: 0.82rem;">
            <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
            <span>Back to Users</span>
        </a>

        <!-- Admin Role Management Action -->
        <div>
            @if ($user->role === 'admin')
                <form method="POST" action="{{ route('admin.admins.demote') }}" onsubmit="return confirm('Remove administrator privileges from this user?');" style="display: inline;">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    <button type="submit" class="btn-admin btn-admin-danger" style="font-size: 0.82rem;">
                        <i data-lucide="shield-alert" style="width: 14px; height: 14px;"></i>
                        <span>Remove Admin Privileges</span>
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.admins.promote') }}" onsubmit="return confirm('Promote this user to Administrator? They will have full access to the admin dashboard.');" style="display: inline;">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    <button type="submit" class="btn-admin btn-admin-primary" style="font-size: 0.82rem;">
                        <i data-lucide="shield-check" style="width: 14px; height: 14px;"></i>
                        <span>Promote to Administrator</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- User Profile Overview Card -->
    <div class="admin-card">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
            <div style="display: flex; align-items: center; gap: 18px;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #06b6d4); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem; color: #fff; box-shadow: 0 0 20px rgba(99, 102, 241, 0.4);">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                        <h2 style="font-size: 1.3rem; font-weight: 800; color: #f8fafc;">{{ $user->name }}</h2>
                        @if ($user->role === 'admin')
                            <span class="badge badge-admin">
                                <i data-lucide="shield-check" style="width: 12px; height: 12px;"></i> Administrator
                            </span>
                        @else
                            <span class="badge badge-user">
                                <i data-lucide="user" style="width: 12px; height: 12px;"></i> Standard User
                            </span>
                        @endif
                    </div>
                    <div style="display: flex; align-items: center; gap: 14px; color: #94a3b8; font-size: 0.84rem; flex-wrap: wrap;">
                        <span><i data-lucide="mail" style="width: 14px; height: 14px; display: inline; vertical-align: middle;"></i> {{ $user->email }}</span>
                        <span><i data-lucide="calendar" style="width: 14px; height: 14px; display: inline; vertical-align: middle;"></i> Registered: {{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}</span>
                        <span><i data-lucide="fingerprint" style="width: 14px; height: 14px; display: inline; vertical-align: middle;"></i> ID: <code class="font-mono" style="color: #cbd5e1;">{{ $user->id }}</code></span>
                    </div>
                </div>
            </div>

            <!-- Email Verification Status Badge -->
            <div>
                @if ($user->email_verified_at)
                    <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; padding: 6px 14px; border-radius: 8px; font-size: 0.8rem; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="check-circle-2" style="width: 14px; height: 14px;"></i>
                        <span>Email Confirmed</span>
                    </div>
                @else
                    <div style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; padding: 6px 14px; border-radius: 8px; font-size: 0.8rem; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                        <span>Email Unverified</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- User Generation & Credit Metrics -->
    <div class="stats-grid" style="margin-bottom: 0; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Generation Credits</span>
                <span class="stat-value" style="color: #34d399;">{{ number_format($user->credit_balance) }}</span>
                <span class="stat-hint">Current available balance</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #34d399; background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.25);">
                <i data-lucide="coins"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Total Creations</span>
                <span class="stat-value">{{ $stats['total_creations'] }}</span>
                <span class="stat-hint">Combined images & videos</span>
            </div>
            <div class="stat-icon-wrapper">
                <i data-lucide="layers"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Images Created</span>
                <span class="stat-value">{{ $stats['total_images'] }}</span>
                <span class="stat-hint">{{ $stats['successful_images'] }} successful / {{ $stats['failed_images'] }} failed</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #38bdf8; background: rgba(6, 182, 212, 0.12); border-color: rgba(6, 182, 212, 0.25);">
                <i data-lucide="image"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <span class="stat-label">Videos Created</span>
                <span class="stat-value">{{ $stats['total_videos'] }}</span>
                <span class="stat-hint">{{ $stats['successful_videos'] }} successful / {{ $stats['failed_videos'] }} failed</span>
            </div>
            <div class="stat-icon-wrapper" style="color: #c084fc; background: rgba(139, 92, 246, 0.12); border-color: rgba(139, 92, 246, 0.25);">
                <i data-lucide="video"></i>
            </div>
        </div>
    </div>

    <!-- Subscription & Paddle Billing Information -->
    <div class="admin-card">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.06); padding-bottom: 14px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(168, 85, 247, 0.15); display: flex; align-items: center; justify-content: center; color: #c084fc;">
                    <i data-lucide="credit-card" style="width: 18px; height: 18px;"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 800; color: #f8fafc; margin: 0;">Subscription & Paddle Billing</h3>
                    <p style="font-size: 0.78rem; color: #94a3b8; margin: 2px 0 0;">Paddle customer metadata, active tiers, and recent credits ledger.</p>
                </div>
            </div>

            @if ($subscription && $subscription->isActive())
                <span style="padding: 4px 12px; border-radius: 9999px; background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 0.76rem; font-weight: 700; text-transform: uppercase;">
                    Active {{ $subscription->pricingPlan?->name ?? 'Plan' }}
                </span>
            @else
                <span style="padding: 4px 12px; border-radius: 9999px; background: rgba(148, 163, 184, 0.12); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2); font-size: 0.76rem; font-weight: 700; text-transform: uppercase;">
                    No Active Subscription
                </span>
            @endif
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
            <div style="padding: 12px 14px; border-radius: 8px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.04);">
                <div style="font-size: 0.74rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Paddle Customer ID</div>
                <code style="font-size: 0.84rem; color: #818cf8;">{{ $user->paddle_customer_id ?: 'Not linked yet' }}</code>
            </div>

            <div style="padding: 12px 14px; border-radius: 8px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.04);">
                <div style="font-size: 0.74rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Subscription ID</div>
                <code style="font-size: 0.84rem; color: #c084fc;">{{ $subscription?->paddle_subscription_id ?: '—' }}</code>
            </div>

            <div style="padding: 12px 14px; border-radius: 8px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.04);">
                <div style="font-size: 0.74rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Billing Period</div>
                <span style="font-size: 0.88rem; color: #f8fafc; font-weight: 600; text-transform: capitalize;">{{ $subscription?->billing_period ?: '—' }}</span>
            </div>

            <div style="padding: 12px 14px; border-radius: 8px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.04);">
                <div style="font-size: 0.74rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Next Renewal / Expiry</div>
                <span style="font-size: 0.88rem; color: #cbd5e1;">{{ $subscription?->next_billed_at ? $subscription->next_billed_at->format('M d, Y') : ($subscription?->canceled_at ? 'Canceled' : '—') }}</span>
            </div>
        </div>

        @if ($creditTransactions && $creditTransactions->count() > 0)
            <div style="border-top: 1px solid rgba(255, 255, 255, 0.06); padding-top: 16px;">
                <div style="font-size: 0.84rem; font-weight: 700; color: #cbd5e1; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                    <span>Recent Credit Activity</span>
                    <a href="{{ route('admin.credits.index', ['user_id' => $user->id]) }}" style="font-size: 0.76rem; color: #818cf8; text-decoration: none;">View full history &rarr;</a>
                </div>
                <div class="admin-table-container" style="border: none; margin: 0;">
                    <table class="admin-table" style="font-size: 0.82rem;">
                        <thead>
                            <tr>
                                <th>Amount</th>
                                <th>Balance After</th>
                                <th>Source</th>
                                <th>Description</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($creditTransactions as $ct)
                                <tr>
                                    <td style="font-weight: 800; color: {{ $ct->amount > 0 ? '#34d399' : '#f87171' }};">
                                        {{ $ct->amount > 0 ? '+' . $ct->amount : $ct->amount }}
                                    </td>
                                    <td style="font-weight: 600; color: #e2e8f0;">{{ number_format($ct->balance_after) }}</td>
                                    <td style="color: #94a3b8; text-transform: uppercase; font-size: 0.72rem;">{{ str_replace('_', ' ', $ct->source) }}</td>
                                    <td style="color: #cbd5e1;">{{ $ct->description }}</td>
                                    <td style="color: #64748b; font-size: 0.76rem;">{{ $ct->created_at->format('M d, H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>


    <!-- User Creations Tabbed Navigation -->
    <div>
        <div style="display: flex; gap: 8px; border-bottom: 1px solid var(--admin-border); padding-bottom: 12px;">
            <button type="button" class="btn-admin" :class="activeTab === 'images' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="activeTab = 'images'">
                <i data-lucide="image" style="width: 16px; height: 16px;"></i>
                <span>Images ({{ $imageGenerations->total() }})</span>
            </button>
            <button type="button" class="btn-admin" :class="activeTab === 'videos' ? 'btn-admin-primary' : 'btn-admin-secondary'" @click="activeTab = 'videos'">
                <i data-lucide="video" style="width: 16px; height: 16px;"></i>
                <span>Videos ({{ $videoGenerations->total() }})</span>
            </button>
        </div>

        <!-- Images Section -->
        <div x-show="activeTab === 'images'" style="margin-top: 20px;">
            @if ($imageGenerations->count() > 0)
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;">
                    @foreach ($imageGenerations as $img)
                        <div class="admin-card" style="padding: 12px; display: flex; flex-direction: column; gap: 10px;">
                            <div style="aspect-ratio: 1; border-radius: 8px; overflow: hidden; background: #07090e; position: relative;">
                                @if ($img->image_url)
                                    <img src="{{ $img->image_url }}" alt="{{ $img->prompt }}" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                                @else
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 0.78rem;">
                                        No preview
                                    </div>
                                @endif
                                <div style="position: absolute; top: 6px; right: 6px;">
                                    @if ($img->status === 'succeeded')
                                        <span class="badge badge-success" style="font-size: 0.68rem;">Ready</span>
                                    @elseif ($img->status === 'failed')
                                        <span class="badge badge-danger" style="font-size: 0.68rem;">Failed</span>
                                    @else
                                        <span class="badge badge-warning" style="font-size: 0.68rem;">{{ ucfirst($img->status) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div style="font-size: 0.78rem; color: #cbd5e1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $img->prompt }}">
                                {{ $img->prompt }}
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.72rem; color: #64748b;">
                                <span>{{ $img->aspect_ratio }}</span>
                                <span>{{ $img->created_at ? $img->created_at->format('M d') : '' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="margin-top: 16px;">
                    {{ $imageGenerations->links() }}
                </div>
            @else
                <div class="admin-card" style="text-align: center; padding: 40px; color: #64748b;">
                    No images created by this user yet.
                </div>
            @endif
        </div>

        <!-- Videos Section -->
        <div x-show="activeTab === 'videos'" style="margin-top: 20px;">
            @if ($videoGenerations->count() > 0)
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px;">
                    @foreach ($videoGenerations as $vid)
                        <div class="admin-card" style="padding: 12px; display: flex; flex-direction: column; gap: 10px;">
                            <div style="aspect-ratio: 16/9; border-radius: 8px; overflow: hidden; background: #07090e; position: relative;">
                                @if ($vid->video_url)
                                    <video src="{{ $vid->video_url }}" controls preload="none" style="width: 100%; height: 100%; object-fit: cover;"></video>
                                @else
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 0.78rem;">
                                        No video available
                                    </div>
                                @endif
                                <div style="position: absolute; top: 6px; right: 6px;">
                                    @if ($vid->status === 'succeeded')
                                        <span class="badge badge-success" style="font-size: 0.68rem;">Ready</span>
                                    @elseif ($vid->status === 'failed')
                                        <span class="badge badge-danger" style="font-size: 0.68rem;">Failed</span>
                                    @else
                                        <span class="badge badge-warning" style="font-size: 0.68rem;">{{ ucfirst($vid->status) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div style="font-size: 0.78rem; color: #cbd5e1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $vid->prompt }}">
                                {{ $vid->prompt }}
                            </div>
                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.72rem; color: #64748b;">
                                <span>{{ $vid->generation_type === 'image-to-video' ? 'Wan 2.2' : 'Hunyuan' }}</span>
                                <span>{{ $vid->created_at ? $vid->created_at->format('M d') : '' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="margin-top: 16px;">
                    {{ $videoGenerations->links() }}
                </div>
            @else
                <div class="admin-card" style="text-align: center; padding: 40px; color: #64748b;">
                    No videos created by this user yet.
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
