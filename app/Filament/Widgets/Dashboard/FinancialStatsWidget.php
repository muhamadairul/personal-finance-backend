<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class FinancialStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now();

        $totalBalance = Wallet::sum('balance');

        $monthlyIncome = Transaction::where('type', 'income')
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');

        $monthlyExpense = Transaction::where('type', 'expense')
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');

        $savingsRatio = $monthlyIncome > 0
            ? round((($monthlyIncome - $monthlyExpense) / $monthlyIncome) * 100, 1)
            : 0;

        $volume24h = Transaction::where('created_at', '>=', now()->subDay())->sum('amount');

        return [
            Stat::make('Total Saldo Global', 'Rp ' . number_format($totalBalance, 0, ',', '.'))
                ->description('Akumulasi seluruh dompet')
                ->descriptionIcon('heroicon-o-wallet')
                ->color('primary')
                ->chart([7, 3, 5, 8, 4, 6, 9]),

            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($monthlyIncome, 0, ',', '.'))
                ->description($now->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->chart([3, 5, 7, 4, 6, 8, 5]),

            Stat::make('Pengeluaran Bulan Ini', 'Rp ' . number_format($monthlyExpense, 0, ',', '.'))
                ->description($now->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color('danger')
                ->chart([5, 4, 6, 3, 7, 5, 4]),

            Stat::make('Rasio Tabungan', $savingsRatio . '%')
                ->description($savingsRatio >= 0 ? 'Sisa dari pemasukan' : 'Pengeluaran melebihi pemasukan')
                ->descriptionIcon($savingsRatio >= 0 ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle')
                ->color($savingsRatio >= 20 ? 'success' : ($savingsRatio >= 0 ? 'warning' : 'danger'))
                ->chart([4, 6, 5, 7, 3, 8, 6]),
        ];
    }
}
