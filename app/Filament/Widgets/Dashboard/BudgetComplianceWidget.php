<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Budget;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class BudgetComplianceWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now();

        $budgets = Budget::where('month', $now->month)
            ->where('year', $now->year)
            ->get();

        $totalBudgets = $budgets->count();
        $compliant = 0;

        foreach ($budgets as $budget) {
            $spent = Transaction::where('user_id', $budget->user_id)
                ->where('category_id', $budget->category_id)
                ->where('type', 'expense')
                ->whereMonth('date', $budget->month)
                ->whereYear('date', $budget->year)
                ->sum('amount');

            if ($spent <= $budget->amount) {
                $compliant++;
            }
        }

        $complianceRate = $totalBudgets > 0
            ? round(($compliant / $totalBudgets) * 100, 1)
            : 0;

        return [
            Stat::make('Budget Tercapai', $compliant . ' / ' . $totalBudgets)
                ->description($complianceRate . '% di bawah limit')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color($complianceRate >= 70 ? 'success' : ($complianceRate >= 40 ? 'warning' : 'danger'))
                ->chart([3, 5, 4, 6, 7, 5, 8]),

            Stat::make('Total Budget Aktif', $totalBudgets)
                ->description($now->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('info')
                ->chart([2, 4, 3, 5, 4, 6, 5]),

            Stat::make('User dengan Budget', Budget::where('month', $now->month)->where('year', $now->year)->distinct('user_id')->count('user_id'))
                ->description('Mengatur anggaran')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('primary')
                ->chart([1, 3, 2, 4, 3, 5, 4]),
        ];
    }
}
