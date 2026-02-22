<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now();

        $monthlyIncome = Transaction::where('type', 'income')
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');

        $monthlyExpense = Transaction::where('type', 'expense')
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('amount');

        $totalBalance = Wallet::sum('balance');

        return [
            Stat::make('Total Saldo', 'Rp ' . number_format($totalBalance, 0, ',', '.'))
                ->description('Seluruh dompet')
                ->descriptionIcon('heroicon-o-wallet')
                ->color('primary'),
            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($monthlyIncome, 0, ',', '.'))
                ->description($now->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success'),
            Stat::make('Pengeluaran Bulan Ini', 'Rp ' . number_format($monthlyExpense, 0, ',', '.'))
                ->description($now->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color('danger'),
            Stat::make('Total Pengguna', User::count())
                ->description('Terdaftar')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),
        ];
    }
}
