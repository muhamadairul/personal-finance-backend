<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $now  = Carbon::now();

        // Wallets
        $wallets = $user->wallets()->orderBy('created_at')->get();
        $totalBalance = $wallets->sum('balance');

        // Monthly income & expense
        $monthlyIncome = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');

        $monthlyExpense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');

        // Weekly expenses (last 7 days)
        $weeklyExpenses = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dayExpense = Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->whereDate('date', $date->format('Y-m-d'))
                ->sum('amount');
            $weeklyExpenses[] = (float) $dayExpense;
        }

        // Recent transactions (last 5)
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['category', 'wallet'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_balance'       => (float) $totalBalance,
            'monthly_income'      => (float) $monthlyIncome,
            'monthly_expense'     => (float) $monthlyExpense,
            'weekly_expenses'     => $weeklyExpenses,
            'recent_transactions' => TransactionResource::collection($recentTransactions),
            'wallets'             => WalletResource::collection($wallets),
        ]);
    }
}
