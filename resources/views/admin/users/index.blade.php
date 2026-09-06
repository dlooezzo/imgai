@extends('admin.layouts.app')

@section('title', 'Users Management')
@section('breadcrumb', 'Users')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                User Accounts
            </h1>
            <p style="color: #94a3b8; font-size: 0.88rem;">
                Browse registered users, inspect creation activity, and manage privileges.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="{{ route('admin.admins.index') }}" class="btn-admin btn-admin-primary">
                <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i>
                <span>Manage Administrators ({{ $totalAdminsCount }})</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <form method="GET" action="{{ route('admin.users.index') }}" class="admin-filter-bar">
        <div class="admin-search-box">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, email, or ID...">
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <select name="role" class="admin-select" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <option value="user" {{ $role === 'user' ? 'selected' : '' }}>Standard Users</option>
                <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Administrators</option>
            </select>

            <select name="status" class="admin-select" onchange="this.form.submit()">
                <option value="">All Email Statuses</option>
                <option value="verified" {{ $status === 'verified' ? 'selected' : '' }}>Verified</option>
                <option value="unverified" {{ $status === 'unverified' ? 'selected' : '' }}>Unverified</option>
            </select>

            @if ($search || $role || $status)
                <a href="{{ route('admin.users.index') }}" class="btn-admin btn-admin-secondary" style="padding: 8px 12px; font-size: 0.82rem;">
                    Clear Filters
                </a>
            @endif
        </div>
    </form>

    <!-- Users Table -->
    <div class="admin-card" style="padding: 0; overflow: hidden;">
        <div class="admin-table-container" style="border: none; border-radius: 0;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Email Status</th>
                        <th>Generations</th>
                        <th>Registered</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #4f46e5, #06b6d4); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.88rem; color: #fff; flex-shrink: 0;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div style="display: flex; flex-direction: column;">
                                        <a href="{{ route('admin.users.show', $user->id) }}" style="color: #f8fafc; font-weight: 600; text-decoration: none;">
                                            {{ $user->name }}
                                        </a>
                                        <span style="color: #64748b; font-size: 0.78rem;">
                                            {{ $user->email }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($user->role === 'admin')
                                    <span class="badge badge-admin">
                                        <i data-lucide="shield-check" style="width: 12px; height: 12px;"></i> Admin
                                    </span>
                                @else
                                    <span class="badge badge-user">
                                        <i data-lucide="user" style="width: 12px; height: 12px;"></i> User
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($user->email_verified_at)
                                    <span class="badge badge-success">Verified</span>
                                @else
                                    <span class="badge badge-warning">Unverified</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 6px; font-size: 0.84rem;">
                                    <span style="font-weight: 700; color: #f8fafc;">{{ $user->total_generations_count }}</span>
                                    <span style="color: #64748b; font-size: 0.74rem;">({{ $user->images_count }} imgs / {{ $user->videos_count }} vids)</span>
                                </div>
                            </td>
                            <td style="font-size: 0.8rem; color: #94a3b8; white-space: nowrap;">
                                {{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="btn-admin btn-admin-secondary" style="padding: 6px 12px; font-size: 0.78rem;">
                                    <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                    <span>Details</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                                No users matched your search criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--admin-border);">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
