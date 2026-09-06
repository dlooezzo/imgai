<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditTransaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CreditTransactionController extends Controller
{
    /**
     * Display a listing of credit ledger entries.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $type = $request->input('type');
        $source = $request->input('source');
        $userId = $request->input('user_id');

        // Root-cause fix: If credit_transactions table is missing, auto-migrate
        if (!Schema::hasTable('credit_transactions')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::error('Auto-migration for credit_transactions failed: ' . $e->getMessage());
            }
        }

        // Graceful fallback if database permissions prevent table creation
        if (!Schema::hasTable('credit_transactions')) {
            $emptyPaginator = new LengthAwarePaginator([], 0, 25, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('admin.credits.index', [
                'creditTransactions' => $emptyPaginator,
                'search' => $search,
                'type' => $type,
                'source' => $source,
                'userId' => $userId,
                'stats' => [
                    'total_granted' => 0,
                    'total_deducted' => 0,
                    'total_entries' => 0,
                ],
                'migrationWarning' => 'Table `credit_transactions` was not found and could not be automatically created. Please run migrations from System Settings.',
            ]);
        }

        $query = CreditTransaction::with('user')->latest();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reference_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($source) {
            $query->where('source', $source);
        }

        $creditTransactions = $query->paginate(25)->withQueryString();

        $stats = [
            'total_granted' => (int) CreditTransaction::where('amount', '>', 0)->sum('amount'),
            'total_deducted' => (int) abs(CreditTransaction::where('amount', '<', 0)->sum('amount')),
            'total_entries' => (int) CreditTransaction::count(),
        ];

        return view('admin.credits.index', [
            'creditTransactions' => $creditTransactions,
            'search' => $search,
            'type' => $type,
            'source' => $source,
            'userId' => $userId,
            'stats' => $stats,
        ]);
    }
}
