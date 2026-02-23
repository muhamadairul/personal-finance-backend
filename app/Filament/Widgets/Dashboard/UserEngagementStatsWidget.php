<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class UserEngagementStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $now = Carbon::now();

        // Active users: users who have transactions in the last 30 days
        $activeUsers = Transaction::where('date', '>=', $now->copy()->subDays(30))
            ->distinct('user_id')
            ->count('user_id');

        // New users this week
        $newUsersThisWeek = User::where('created_at', '>=', $now->copy()->startOfWeek())
            ->count();

        // Retention rate: users registered > 7 days ago who have transactions in the last 7 days
        $usersOlderThan7Days = User::where('created_at', '<=', $now->copy()->subDays(7))->count();
        $retainedUsers = 0;
        if ($usersOlderThan7Days > 0) {
            $retainedUsers = User::where('created_at', '<=', $now->copy()->subDays(7))
                ->whereHas('transactions', fn($q) => $q->where('date', '>=', $now->copy()->subDays(7)))
                ->count();
        }
        $retentionRate = $usersOlderThan7Days > 0
            ? round(($retainedUsers / $usersOlderThan7Days) * 100, 1)
            : 0;

        // Average balance per user
        $totalUsers = User::count();
        $totalBalance = Wallet::sum('balance');
        $avgBalance = $totalUsers > 0 ? $totalBalance / $totalUsers : 0;

        return [
            Stat::make('User Aktif (30 Hari)', $activeUsers)
                ->description('Memiliki transaksi')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success')
                ->chart([3, 5, 4, 7, 6, 8, 5]),

            Stat::make('User Baru Minggu Ini', $newUsersThisWeek)
                ->description('Sejak ' . $now->copy()->startOfWeek()->format('d M'))
                ->descriptionIcon('heroicon-o-user-plus')
                ->color('info')
                ->chart([1, 2, 1, 3, 2, 4, 3]),

            Stat::make('Retention Rate (7 Hari)', $retentionRate . '%')
                ->description($retainedUsers . ' dari ' . $usersOlderThan7Days . ' user')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color($retentionRate >= 50 ? 'success' : ($retentionRate >= 25 ? 'warning' : 'danger'))
                ->chart([4, 5, 3, 6, 7, 5, 8]),

            Stat::make('Rata-rata Saldo / User', 'Rp ' . number_format($avgBalance, 0, ',', '.'))
                ->description('Dari ' . $totalUsers . ' pengguna')
                ->descriptionIcon('heroicon-o-calculator')
                ->color('primary')
                ->chart([5, 6, 4, 7, 5, 8, 6]),
        ];
    }
}
