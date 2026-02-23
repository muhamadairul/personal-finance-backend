<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\ApiLog;
use App\Models\Category;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class MaintenanceStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalLogs = ApiLog::count();
        $oldLogs = ApiLog::where('created_at', '<', Carbon::now()->subDays(30))->count();
        $globalCategories = Category::whereNull('user_id')->count();

        return [
            Stat::make('Total API Logs', number_format($totalLogs))
                ->description('Seluruh log tersimpan')
                ->descriptionIcon('heroicon-o-server-stack')
                ->color('info')
                ->chart([3, 5, 7, 6, 8, 9, 10]),

            Stat::make('Logs > 30 Hari', number_format($oldLogs))
                ->description($oldLogs > 0 ? 'Dapat dibersihkan' : 'Semua log masih baru')
                ->descriptionIcon('heroicon-o-trash')
                ->color($oldLogs > 10000 ? 'danger' : ($oldLogs > 0 ? 'warning' : 'success'))
                ->chart([5, 4, 6, 5, 7, 6, 8]),

            Stat::make('Kategori Global', $globalCategories)
                ->description('Kategori default untuk semua user')
                ->descriptionIcon('heroicon-o-tag')
                ->color('primary')
                ->chart([2, 3, 2, 4, 3, 5, 4]),
        ];
    }
}
