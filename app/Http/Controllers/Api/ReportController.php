<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     * GET /api/reports/monthly
     * Returns income/expense trends for the last 6 months.
     */
    public function monthly(Request $request)
    {
        $user = $request->user();
        $now  = Carbon::now();

        $monthlyIncome  = [];
        $monthlyExpense = [];
        $monthLabels    = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $monthLabels[] = $date->translatedFormat('M');

            $income = Transaction::where('user_id', $user->id)
                ->where('type', 'income')
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('amount');

            $expense = Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('amount');

            $monthlyIncome[]  = (float) $income;
            $monthlyExpense[] = (float) $expense;
        }

        $totalIncome  = array_sum($monthlyIncome);
        $totalExpense = array_sum($monthlyExpense);

        return response()->json([
            'total_income'    => $totalIncome,
            'total_expense'   => $totalExpense,
            'net'             => $totalIncome - $totalExpense,
            'monthly_income'  => $monthlyIncome,
            'monthly_expense' => $monthlyExpense,
            'month_labels'    => $monthLabels,
        ]);
    }

    /**
     * GET /api/reports/category
     * Returns expense breakdown by category for the current month.
     */
    public function category(Request $request)
    {
        $user = $request->user();
        $now  = Carbon::now();

        $transactions = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->with('category')
            ->get();

        $breakdown = [];
        $colors    = [];

        foreach ($transactions->groupBy('category_id') as $categoryId => $group) {
            $category = $group->first()->category;
            if ($category) {
                $breakdown[$category->name] = (float) $group->sum('amount');
                $colors[$category->name]    = $category->color;
            }
        }

        // Sort by amount descending
        arsort($breakdown);

        return response()->json([
            'category_breakdown' => $breakdown,
            'category_colors'    => $colors,
        ]);
    }
}
