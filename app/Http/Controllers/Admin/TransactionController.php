<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TransactionController extends Controller
{
    /**
     * Display a listing of financial transactions.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');

        // Root-cause fix: If transactions table is missing, auto-migrate
        if (!Schema::hasTable('transactions')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::error('Auto-migration for transactions failed: ' . $e->getMessage());
            }
        }

        // Graceful fallback if database permissions prevent table creation
        if (!Schema::hasTable('transactions')) {
            $emptyPaginator = new LengthAwarePaginator([], 0, 20, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('admin.transactions.index', [
                'transactions' => $emptyPaginator,
                'search' => $search,
                'status' => $status,
                'stats' => [
                    'total_count' => 0,
                    'completed_count' => 0,
                    'total_volume' => 0.0,
                ],
                'migrationWarning' => 'Table `transactions` was not found and could not be automatically created. Please run migrations from System Settings.',
            ]);
        }

        $query = Transaction::with(['user', 'pricingPlan'])->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('paddle_transaction_id', 'like', "%{$search}%")
                  ->orWhere('paddle_subscription_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $transactions = $query->paginate(20)->withQueryString();

        $stats = [
            'total_count' => (int) Transaction::count(),
            'completed_count' => (int) Transaction::where('status', 'completed')->count(),
            'total_volume' => (float) Transaction::where('status', 'completed')->sum('amount'),
        ];

        return view('admin.transactions.index', [
            'transactions' => $transactions,
            'search' => $search,
            'status' => $status,
            'stats' => $stats,
        ]);
    }
}
