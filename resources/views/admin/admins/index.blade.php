@extends('admin.layouts.app')

@section('title', 'Admin Management')
@section('breadcrumb', 'Admin Management')

@section('content')
<div style="display: flex; flex-direction: column; gap: 28px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                Administrator Management
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Control platform privileges. Promote existing Supabase users to Administrator or revoke administrative roles.
            </p>
        </div>
    </div>

    <!-- Security Information Banner -->
    <div style="background: rgba(99, 102, 241, 0.1); border: 1px solid var(--admin-border-accent); border-radius: 12px; padding: 18px 22px; display: flex; align-items: flex-start; gap: 14px;">
        <i data-lucide="shield-alert" style="width: 22px; height: 22px; color: var(--admin-primary); flex-shrink: 0; margin-top: 2px;"></i>
        <div style="font-size: 0.86rem; color: #cbd5e1; line-height: 1.5;">
            <strong style="color: #fff;">Role Security Policy:</strong> Public registration always creates standard user accounts. Administrators can only be created by promoting existing registered accounts through this panel or via the server CLI command <code class="font-mono" style="color: var(--admin-cyan); background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;">php artisan admin:promote {email}</code>. Built-in safeguards prevent removing the last remaining administrator.
        </div>
    </div>

    <!-- Current Administrators Section -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">
                    <i data-lucide="shield-check" style="width: 18px; height: 18px; color: var(--admin-emerald);"></i>
                    <span>Active Administrators ({{ $admins->count() }})</span>
                </h2>
                <p class="admin-card-subtitle">Accounts with full administrative permissions</p>
            </div>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Administrator</th>
                        <th>Email</th>
                        <th>Admin Status</th>
                        <th>Account Created</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($admins as $admin)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #06b6d4); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; color: #fff; flex-shrink: 0;">
                                        {{ strtoupper(substr($admin->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $admin->id) }}" style="color: #f8fafc; font-weight: 600; text-decoration: none;">
                                            {{ $admin->name }}
                                        </a>
                                        @if (auth()->id() === $admin->id || (session('supabase_user') && session('supabase_user')['email'] === $admin->email))
                                            <span style="font-size: 0.72rem; color: var(--admin-cyan); margin-left: 6px;">(You)</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="color: #cbd5e1; font-size: 0.85rem;">
                                {{ $admin->email }}
                            </td>
                            <td>
                                <span class="badge badge-admin">
                                    <i data-lucide="shield-check" style="width: 12px; height: 12px;"></i> Administrator
                                </span>
                            </td>
                            <td style="font-size: 0.8rem; color: #94a3b8;">
                                {{ $admin->created_at ? $admin->created_at->format('M d, Y') : '—' }}
                            </td>
                            <td style="text-align: right;">
                                @if ($admins->count() > 1)
                                    <form method="POST" action="{{ route('admin.admins.demote') }}" onsubmit="return confirm('Are you sure you want to remove administrator privileges from {{ $admin->name }} ({{ $admin->email }})?');" style="display: inline;">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $admin->id }}">
                                        <button type="submit" class="btn-admin btn-admin-danger" style="padding: 6px 12px; font-size: 0.76rem;">
                                            <i data-lucide="shield-minus" style="width: 14px; height: 14px;"></i>
                                            <span>Remove Role</span>
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size: 0.75rem; color: #64748b;" title="Cannot remove the only administrator">
                                        Protected (Sole Admin)
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Promote Existing User Section -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2 class="admin-card-title">
                    <i data-lucide="user-plus" style="width: 18px; height: 18px; color: var(--admin-cyan);"></i>
                    <span>Promote Existing User to Administrator</span>
                </h2>
                <p class="admin-card-subtitle">Search registered users to grant administrator privileges</p>
            </div>
        </div>

        <!-- Search input -->
        <form method="GET" action="{{ route('admin.admins.index') }}" style="margin-bottom: 18px;">
            <div class="admin-search-box" style="max-width: 450px;">
                <i data-lucide="search"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search eligible users by name or email...">
            </div>
        </form>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Current Role</th>
                        <th>Registered</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($eligibleUsers as $user)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem; color: #fff;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <span style="font-weight: 500; color: #f8fafc;">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td style="color: #cbd5e1; font-size: 0.85rem;">
                                {{ $user->email }}
                            </td>
                            <td>
                                <span class="badge badge-user">Standard User</span>
                            </td>
                            <td style="font-size: 0.8rem; color: #94a3b8;">
                                {{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}
                            </td>
                            <td style="text-align: right;">
                                <form method="POST" action="{{ route('admin.admins.promote') }}" onsubmit="return confirm('Promote {{ $user->name }} ({{ $user->email }}) to Administrator? This grants complete control over the admin dashboard.');" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <button type="submit" class="btn-admin btn-admin-primary" style="padding: 6px 12px; font-size: 0.76rem;">
                                        <i data-lucide="shield-plus" style="width: 14px; height: 14px;"></i>
                                        <span>Promote to Admin</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 32px; color: #64748b;">
                                No standard users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($eligibleUsers->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--admin-border);">
                {{ $eligibleUsers->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
