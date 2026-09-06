<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');

        // Root-cause fix: If subscriptions table is missing, auto-migrate
        if (!Schema::hasTable('subscriptions')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::error('Auto-migration for subscriptions failed: ' . $e->getMessage());
            }
        }

        // Graceful fallback if database permissions prevent table creation
        if (!Schema::hasTable('subscriptions')) {
            $emptyPaginator = new LengthAwarePaginator([], 0, 15, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('admin.subscriptions.index', [
                'subscriptions' => $emptyPaginator,
                'search' => $search,
                'status' => $status,
                'stats' => [
                    'total' => 0,
                    'active' => 0,
                    'canceled' => 0,
                    'past_due' => 0,
                ],
                'migrationWarning' => 'Table `subscriptions` was not found and could not be automatically created. Please run migrations from System Settings.',
            ]);
        }

        $query = Subscription::with(['user', 'pricingPlan'])->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('paddle_subscription_id', 'like', "%{$search}%")
                  ->orWhere('paddle_customer_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($status && in_array($status, ['active', 'trialing', 'past_due', 'paused', 'canceled'], true)) {
            $query->where('status', $status);
        }

        $subscriptions = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => (int) Subscription::count(),
            'active' => (int) Subscription::whereIn('status', ['active', 'trialing'])->count(),
            'canceled' => (int) Subscription::where('status', 'canceled')->count(),
            'past_due' => (int) Subscription::where('status', 'past_due')->count(),
        ];

        return view('admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'search' => $search,
            'status' => $status,
            'stats' => $stats,
        ]);
    }
}
