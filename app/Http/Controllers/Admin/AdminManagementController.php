<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminManagementController extends Controller
{
    /**
     * Display the Admin Management page.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));

        // Current Admins
        $admins = User::where('role', 'admin')->orderBy('name')->get();

        // Searchable users to promote
        $userQuery = User::where('role', '!=', 'admin')->orWhereNull('role');

        if ($search !== '') {
            $userQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $eligibleUsers = $userQuery->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('admin.admins.index', [
            'admins' => $admins,
            'eligibleUsers' => $eligibleUsers,
            'search' => $search,
            'totalAdmins' => $admins->count(),
        ]);
    }

    /**
     * Promote an existing user to Administrator.
     */
    public function promote(Request $request): JsonResponse|RedirectResponse
    {
        $userId = $request->input('user_id');
        $email = $request->input('email');

        $user = null;
        if ($userId) {
            $user = User::find($userId);
        } elseif ($email) {
            $user = User::where('email', $email)->first();
        }

        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'User not found in MySQL database.'], 404);
            }
            return redirect()->back()->with('error', 'User not found in database.');
        }

        if ($user->role === 'admin') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => "User {$user->email} is already an Administrator."]);
            }
            return redirect()->back()->with('info', "User {$user->email} is already an Administrator.");
        }

        $user->role = 'admin';
        $user->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "User {$user->name} ({$user->email}) successfully promoted to Administrator.",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ]);
        }

        return redirect()->back()->with('success', "User {$user->name} ({$user->email}) promoted to Administrator.");
    }

    /**
     * Demote an Administrator to standard user.
     */
    public function demote(Request $request): JsonResponse|RedirectResponse
    {
        $userId = $request->input('user_id');
        $email = $request->input('email');

        $user = null;
        if ($userId) {
            $user = User::find($userId);
        } elseif ($email) {
            $user = User::where('email', $email)->first();
        }

        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Administrator not found.'], 404);
            }
            return redirect()->back()->with('error', 'Administrator not found.');
        }

        // Safeguard: Prevent removing the last remaining Admin
        $adminCount = User::where('role', 'admin')->count();
        if ($adminCount <= 1) {
            $msg = 'Action blocked: Cannot remove administrator privileges from the last remaining Administrator.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }
            return redirect()->back()->with('error', $msg);
        }

        $user->role = 'user';
        $user->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Administrator role successfully removed for {$user->name} ({$user->email}).",
            ]);
        }

        return redirect()->back()->with('success', "Administrator privileges removed for {$user->name}.");
    }
}
