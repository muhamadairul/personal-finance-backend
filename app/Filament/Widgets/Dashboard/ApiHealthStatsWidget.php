<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\ApiLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ApiHealthStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $totalToday = ApiLog::where('created_at', '>=', $today)->count();
        $avgResponseTime = ApiLog::where('created_at', '>=', $today)->avg('duration_ms') ?? 0;

        $totalLogs = ApiLog::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        $errorLogs = ApiLog::where('created_at', '>=', Carbon::now()->subDays(7))
            ->where('status_code', '>=', 400)
            ->count();
        $errorRate = $totalLogs > 0 ? round(($errorLogs / $totalLogs) * 100, 2) : 0;

        $clientErrors = ApiLog::where('created_at', '>=', Carbon::now()->subDays(7))
            ->whereBetween('status_code', [400, 499])
            ->count();
        $serverErrors = ApiLog::where('created_at', '>=', Carbon::now()->subDays(7))
            ->where('status_code', '>=', 500)
            ->count();

        return [
            Stat::make('Avg Response Time', number_format($avgResponseTime, 1) . ' ms')
                ->description('Hari ini')
                ->descriptionIcon('heroicon-o-clock')
                ->color($avgResponseTime <= 200 ? 'success' : ($avgResponseTime <= 500 ? 'warning' : 'danger'))
                ->chart([5, 3, 7, 4, 6, 3, 5]),

            Stat::make('Error Rate (7 Hari)', $errorRate . '%')
                ->description($errorLogs . ' error dari ' . $totalLogs . ' request')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($errorRate <= 2 ? 'success' : ($errorRate <= 5 ? 'warning' : 'danger'))
                ->chart([2, 4, 1, 3, 5, 2, 4]),

            Stat::make('Total Log Hari Ini', number_format($totalToday))
                ->description('API requests')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info')
                ->chart([8, 5, 9, 6, 7, 10, 8]),

            Stat::make('Client / Server Errors', $clientErrors . ' / ' . $serverErrors)
                ->description('4xx / 5xx (7 hari)')
                ->descriptionIcon('heroicon-o-bug-ant')
                ->color($serverErrors > 0 ? 'danger' : ($clientErrors > 0 ? 'warning' : 'success'))
                ->chart([1, 3, 2, 4, 1, 3, 2]),
        ];
    }
}
